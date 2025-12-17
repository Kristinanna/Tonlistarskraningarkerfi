<?php

declare(strict_types=1);

namespace Drupal\musicsearch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\musicsearch\Service\MusicSearchService;
use Drupal\musicsearch\Service\MusicImportService;

/**
 * Controller for importing albums from external providers.
 */
final class MusicSearchController extends ControllerBase {

  /**
   * The music search service.
   *
   * @var \Drupal\musicsearch\Service\MusicSearchService
   */
  protected MusicSearchService $musicSearchService;

  /**
   * The music import service.
   *
   * @var \Drupal\musicsearch\Service\MusicImportService
   */
  protected MusicImportService $musicImportService;

  /**
   * Constructs a MusicSearchController object.
   */
  public function __construct(
    MusicSearchService $music_search_service,
    MusicImportService $music_import_service
  ) {
    $this->musicSearchService = $music_search_service;
    $this->musicImportService = $music_import_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('musicsearch.music_search_service'),
      $container->get('musicsearch.importer')
    );
  }

  /**
   * Import an album from Spotify by ID.
   *
   * @param string $spotify_id
   *   The Spotify album ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the created/updated node or back to search on error.
   */
  public function importSpotifyAlbum(string $spotify_id): RedirectResponse {
    // Fetch normalized album data from Spotify.
    $album = $this->musicSearchService->getSpotifyAlbumById($spotify_id);
    if (!$album) {
      $this->messenger()->addError($this->t('Spotify album not found.'));
      return $this->redirect('musicsearch.album_search');
    }

    // Import the album into Drupal (create or update node).
    $node = $this->musicImportService->importAlbum($album);

    $this->messenger()->addStatus($this->t(
      'Imported album @title from Spotify.',
      ['@title' => $album['title'] ?? '']
    ));

    return $this->redirect('entity.node.canonical', [
      'node' => $node->id(),
    ]);
  }

  /**
   * Import an album from Discogs by ID.
   *
   * @param string $discogs_id
   *   The Discogs release ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the created/updated node or back to search on error.
   */
  public function importDiscogsAlbum(string $discogs_id): RedirectResponse {
    // Fetch normalized album data from Discogs.
    $album = $this->musicSearchService->getDiscogsAlbumById($discogs_id);
    if (!$album) {
      $this->messenger()->addError($this->t('Discogs release not found.'));
      return $this->redirect('musicsearch.album_search');
    }

    $node = $this->musicImportService->importAlbum($album);

    $this->messenger()->addStatus($this->t(
      'Imported album @title from Discogs.',
      ['@title' => $album['title'] ?? '']
    ));

    return $this->redirect('entity.node.canonical', [
      'node' => $node->id(),
    ]);
  }

}
