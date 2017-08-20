<?php

namespace Drupal\Tests\configuration_archive\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests web page archive.
 *
 * @group configuration_archive
 */
class ConfigurationCaptureTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'minimal';

  /**
   * Authorized User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'web_page_archive',
    'configuration_archive',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->authorizedUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer web page archive',
    ]);
  }

  /**
   * Tests cron processes captures.
   */
  public function testCronProcessesCaptures() {
    $assert = $this->assertSession();

    // TODO: Replace capture url with nothing.
    // Grab the URL of the front page.
    $capture_url = $this->getUrl();

    // Login.
    $this->drupalLogin($this->authorizedUser);

    // Set our site name and email address.
    $config_factory = \Drupal::service('config.factory');
    $site_config = $config_factory->getEditable('system.site');
    $site_config->set('name', 'My config test');
    $site_config->set('mail', 'config.test@nobody.com');
    $site_config->save();

    // Confirm config changes.
    $this->drupalGet('admin/config/system/site-information');
    $assert->fieldValueEquals('site_name', 'My config test');
    $assert->fieldValueEquals('site_mail', 'config.test@nobody.com');

    // Verify list exists with add button.
    $this->drupalGet('admin/config/system/web-page-archive');
    $this->assertLinkByHref('admin/config/system/web-page-archive/add');

    // Add an entity using the entity form.
    $this->drupalGet('admin/config/system/web-page-archive/add');
    $this->drupalPostForm(
      NULL,
      [
        'label' => 'localhost',
        'id' => 'localhost',
        'timeout' => 500,
        'cron_schedule' => '* * * * *',
        'url_type' => 'url',
        'urls' => $capture_url,
      ],
      t('Create new archive')
    );
    $assert->pageTextContains('Created the localhost Web page archive entity.');

    // Add the configuration capture utility.
    $this->drupalPostForm(NULL, ['new' => 'ca_configuration_capture'], t('Add'));
    $assert->pageTextContains('Saved the localhost Web page archive entity.');
    $this->drupalPostForm(NULL, ['data[capture_list]' => ''], t('Add capture utility'));
    $assert->pageTextContains('The capture utility was successfully applied.');

    // Allow immediate cron run.
    \Drupal::state()->set('web_page_archive.next_run.localhost', 100);

    // Simulate a cron run.
    web_page_archive_cron();

    // Check canonical view to see if run occurred.
    $this->drupalGet('admin/config/system/web-page-archive/localhost');
    $assert->pageTextContains('Configuration capture utility');

    // Switched to detailed view.
    $this->clickLink('View Details');
    $file_path = str_replace(['://', ':', '/'], '-', $capture_url);
    $assert->responseMatches("/<span>.*{$file_path}\.tar.gz<\/span>/");

    // TODO: Open file and check for site name change.

  }

}
