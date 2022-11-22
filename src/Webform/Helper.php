<?php

namespace Drupal\os2forms_kl_forms\Webform;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;

/**
 * The helper.
 */
class Helper {
  /**
   * The builder.
   *
   * @var \Drupal\os2forms_kl_forms\Webform\Builder
   */
  private Builder $builder;

  /**
   * The webform entity storage.
   *
   * @var \Drupal\webform\WebformEntityStorageInterface
   */
  private WebformEntityStorageInterface $webformEntityStorage;

  /**
   * The constructor.
   */
  public function __construct(Builder $builder, EntityTypeManagerInterface $entityTypeManager) {
    $this->builder = $builder;
    $this->webformEntityStorage = $entityTypeManager->getStorage('webform');
  }

  /**
   * Generate webform.
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function generate(string $id, string $url, string $elementName = NULL, array $options = []): WebformInterface {
    $id = strtolower($id);
    $form = $this->builder->build($url, $elementName, $options + ['id' => $id]);

    $webform = $this->loadWebform($id);
    if (NULL === $webform) {
      $settings = []
        + Webform::getDefaultSettings();

      $webform = Webform::create([
        'id' => $form['id'],
        'settings' => $settings,
      ]);
    }

    foreach ($form as $name => $value) {
      if ('elements' === $name) {
        $value = Yaml::encode($value);
      }
      $webform->set($name, $value);
    }

    $webform->save();

    return $webform;
  }

  /**
   * Load webform by id.
   */
  public function loadWebform(string $id): ?WebformInterface {
    $id = strtolower($id);

    return $this->webformEntityStorage->load($id);
  }

}
