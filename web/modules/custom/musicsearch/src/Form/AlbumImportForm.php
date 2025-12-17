namespace Drupal\musicsearch\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\musicsearch\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AlbumImportForm extends FormBase {

  protected $musicSearch;
  protected $entityTypeManager;

  public function __construct(MusicSearchService $musicSearch, EntityTypeManagerInterface $entityTypeManager) {
    $this->musicSearch = $musicSearch;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('musicsearch.service'),
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'musicsearch_album_import';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $provider = NULL, $id = NULL) {
    $album = $this->musicSearch->getAlbumDetails($provider, $id);
    if (!$album) {
      $this->messenger()->addError($this->t('Could not load album details.'));
      return $form;
    }

    // Store for submit
    $form_state->set('provider', $provider);
    $form_state->set('provider_id', $id);

    // Basic fields mapped from normalized array → your album fields.
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $album['title'],
      '#required' => TRUE,
    ];

    $form['artist_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist'),
      '#default_value' => $album['artist_name'],
    ];

    $form['release_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Release year'),
      '#default_value' => $album['release_year'],
    ];

    $form['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label / Publisher'),
      '#default_value' => $album['label'],
    ];

    $form['music_genre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Genres (comma-separated)'),
      '#default_value' => implode(', ', $album['genres']),
    ];

    $form['tracklist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tracklist (one per line)'),
      '#default_value' => implode("\n", $album['tracklist']),
    ];

    // Hidden cover art URL for later download into image field.
    $form['cover_art_url'] = [
      '#type' => 'hidden',
      '#value' => $album['cover_art_url'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create album'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $provider    = $form_state->get('provider');
    $provider_id = $form_state->get('provider_id');

    $values = $form_state->getValues();

    $storage = $this->entityTypeManager->getStorage('node');
    $node = $storage->create([
      'type'  => 'album',
      'title' => $values['title'],
    ]);

    // Field mappings – adjust based on actual field types:
    $node->set('field_release_year', $values['release_year']);
    $node->set('field_publisher', $values['publisher']);
    $node->set('field_music_genre', $values['music_genre']);
    $node->set('field_lysing', $values['tracklist']); // or description if more appropriate

    // Tracklist: if field_tracklist is a long text, use textarea content.
    $node->set('field_tracklist', $values['tracklist']);

    // Artist: if field_artist is just text, set directly; if entity reference, you’d look up/create the Artist node first.
    $node->set('field_artist', $values['artist_name']);

    // External IDs:
    if ($provider === 'spotify') {
      $node->set('field_spotify_album_id', $provider_id);
    }
    elseif ($provider === 'discogs') {
      $node->set('field_discogs_release_id', $provider_id);
    }

    // TODO: download and attach cover art to field_cover_art if you want.

    $node->save();

    $this->messenger()->addStatus($this->t('Album %title created.', ['%title' => $node->label()]));
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
