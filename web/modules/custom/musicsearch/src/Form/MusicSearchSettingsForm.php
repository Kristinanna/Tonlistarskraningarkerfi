<?php

namespace Drupal\musicsearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for MusicSearch API settings.
 */
class MusicSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'musicsearch_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['musicsearch.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('musicsearch.settings');

    $form['discogs'] = [
      '#type' => 'details',
      '#title' => $this->t('Discogs settings'),
      '#open' => TRUE,
    ];

    $form['discogs']['discogs_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Discogs API token'),
      '#default_value' => $config->get('discogs_token') ?: '',
      '#description' => $this->t('Personal access token from discogs.com.'),
      '#required' => FALSE,
      '#rows' => 2,
    ];

    $form['spotify'] = [
      '#type' => 'details',
      '#title' => $this->t('Spotify settings'),
      '#open' => TRUE,
    ];

    // Option A: store a pre-generated OAuth token.
    $form['spotify']['spotify_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Spotify access token'),
      '#default_value' => $config->get('spotify_token') ?: '',
      '#description' => $this->t('Bearer token for the Spotify Web API (for development you can paste a token here).'),
      '#required' => FALSE,
      '#rows' => 2,
    ];

    // Option B (if you want client credentials):
    $form['spotify']['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify client ID'),
      '#default_value' => $config->get('spotify_client_id') ?: '',
      '#required' => FALSE,
    ];

    $form['spotify']['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify client secret'),
      '#default_value' => $config->get('spotify_client_secret') ?: '',
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->configFactory
      ->getEditable('musicsearch.settings')
      ->set('discogs_token', $form_state->getValue('discogs_token'))
      ->set('spotify_token', $form_state->getValue('spotify_token'))
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->save();
  }

}
