<?php

namespace Drupal\musicsearch\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Handles importing albums, songs, and basic artists into Drupal.
 */
class MusicImportService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileRepositoryInterface $fileRepository,
    protected FileSystemInterface $fileSystem,
    protected ClientInterface $httpClient,
  ) {}

  /**
   * Import or update an Album from normalized data.
   *
   * Expected $album structure (from MusicSearchService):
   * - provider (string) // 'spotify' or 'discogs'
   * - title (string)
   * - artist (string) // single artist name
   * - year (int\vert string\vert null)
   * - spotify_id (string\vert null)
   * - discogs_id (string\vert null)
   * - cover_url (string\vert null)
   * - tracks (array of:
   *   - title (string)
   *   - spotify_id (string\vert null)
   *   - discogs_id (string\vert null)
   *   - duration_ms (int\vert null)
   *   - track_number (int\vert null)
   *   )
   */
  public function importAlbum(array $album): NodeInterface {
    $title = trim($album['title'] ?? '');
    if ($title === '') {
      throw new \InvalidArgumentException('Album title is required for import.');
    }

    // 1. Ensure Artist node exists (single artist string).
    $artist_name = trim($album['artist'] ?? '');
    $artist_ids = [];
    if ($artist_name !== '') {
      $artist_ids = $this->ensureArtists([$artist_name]);
    }

    // 2. Find existing album or create a new one.
    $existing = $this->findExistingAlbum($title, $artist_ids);

    /** @var \Drupal\node\NodeInterface $node */
    if ($existing) {
      $node = $existing;
    }
    else {
      $storage = $this->entityTypeManager->getStorage('node');
      $node = $storage->create([
        'type' => 'album',
        'title' => $title,
        'status' => 1,
      ]);
    }

    // 3. Merge fields (only fill if empty to avoid overwriting manual edits).

    // Artists.
    if ($node->get('field_artist')->isEmpty() && !empty($artist_ids)) {
      $node->set('field_artist', array_map(static fn($id) => ['target_id' => $id], $artist_ids));
    }

    // Release year.
    if (!empty($album['year']) && $node->get('field_release_year')->isEmpty()) {
      $node->set('field_release_year', (int) $album['year']);
    }

    // Cover art (Media: image).
    if (!empty($album['cover_url']) && $node->get('field_cover_art')->isEmpty()) {
      if ($media = $this->ensureCoverMedia($album['cover_url'], $title)) {
        $node->set('field_cover_art', ['target_id' => $media->id()]);
      }
    }

    $node->save();

    // 4. Import tracks as Song nodes and attach to tracklist.
    if (!empty($album['tracks']) && is_array($album['tracks'])) {
      $this->importTracks($album['tracks'], $node);
    }

    return $node;
  }

  /**
   * Ensure Artist nodes exist for the given list of names.
   *
   * Returns an array of node IDs (Artist).
   */
  protected function ensureArtists(array $artist_names): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = [];

    foreach ($artist_names as $name) {
      $name = trim((string) $name);
      if ($name === '') {
        continue;
      }

      // Try to find existing Artist by title.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'artist')
        ->condition('title', $name)
        ->range(0, 1);

      $existing_ids = $query->execute();

      if ($existing_ids) {
        $ids[] = reset($existing_ids);
        continue;
      }

      /** @var \Drupal\node\NodeInterface $artist */
      $artist = $storage->create([
        'type' => 'artist',
        'title' => $name,
        'status' => 1,
      ]);
      $artist->save();
      $ids[] = $artist->id();
    }

    return $ids;
  }

  /**
   * Find an existing album by title and (optionally) first artist.
   */
  protected function findExistingAlbum(string $title, array $artist_ids): ?NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'album')
      ->condition('title', $title)
      ->range(0, 1);

    // If we have at least one artist, also match on field_artist.
    if (!empty($artist_ids)) {
      $query->condition('field_artist', reset($artist_ids));
    }

    $ids = $query->execute();
    if (!$ids) {
      return NULL;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $storage->load(reset($ids));
    return $node;
  }

  /**
   * Import tracks as Song nodes and attach them to the Album tracklist.
   */
  protected function importTracks(array $tracks, NodeInterface $album): void {
    $song_ids = [];

    foreach ($tracks as $track) {
      $title = trim($track['title'] ?? '');
      if ($title === '') {
        continue;
      }

      $spotify_id = $track['spotify_id'] ?? '';
      $duration_ms = $track['duration_ms'] ?? NULL;

      $song = $this->ensureSong($title, $spotify_id, $duration_ms);
      if ($song) {
        $song_ids[] = $song->id();
      }
    }

    if (!$song_ids) {
      return;
    }

    // Only fill field_tracklist if it is empty (do not override manual tracklists).
    if ($album->get('field_tracklist')->isEmpty()) {
      $album->set('field_tracklist', array_map(
        static fn($id) => ['target_id' => $id],
        $song_ids
      ));
      $album->save();
    }
  }

  /**
   * Ensure a Song node exists for a given track.
   *
   * Deduplication strategy:
   * - If spotify_id present: match by field_spotify_id.
   * - Otherwise: match by title only.
   */
  protected function ensureSong(
    string $title,
    string $spotify_id = '',
    ?int $duration_ms = NULL,
  ): ?NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');

    // 1. Try to find by Spotify ID if provided.
    if ($spotify_id !== '') {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'song')
        ->condition('field_spotify_id', $spotify_id)
        ->range(0, 1);

      $ids = $query->execute();
      if ($ids) {
        return $storage->load(reset($ids));
      }
    }

    // 2. Fallback: try to find by title only.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'song')
      ->condition('title', $title)
      ->range(0, 1);

    $ids = $query->execute();
    if ($ids) {
      /** @var \Drupal\node\NodeInterface $song */
      $song = $storage->load(reset($ids));

      // Optionally, update missing spotify_id or length if we now have them.
      $changed = FALSE;
      if ($spotify_id !== '' && $song->get('field_spotify_id')->isEmpty()) {
        $song->set('field_spotify_id', $spotify_id);
        $changed = TRUE;
      }
      if ($duration_ms !== NULL && $song->get('field_length')->isEmpty()) {
        $song->set('field_length', $this->formatDuration($duration_ms));
        $changed = TRUE;
      }
      if ($changed) {
        $song->save();
      }
      return $song;
    }

    // 3. Create a new Song.
    $values = [
      'type' => 'song',
      'title' => $title,
      'status' => 1,
    ];

    if ($spotify_id !== '') {
      $values['field_spotify_id'] = $spotify_id;
    }
    if ($duration_ms !== NULL) {
      $values['field_length'] = $this->formatDuration($duration_ms);
    }

    /** @var \Drupal\node\NodeInterface $song */
    $song = $storage->create($values);
    $song->save();
    return $song;
  }

  /**
   * Format duration in milliseconds as "m:ss" string for field_length.
   */
  protected function formatDuration(int $duration_ms): string {
    if ($duration_ms <= 0) {
      return '';
    }
    $seconds = (int) round($duration_ms / 1000);
    $minutes = intdiv($seconds, 60);
    $rest = $seconds % 60;
    return sprintf('%d:%02d', $minutes, $rest);
  }

  /**
   * Ensure a Media (image) entity exists for the given remote image URL.
   *
   * Returns the Media entity or NULL on failure.
   */
  protected function ensureCoverMedia(string $url, string $album_title = ''): ?Media {
    $url = trim($url);
    if ($url === '') {
      return NULL;
    }

    // Download the image data.
    try {
      $response = $this->httpClient->request('GET', $url, [
        'http_errors' => FALSE,
        'timeout' => 10,
      ]);
      if ($response->getStatusCode() >= 400) {
        return NULL;
      }
      $data = $response->getBody()->getContents();
    }
    catch (GuzzleException $e) {
      return NULL;
    }

    if ($data === '') {
      return NULL;
    }

    // Determine a destination URI in public://.
    $directory = 'public://album_covers';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $basename = basename(parse_url($url, PHP_URL_PATH) ?: 'cover.jpg');
    $destination = $directory . '/' . $basename;

    // Save file.
    $file = $this->fileRepository->writeData($data, $destination, FileSystemInterface::EXISTS_RENAME);
    if (!$file) {
      return NULL;
    }

    // Create Media entity of type "image".
    $media = Media::create([
      'bundle' => 'image',
      'name' => $album_title ?: $file->getFilename(),
      'status' => 1,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => $album_title,
      ],
    ]);
    $media->save();

    return $media;
  }

}
