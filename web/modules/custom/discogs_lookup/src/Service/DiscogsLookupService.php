<?php

namespace Drupal\discogs_lookup\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for looking up music data from Discogs.
 */
class DiscogsLookupService {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * DiscogsLookupService constructor.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    // Uses musicsearch.settings where discogs_token is stored.
    $this->config = $config_factory->get('musicsearch.settings');
  }

  /**
   * Get the configured Discogs token.
   */
  protected function getToken(): ?string {
    $token = $this->config->get('discogs_token');
    return $token ?: NULL;
  }

  /**
   * Search for albums on Discogs.
   *
   * @param string $query
   *   Search string (album title, artist, etc.).
   *
   * @return array
   *   Array of album results (basic info, no tracks).
   *
   * Each result has:
   * - provider (string) = 'discogs'
   * - id (int|string)
   * - title (string)
   * - artists (string[])
   * - year (int|string|null)
   * - cover_url (string|null)
   */
  public function searchAlbums(string $query): array {
    $token = $this->getToken();
    if (!$token | !$query) {
      return [];
    }

    try {
      $response = $this->httpClient->request('GET', 'https://api.discogs.com/database/search', [
        'query' => [
          'q' => $query,
          'type' => 'release',
          'token' => $token,
        ],
        'headers' => [
          // Discogs requires a meaningful User-Agent.
          'User-Agent' => 'DrupalMusicSearch/1.0 +http://example.com',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $results = [];

      if (!empty($data['results']) && is_array($data['results'])) {
        foreach ($data['results'] as $item) {
          $results[] = [
            'provider' => 'discogs',
            'id' => $item['id'] ?? NULL,
            'title' => $item['title'] ?? '',
            // Keep artists as an array for compatibility with MusicImportService.
            'artists' => !empty($item['artist']) ? [$item['artist']] : [],
            'year' => $item['year'] ?? NULL,
            'cover_url' => $item['cover_image'] ?? NULL,
          ];
        }
      }

      return $results;
    }
    catch (GuzzleException $e) {
      // You can log this if needed:
      // \Drupal::logger('discogs_lookup')->error($e->getMessage());
      return [];
    }
  }

  /**
   * Get detailed info for a single album by Discogs release ID.
   *
   * @param string|int $id
   *   Discogs release ID.
   *
   * @return array\vert null
   *   Normalized album data or NULL on failure.
   *
   * Returned structure matches MusicImportService::importAlbum():
   * - provider (string)
   * - id (string)
   * - title (string)
   * - artists (string[])
   * - year (int\vert string\vert null)
   * - cover_url (string\vert null)
   * - tracks (array of):
   *   - title (string)
   *   - spotify_id (string\vert null)  // always '' for Discogs
   *   - duration_ms (int\vert null)
   *   - track_number (int\vert null)
   */
  public function getAlbumDetails($id): ?array {
    $token = $this->getToken();
    if (!$token |  !$id) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('GET', 'https://api.discogs.com/releases/' . $id, [
        'query' => [
          'token' => $token,
        ],
        'headers' => [
          'User-Agent' => 'DrupalMusicSearch/1.0 +http://example.com',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      if (empty($data)) {
        return NULL;
      }

      // Basic album fields.
      $title = $data['title'] ?? '';
      $artists = !empty($data['artists'])
        ? array_map(static fn($a) => $a['name'] ?? '', $data['artists'])
        : [];
      $year = $data['year'] ?? ($data['released'] ?? NULL);
      $cover_url = !empty($data['images'][0]['uri'])
        ? $data['images'][0]['uri']
        : NULL;

      // Build tracks list in the shape MusicImportService expects.
      $tracks = [];
      if (!empty($data['tracklist']) && is_array($data['tracklist'])) {
        foreach ($data['tracklist'] as $track) {
          // Discogs tracklist can include indexes/headings; filter to real tracks.
          if (($track['type_'] ?? 'track') !== 'track') {
            continue;
          }

          $title_t = trim($track['title'] ?? '');
          if ($title_t === '') {
            continue;
          }

          $duration_str = $track['duration'] ?? '';
          $position = $track['position'] ?? '';

          $tracks[] = [
            'title' => $title_t,
            // No Spotify ID for Discogs releases; importer will dedupe by title.
            'spotify_id' => '',
            'duration_ms' => $this->parseDurationToMs($duration_str),
            'track_number' => $this->parseTrackNumber($position),
          ];
        }
      }

      return [
        'provider' => 'discogs',
        'id' => (string) ($data['id'] ?? $id),
        'title' => $title,
        'artists' => $artists,
        'year' => $year,
        'cover_url' => $cover_url,
        'tracks' => $tracks,
      ];
    }
    catch (GuzzleException $e) {
      // \Drupal::logger('discogs_lookup')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Parse a Discogs "mm:ss" duration string into milliseconds.
   */
  protected function parseDurationToMs(string $duration): ?int {
    $duration = trim($duration);
    if ($duration === '') {
      return NULL;
    }

    // Common formats: "3:45", "03:45". Ignore malformed values.
    if (!preg_match('/^(\d+):(\d{1,2})$/', $duration, $m)) {
      return NULL;
    }

    $minutes = (int) $m[1];
    $seconds = (int) $m[2];
    return ($minutes * 60 + $seconds) * 1000;
  }

  /**
   * Parse a Discogs position like "A1", "B2", or just "1" into a track number.
   */
  protected function parseTrackNumber(string $position): ?int {
    $position = trim($position);
    if ($position === '') {
      return NULL;
    }

    // If it's purely numeric, just cast.
    if (ctype_digit($position)) {
      return (int) $position;
    }

    // If it's like "A1", "B2", "CD3", strip non-digits and use the numeric part.
    $num = preg_replace('/\D+/', '', $position);
    if ($num === '') {
      return NULL;
    }

    return (int) $num;
  }

}
