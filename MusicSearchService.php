<?php

namespace Drupal\musicsearch;

use Drupal\spotify_lookup\SpotifyLookupService;

class MusicSearchService {

  /**
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected $spotify;

  public function __construct(SpotifyLookupService $spotify /*, DiscogsLookupService $discogs */) {
    $this->spotify = $spotify;
    // $this->discogs = $discogs;
  }

  /**
   * Search albums across providers.
   */
  public function searchAlbums(string $query): array {
    // Call Spotify (and later Discogs) and normalize.
    $results = $this->spotify->searchAlbums($query);

    // Ensure we always return a list of normalized album arrays.
    return $results;
  }

  /**
   * Get album details for a specific provider/id.
   */
  /**
 * Get detailed info for a single album by Spotify ID.
 *
 * @param string|int $id
 *   Spotify album ID.
 *
 * @return array\vert null
 *   Normalized album data or NULL on failure.
 */
public function getAlbumDetails($id): ?array {
  $token = $this->getToken();

  if (!$token | !$id) {
    return NULL;
  }

  try {
    $response = $this->httpClient->request('GET', "https://api.spotify.com/v1/albums/{$id}", [
      'headers' => [
        'Authorization' => 'Bearer ' . $token,
      ],
    ]);

    $data = json_decode($response->getBody()->getContents(), TRUE);

    if (empty($data)) {
      return NULL;
    }

    return [
      'provider' => 'spotify',
      'id' => $data['id'] ?? NULL,
      'title' => $data['name'] ?? '',
      'artists' => !empty($data['artists'])
        ? array_map(static fn($a) => $a['name'] ?? '', $data['artists'])
        : [],
      'year' => !empty($data['release_date'])
        ? substr($data['release_date'], 0, 4)
        : NULL,
      'cover_url' => !empty($data['images'][0]['url'])
        ? $data['images'][0]['url']
        : NULL,
      'track_count' => !empty($data['tracks']['items'])
        ? count($data['tracks']['items'])
        : NULL,

      // Normalized list of tracks for the importer.
      'tracks' => !empty($data['tracks']['items']) && is_array($data['tracks']['items'])
        ? array_map(
            static function (array $track): array {
              return [
                'title'        => $track['name']        ?? '',
                'spotify_id'   => $track['id']          ?? NULL,
                'duration_ms'  => $track['duration_ms'] ?? NULL,
                'track_number' => $track['track_number'] ?? NULL,
              ];
            },
            $data['tracks']['items']
          )
        : [],
    ];
  }
  catch (GuzzleException $e) {
    return NULL;
  }
}


}
