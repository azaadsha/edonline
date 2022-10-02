<?php

namespace Drupal\Tests\phone_international\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group phone_international
 */
class PhoneInternationalFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'phone_international',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Test to confirm the widget is setup.
   *
   * @covers \Drupal\phone_international\Plugin\Field\FieldWidget\PhoneInternationalDefaultWidget::formElement
   */
  public function testTelephoneWidget() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldExists("field_phone_international[0][value]");
  }

  /**
   * Test the phone_international formatter.
   *
   * @covers \Drupal\phone_international\Plugin\Field\FieldFormatter\PhoneInternationalDefaultFormatter::viewElements
   *
   * @dataProvider providerPhoneNumbers
   */
  public function testTelephoneFormatter($input, $expected) {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_phone_international[0][value]' => $input,
    ];

    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertSession()->responseContains($expected);
  }

  /**
   * Provides the phone numbers to check and expected results.
   */
  public function providerPhoneNumbers() {
    return [
      'standard phone number' => ['+123456789', '+123456789'],
      'standard phone number country' => ['+351123456789', '+351123456789'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
    ]);
    $this->drupalLogin($this->webUser);

    // Add the telephone field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_phone_international',
      'entity_type' => 'node',
      'type' => 'phone_international',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_phone_international',
      'label' => 'International Phone Number',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_phone_international', [
        'type' => 'phone_international_widget',
        'settings' => [
          'geolocation' => 'PT',
          'initial_country' => 0,
        ],
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_phone_international', [
        'type' => 'phone_international_formatter',
        'weight' => 1,
      ])
      ->save();
  }

}
