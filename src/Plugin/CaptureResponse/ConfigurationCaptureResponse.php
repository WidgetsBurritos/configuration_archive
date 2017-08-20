<?php

namespace Drupal\configuration_archive\Plugin\CaptureResponse;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;

/**
 * Capture Reponse.
 */
class ConfigurationCaptureResponse extends UriCaptureResponse {

  /**
   * {@inheritdoc}
   */
  public function renderable(array $options = []) {
    $filename = basename($this->content);
    $path_parts = explode('/', dirname($this->content));
    
    $link = Link::createFromRoute($filename, 'configuration_archive.download', ['id' => end($path_parts), 'filename' => $filename], ['attributes' => ['target' => '_blank']])->toRenderable();
    return $link;
  }
}
