<?php declare(strict_types=1);

namespace Drupal\spotify_lookup\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for looking up music data from Spotify.
 */
class SpotifyLookupService {

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructor.
   */
  public function __construct(
    ClientInterface $http_client,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory
  ) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
  }

  /**
   * Get an access token from Spotify, with caching.
   *
   * @return string|null
   *   The access token, or NULL on failure.
   */
  protected function getAccessToken(): ?string {
    $cache_id = 'spotify_lookup:access_token';

    // Try cached token first.
    if ($cache = $this->cache->get($cache_id)) {
      if (is_string($cache->data) && $cache->data !== '') {
        return $cache->data;
      }
    }

    $config = $this->configFactory->get('musicsearch.settings');
    $clientId = $config->get('spotify_client_id');
    $clientSecret = $config->get('spotify_client_secret');

    if (!$clientId || !$clientSecret) {
      \Drupal::logger('spotify_lookup')->error('Spotify Client ID/Secret not configured in musicsearch.settings.');
      return null;
    }

    $authHeader = base64_encode($clientId . ':' . $clientSecret);

    try {
      $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
        'headers' => [
          'Authorization' => 'Basic ' . $authHeader,
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
        'timeout' => 5,
      ]);
    }
    catch (GuzzleException $e) {
      \Drupal::logger('spotify_lookup')->error(
        'Spotify token error: @msg',
        ['@msg' => $e->getMessage()]
      );
      return null;
    }

    $data = json_decode((string) $response->getBody(), true);
    if (empty($data['access_token'])) {
      \Drupal::logger('spotify_lookup')->error('Spotify token response missing access_token.');
      return null;
    }

    $accessToken = $data['access_token'];
    $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;

    // Cache a bit less than the official expiry to be safe.
    $expire = time() + max(60, $expiresIn - 60);
    $this->cache->set($cache_id, $accessToken, $expire);

    return $accessToken;
  }

  /**
   * Search for albums on Spotify.
   *
   * @param string $query
   *   Search term.
   *
   * @return array
   *   Raw Spotify album items.
   */
  public function searchAlbums(string $query): array {
    $query = trim($query);
    if ($query === '') {
      return [];
    }

    $token = $this->getAccessToken();
    if (!$token) {
      return [];
    }

    $uri = 'https://api.spotify.com/v1/search';
    $params = [
      'q' => $query,
      'type' => 'album',
      'limit' => 20,
    ];

    // Cache per query.
    $cache_id = 'spotify_lookup:search:' . md5($uri . '?' . http_build_query($params));
    if ($cache = $this->cache->get($cache_id)) {
      return is_array($cache->data) ? $cache->data : [];
    }

    try {
      $response = $this->httpClient->request('GET', $uri, [
        'query' => $params,
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
        'timeout' => 5,
      ]);
    }
    catch (GuzzleException $e) {
      \Drupal::logger('spotify_lookup')->error(
        'Spotify search error: @msg',
        ['@msg' => $e->getMessage()]
      );
      return [];
    }

    $data = json_decode((string) $response->getBody(), true);

    if (empty($data['albums']['items']) |  !is_array($data['albums']['items'])) {
      return [];
    }

    $items = $data['albums']['items'];

    // Cache for 5 minutes.
    $this->cache->set($cache_id, $items, time() + 300);

    return $items;
  }

  /**
   * Get full album details by Spotify ID.
   *
   * @param string $id
   *   Spotify album ID.
   *
   * @return array\vert null
   *   Album data, or NULL on failure.
   */
  public function getAlbumById(string $id): ?array {
    $id = trim($id);
    if ($id === '') {
      return null;
    }

    $token = $this->getAccessToken();
    if (!$token) {
      return null;
    }

    $uri = 'https://api.spotify.com/v1/albums/' . $id;
    $cache_id = 'spotify_lookup:album:' . $id;

    // Cache album details.
    if ($cache = $this->cache->get($cache_id)) {
      return is_array($cache->data) ? $cache->data : null;
    }

    try {
      $response = $this->httpClient->request('GET', $uri, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
        'timeout' => 5,
      ]);
    }
    catch (GuzzleException $e) {
      \Drupal::logger('spotify_lookup')->error(
        'Spotify album error: @msg',
        ['@msg' => $e->getMessage()]
      );
      return null;
    }

    $data = json_decode((string) $response->getBody(), true);
    if (!is_array($data)) {
      return null;
    }

    $this->cache->set($cache_id, $data, time() + 300);

    return $data;
  }

}
