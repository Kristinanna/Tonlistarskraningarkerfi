<?php

namespace Drupal\musicsearch\Service;

/**
 * Central service for searching and looking up albums across providers.
 */
class MusicSearchService {

  /**
   * Spotify lookup service, if available.
   *
   * @var object|null
   */
  protected $spotifyLookup;

  /**
   * Discogs lookup service, if available.
   *
   * @var object\vert null
   */
  protected $discogsLookup;

  /**
   * Constructor.
   *
   * Arguments must match the definitions in musicsearch.services.yml.
   *
   * @param object|null $spotify_lookup_service
   *   The Spotify lookup service.
   * @param object\vert null $discogs_lookup_service
   *   The Discogs lookup service.
   */
  public function __construct($spotify_lookup_service = NULL, $discogs_lookup_service = NULL) {
    $this->spotifyLookup = $spotify_lookup_service;
    $this->discogsLookup = $discogs_lookup_service;
  }

  /**
   * Search for albums across selected providers.
   *
   * @param string $query
   *   Search string.
   * @param string[] $providers
   *   List of provider machine names (e.g. ['spotify', 'discogs']).
   *
   * @return array
   *   List of normalized album arrays.
   */
  public function searchAlbums(string $query, array $providers = ['spotify']): array {
    $results = [];

    // Spotify search.
    if (in_array('spotify', $providers, TRUE) && $this->spotifyLookup) {
      $spotify_results = $this->spotifyLookup->searchAlbums($query);
      foreach ($spotify_results as $raw_album) {
        $results[] = $this->normalizeSpotifyAlbum($raw_album);
      }
    }

    // Discogs search.
    if (in_array('discogs', $providers, TRUE) && $this->discogsLookup) {
      $discogs_results = $this->discogsLookup->searchAlbums($query);
      foreach ($discogs_results as $raw_album) {
        $results[] = $this->normalizeDiscogsAlbum($raw_album);
      }
    }

    return $results;
  }

  /**
   * Fetch a single Spotify album by ID and normalize it.
   *
   * @param string $id
   *   Spotify album ID.
   *
   * @return array\vert null
   *   Normalized album array, or NULL if not found.
   */
  public function getSpotifyAlbumById(string $id): ?array {
    if (!$this->spotifyLookup) {
      return NULL;
    }

    $raw_album = $this->spotifyLookup->getAlbumById($id);
    if (!$raw_album) {
      return NULL;
    }

    return $this->normalizeSpotifyAlbum($raw_album, TRUE);
  }

  /**
   * Fetch a single Discogs album by ID and normalize it.
   *
   * @param string $id
   *   Discogs release ID.
   *
   * @return array|null
   *   Normalized album array, or NULL if not found.
   */
  public function getDiscogsAlbumById(string $id): ?array {
    if (!$this->discogsLookup) {
      return NULL;
    }

    $raw_album = $this->discogsLookup->getReleaseById($id);
    if (!$raw_album) {
      return NULL;
    }

    return $this->normalizeDiscogsAlbum($raw_album, TRUE);
  }

  /**
   * Normalize a Spotify album to the common structure.
   *
   * @param mixed $raw
   *   Raw album data from Spotify.
   * @param bool $with_tracks
   *   Whether to include track information.
   *
   * @return array
   *   Normalized album array.
   */
  protected function normalizeSpotifyAlbum($raw, bool $with_tracks = FALSE): array {
    $data = is_array($raw) ? $raw : (array) $raw;

    $id = $data['id'] ?? NULL;
    $title = $data['name'] ?? '';
    $artist = !empty($data['artists'][0]['name']) ? $data['artists'][0]['name'] : '';
    $year = !empty($data['release_date']) ? (int) substr($data['release_date'], 0, 4) : NULL;

    // First image as cover.
    $cover_url = !empty($data['images'][0]['url']) ? $data['images'][0]['url'] : NULL;

    $album = [
      'provider'    => 'spotify',
      'title'       => $title,
      'artist'      => $artist,
      'year'        => $year,
      'spotify_id'  => $id,
      'discogs_id'  => NULL,
      'cover_url'   => $cover_url,
      'tracks'      => [],
    ];

    if ($with_tracks && !empty($data['tracks']['items'])) {
      foreach ($data['tracks']['items'] as $track) {
        $t = is_array($track) ? $track : (array) $track;
        $album['tracks'][] = [
          'title'       => $t['name'] ?? '',
          'track_number'=> $t['track_number'] ?? NULL,
          'duration_ms' => $t['duration_ms'] ?? NULL,
          'spotify_id'  => $t['id'] ?? NULL,
          'discogs_id'  => NULL,
        ];
      }
    }

    return $album;
  }

  /**
   * Normalize a Discogs release to the common structure.
   *
   * @param mixed $raw
   *   Raw release data from Discogs.
   * @param bool $with_tracks
   *   Whether to include track information.
   *
   * @return array
   *   Normalized album array.
   */
  protected function normalizeDiscogsAlbum($raw, bool $with_tracks = FALSE): array {
    $data = is_array($raw) ? $raw : (array) $raw;

    $id = $data['id'] ?? NULL;
    $title = $data['title'] ?? '';
    $artist = !empty($data['artists'][0]['name']) ? $data['artists'][0]['name'] : '';
    $year = $data['year'] ?? NULL;

    // Discogs often has 'cover_image' or 'thumb'.
    $cover_url = $data['cover_image'] ?? ($data['thumb'] ?? NULL);

    $album = [
      'provider'    => 'discogs',
      'title'       => $title,
      'artist'      => $artist,
      'year'        => $year,
      'spotify_id'  => NULL,
      'discogs_id'  => $id,
      'cover_url'   => $cover_url,
      'tracks'      => [],
    ];

    if ($with_tracks && !empty($data['tracklist'])) {
      $track_number = 0;
      foreach ($data['tracklist'] as $track) {
        $t = is_array($track) ? $track : (array) $track;
        $track_number++;
        $album['tracks'][] = [
          'title'       => $t['title'] ?? '',
          'track_number'=> $track_number,
          'duration_ms' => NULL, // Could parse "3:45" if desired.
          'spotify_id'  => NULL,
          'discogs_id'  => NULL,
        ];
      }
    }

    return $album;
  }

}
