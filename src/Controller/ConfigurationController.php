<?php


namespace Drupal\configuration_archive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WebPageArchiveController.
 */
class ConfigurationController extends ControllerBase {

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * Constructs a ConfigurationController object.
   *
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(FileDownloadController $file_download_controller) {
    $this->fileDownloadController = $file_download_controller;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(new FileDownloadController());
  }


  /**
   * Attempts to download the specified file.
   */
  public function download($id, $filename) {
    // TODO: Security validation needed?
    $save_dir = "configuration-archive/{$id}";
    $request = new Request(['file' => "{$save_dir}/{$filename}"]);
    return $this->fileDownloadController->download($request, );
  }

}
