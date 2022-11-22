<?php

// phpcs:disable Drupal.Commenting.DocComment.ParamGroup
// phpcs:disable Drupal.Commenting.FunctionComment.ParamMissingDefinition

namespace Drupal\os2forms_kl_forms\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\os2forms_kl_forms\Builder;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformEntityStorage;
use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class Commands extends DrushCommands {
  /**
   * The builder.
   *
   * @var \Drupal\os2forms_kl_forms\Builder
   */
  private Builder $builder;

  /**
   * The webform entity storage.
   *
   * @var \Drupal\webform\WebformEntityStorage
   */
  private WebformEntityStorage $webformStorage;

  /**
   * The constructor.
   */
  public function __construct(Builder $builder, EntityTypeManagerInterface $entityTypeManager) {
    $this->builder = $builder;
    $this->webformStorage = $entityTypeManager->getStorage('webform');
  }

  /**
   * Render KL form as YAML.
   *
   * @param string $url
   *   The XSD url.
   * @param string $elementName
   *   The optional element name.
   *
   * @command os2forms-kl-forms:render
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function render(string $url, string $elementName = NULL, array $options = []): void {
    $data = $this->builder->build($url, $elementName);
    $this->output()->write(Yaml::encode($data));
  }

  /**
   * Generate or update KL form.
   *
   * @param string $id
   *   The webform id.
   * @param string $url
   *   The XSD url.
   * @param string $elementName
   *   The optional element name.
   *
   * @option string $title
   *   The webform title.
   * @option string $category
   *   The webform category.
   *
   * @command os2forms-kl-forms:generate
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function generate(string $id, string $url, string $elementName = NULL, array $options = [
    'title' => NULL,
    'category' => 'KL',
  ]): void {
    $id = strtolower($id);

    $webform = $this->webformStorage->load($id);
    $created = FALSE;
    if (NULL !== $webform) {
      $question = sprintf('Webform with id %s already exists. Update it?', $webform->id());
      if (!$this->confirm($question, TRUE)) {
        return;
      }
    }
    else {
      $settings = []
        + Webform::getDefaultSettings();

      $webform = Webform::create([
        'id' => $id,
        'title' => $id,
        'settings' => $settings,
      ]);
      $created = TRUE;
    }

    $form = $this->builder->build($url, $elementName);
    if (isset($form['elements'])) {
      $webform->set('elements', Yaml::encode($form['elements']));
    }

    $title = $form['name'] ?? $id;
    if (NULL !== $title) {
      $webform->set('title', $title);
    }

    $description = $form['doc'] ?? NULL;
    if (NULL !== $description) {
      $webform->set('description', $description);
    }

    $options = array_filter($options);
    foreach ($options as $name => $value) {
      $webform->set($name, $value);
    }

    $webform->save();

    $this->output()->writeln([
      $created
        ? sprintf('Webform %s (%s) created', $webform->get('title'), $webform->id())
        : sprintf('Webform %s (%s) updated', $webform->get('title'), $webform->id()),
      sprintf('Show: %s', Url::fromRoute('entity.webform.canonical', ['webform' => $webform->id()], ['absolute' => TRUE])->toString()),
      sprintf('Edit: %s', Url::fromRoute('entity.webform.edit_form', ['webform' => $webform->id()], ['absolute' => TRUE])->toString()),
    ]);
  }

}
