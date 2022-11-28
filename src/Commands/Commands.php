<?php

// phpcs:disable Drupal.Commenting.DocComment.ParamGroup
// phpcs:disable Drupal.Commenting.FunctionComment.ParamMissingDefinition

namespace Drupal\os2forms_kl_forms\Commands;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\os2forms_kl_forms\Webform\Builder;
use Drupal\os2forms_kl_forms\Webform\Helper;
use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class Commands extends DrushCommands {
  /**
   * The builder.
   *
   * @var \Drupal\os2forms_kl_forms\Webform\Builder
   */
  private Builder $builder;

  /**
   * The helper.
   *
   * @var \Drupal\os2forms_kl_forms\Webform\Helper
   */
  private Helper $helper;

  /**
   * The constructor.
   */
  public function __construct(Builder $builder, Helper $helper) {
    $this->builder = $builder;
    $this->helper = $helper;
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
    $data = $this->builder->build($url, $elementName, $options);
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
    $webform = $this->helper->loadWebform($id);
    $isNewWebform = NULL === $webform;
    if (NULL !== $webform) {
      $question = sprintf('Webform with id %s already exists. Update it?', $webform->id());
      if (!$this->confirm($question, TRUE)) {
        return;
      }
    }

    $webform = $this->helper->generate($id, $url, $elementName, $options);

    $this->output()->writeln([
      $isNewWebform
        ? sprintf('Webform %s (%s) created', $webform->get('title'), $webform->id())
        : sprintf('Webform %s (%s) updated', $webform->get('title'), $webform->id()),
      sprintf('Show: %s', Url::fromRoute('entity.webform.canonical', ['webform' => $webform->id()], ['absolute' => TRUE])->toString()),
      sprintf('Edit: %s', Url::fromRoute('entity.webform.edit_form', ['webform' => $webform->id()], ['absolute' => TRUE])->toString()),
    ]);
  }

}
