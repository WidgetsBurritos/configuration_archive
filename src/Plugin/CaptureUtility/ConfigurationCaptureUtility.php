<?php

namespace Drupal\configuration_archive\Plugin\CaptureUtility;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;

/**
 * Skeleton capture utility, useful for creating new plugins.
 *
 * @CaptureUtility(
 *   id = "ca_configuration_capture",
 *   label = @Translation("Configuration capture utility", context = "Configuration Archive"),
 *   description = @Translation("Captures configuration settings periodically.", context = "Configuration Archive")
 * )
 */
class ConfigurationCaptureUtility extends ConfigurableCaptureUtilityBase {

  /**
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {
    // Configuration data is stored in $this->configuration. For example:
    $capture_list = $this->configuration['capture_list'];
    $capture_all = empty($capture_list);

    // If not empty, split up into separate items.
    if (!$capture_all) {
      $capture_list = array_map('trim', explode(PHP_EOL, $capture_list));
      $capture_list = array_combine($capture_list, $capture_list);
    }

    // Determine save file.
    // TODO: file_default_scheme() seems like a potentially insecure storage
    // location for config data. This should be evaluated and could potentially
    // get resolved by https://www.drupal.org/node/2901781.
    $file_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $save_dir = "{$file_path}/configuration-archive/{$data['web_page_archive']->id()}";
    $file_name = "config-{$data['run_uuid']}.tar.gz";
    $file_location = "{$save_dir}/{$file_name}";

    // If file already exists we should throw an exception.
    if (file_exists($file_location)) {
      throw new \Exception($this->t('@file already exists', ['@file' => $file_location]));
    }
    elseif (!file_prepare_directory($save_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      throw new \Exception($this->t("Could not write to @save_dir", ['@save_dir' => $save_dir]));
    }

    $archiver = new ArchiveTar($file_location, 'gz');
    $config_manager = \Drupal::service('config.manager');
    $target_storage = \Drupal::service('config.storage');

    // Get raw configuration data without overrides.
    foreach ($config_manager->getConfigFactory()->listAll() as $name) {
      if ($capture_all || array_key_exists($name, $capture_list)) {
        $archiver->addString("$name.yml", Yaml::encode($config_manager->getConfigFactory()->get($name)->getRawData()));
      }
    }
    // Get all override data from the remaining collections.
    foreach ($target_storage->getAllCollectionNames() as $collection) {
      $collection_storage = $target_storage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        if ($capture_all || array_key_exists($name, $capture_list)) {
          $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
        }
      }
    }

    $this->response = new UriCaptureResponse($file_location, $data['url']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'capture_list' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['capture_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Config capture list'),
      '#description' => $this->t('A list of configuration keys to capture. Leave empty to capture entire config.'),
      '#default_value' => $this->configuration['capture_list'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['capture_list'] = trim($form_state->getValue('capture_list'));
  }

}
