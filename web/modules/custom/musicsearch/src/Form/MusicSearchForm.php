<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\musicsearch\Service\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple album search form using MusicSearchService.
 */
class MusicSearchForm extends FormBase {

  /**
   * @var \Drupal\musicsearch\Service\MusicSearchService
   */
  protected MusicSearchService $musicSearchService;

  /**
   * MusicSearchForm constructor.
   */
  public function __construct(MusicSearchService $musicSearchService) {
    $this->musicSearchService = $musicSearchService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      // Correct service ID.
      $container->get('musicsearch.music_search_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'musicsearch_album_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $query = $form_state->getValue('query') ?? '';

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search albums'),
      '#default_value' => $query,
      '#size' => 40,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    // Provider select.
    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#options' => [
        'spotify' => $this->t('Spotify'),
        'discogs' => $this->t('Discogs'),
        'all' => $this->t('Both'),
      ],
      '#default_value' => $form_state->getValue('provider') ?? 'spotify',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    // Results table – start with no rows.
    $form['results'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Artist'),
        $this->t('Year'),
        $this->t('Source'),
        $this->t('Actions'),
      ],
      '#rows' => [],
      '#empty' => $this->t('Enter a query and click Search.'),
    ];

    if (!empty($query)) {
      $provider = $form_state->getValue('provider') ?? 'spotify';

      // Map single select to array of providers for the service.
      switch ($provider) {
        case 'all':
          $providers = ['spotify', 'discogs'];
          break;

        case 'discogs':
          $providers = ['discogs'];
          break;

        case 'spotify':
        default:
          $providers = ['spotify'];
          break;
      }

      // MusicSearchService::searchAlbums(string $query, array $providers).
      $albums = $this->musicSearchService->searchAlbums($query, $providers);

      $rows = [];

      foreach ($albums as $album) {
        // Expected normalized structure from MusicSearchService:
        // [
        //   'provider'   => 'spotify' or 'discogs',
        //   'title'      => 'Album title',
        //   'artist'     => 'Artist name',
        //   'year'       => 1999 or null,
        //   'spotify_id' => 'spotify_album_id' (for Spotify),
        //   'discogs_id' => 'discogs_release_id' (for Discogs),
        // ]
        $provider_machine = $album['provider'] ?? '';
        $provider_label = ucfirst($provider_machine);
        $spotify_id = $album['spotify_id'] ?? NULL;

        // Each cell can be a plain string or a ['data' => ...] array.
        $row = [];

        $row[] = $album['title'] ?? '';
        $row[] = $album['artist'] ?? '';
        $row[] = $album['year'] ?? '';
        $row[] = $provider_label;

        if ($provider_machine === 'spotify' && !empty($spotify_id)) {
          $row[] = [
            'data' => [
              '#type' => 'link',
              '#title' => $this->t('Import'),
              '#url' => Url::fromRoute('musicsearch.import_spotify_album', [
                'spotify_id' => $spotify_id,
              ]),
              '#attributes' => [
                'class' => ['button', 'button--small'],
              ],
            ],
          ];
        }
        else {
          $row[] = [
            'data' => [
              '#markup' => $this->t('Import not available'),
            ],
          ];
        }

        $rows[] = $row;
      }

      if (!empty($rows)) {
        $form['results']['#rows'] = $rows;
      }
      else {
        $form['results']['#empty'] = $this->t('No results found.');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Rebuild to show results after submit.
    $form_state->setRebuild(TRUE);
  }

}
