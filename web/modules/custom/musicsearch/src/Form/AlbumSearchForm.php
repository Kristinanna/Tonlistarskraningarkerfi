<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\musicsearch\Service\MusicSearchService;

/**
 * Admin form to search for albums (Spotify, Discogs, etc.).
 */
class AlbumSearchForm extends FormBase {

  /**
   * The music search service.
   *
   * @var \Drupal\musicsearch\Service\MusicSearchService
   */
  protected $musicSearchService;

  /**
   * AlbumSearchForm constructor.
   */
  public function __construct(MusicSearchService $music_search_service) {
    $this->musicSearchService = $music_search_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Adjust this service ID to whatever you actually defined
      // in musicsearch.services.yml.
      $container->get('musicsearch.search')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'musicsearch_album_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album / artist'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('query') ?: '',
      '#size' => 40,
    ];

    // Multi-provider support: Spotify + Discogs.
    $form['providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Providers'),
      '#options' => [
        'spotify' => $this->t('Spotify'),
        'discogs' => $this->t('Discogs'),
      ],
      '#default_value' => $form_state->getValue('providers') ?: ['spotify'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    // Results from the last submit (set in submitForm()).
    $results = $form_state->get('results');
    if (!empty($results) && is_array($results)) {
      $header = [
        'provider' => $this->t('Provider'),
        'title' => $this->t('Album'),
        'artist' => $this->t('Artist'),
        'year' => $this->t('Year'),
        'actions' => $this->t('Actions'),
      ];

      $rows = [];

      foreach ($results as $album) {
        // Expected normalized structure from MusicSearchService:
        // [
        //   'provider'   => 'spotify'|'discogs',
        //   'title'      => '...',
        //   'artist'     => '...',
        //   'year'       => 2020,
        //   'spotify_id' => '...',
        //   'discogs_id' => '...',
        //   ...
        // ]
        $provider_key = $album['provider'] ?? '';
        $provider_label = [
          'spotify' => $this->t('Spotify'),
          'discogs' => $this->t('Discogs'),
        ][$provider_key] ?? $provider_key;

        $title = $album['title'] ?? '';
        $artist = $album['artist'] ?? '';
        $year = $album['year'] ?? '';

        $spotify_id = $album['spotify_id'] ?? NULL;
        $discogs_id = $album['discogs_id'] ?? NULL;

        // Default action if we can’t import.
        $actions_renderable = [
          '#markup' => $this->t('N/A'),
        ];

        // Provider-specific import links.
        if ($provider_key === 'spotify' && !empty($spotify_id)) {
          $url = Url::fromRoute('musicsearch.import_spotify_album', [
            'spotify_id' => $spotify_id,
          ]);
          $actions_renderable = Link::fromTextAndUrl(
            $this->t('Import from Spotify'),
            $url
          )->toRenderable();
        }
        elseif ($provider_key === 'discogs' && !empty($discogs_id)) {
          $url = Url::fromRoute('musicsearch.import_discogs_album', [
            'discogs_id' => $discogs_id,
          ]);
          $actions_renderable = Link::fromTextAndUrl(
            $this->t('Import from Discogs'),
            $url
          )->toRenderable();
        }

        $rows[] = [
          'provider' => [
            'data' => ['#markup' => $provider_label],
          ],
          'title' => [
            'data' => ['#markup' => $this->t('@title', ['@title' => $title])],
          ],
          'artist' => [
            'data' => ['#markup' => $this->t('@artist', ['@artist' => $artist])],
          ],
          'year' => [
            'data' => [
              '#markup' => $year
                ? $this->t('@year', ['@year' => $year])
                : '',
            ],
          ],
          'actions' => [
            'data' => $actions_renderable,
          ],
        ];
      }

      $form['results'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No results found.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = trim($form_state->getValue('query'));
    if ($query === '') {
      $this->messenger()->addWarning($this->t('Please enter a search term.'));
      return;
    }

    // Normalize providers: keep only checked values.
    $providers = array_filter($form_state->getValue('providers') ?: []);
    if (empty($providers)) {
      $providers = ['spotify'];
    }

    try {
      // Adjust the signature to match your actual MusicSearchService:
      // e.g. public function searchAlbums(string $query, array $providers = ['spotify']);
      $results = $this->musicSearchService->searchAlbums($query, $providers);
      $form_state->set('results', $results);
    }
    catch (\Exception $e) {
      $this->messenger()->addError(
        $this->t('Search failed: @msg', ['@msg' => $e->getMessage()])
      );
      $form_state->set('results', []);
    }

    // Rebuild to display results in the same request cycle.
    $form_state->setRebuild(TRUE);
  }

}

