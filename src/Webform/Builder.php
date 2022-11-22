<?php

namespace Drupal\os2forms_kl_forms\Webform;

use Drupal\os2forms_kl_forms\Exception\BuildException;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\SchemaReader;

/**
 * The builder.
 */
class Builder {

  /**
   * Build form.
   *
   * @phpstan-param array<string, mixed> $options
   * @phpstan-return array<string, mixed>
   */
  public function build(string $url, string $elementName = NULL, array $options = []): array {
    $reader = new SchemaReader();
    $schema = $reader->readFile($url);

    if (NULL !== $elementName) {
      $element = $schema->getElement($elementName);
      if (NULL === $element) {
        throw new BuildException(sprintf('Cannot find element %s', $elementName));
      }
    }
    else {
      $elements = $schema->getElements();
      $element = reset($elements) ?: NULL;
      if (NULL === $element) {
        throw new BuildException(sprintf('Cannot find element'));
      }
    }

    return $this->buildForm($element, $options);
  }

  /**
   * Build form.
   *
   * @phpstan-param array<string, mixed> $options
   * @phpstan-return array<string, mixed>
   */
  private function buildForm(ElementItem $element, array $options): array {
    $form = [
      'id' => $options['id'] ?? $element->getName(),
      'title' => $options['title'] ?? $element->getName(),
      'category' => $options['category'] ?? 'KL',
    ];

    if ($element instanceof SchemaItem) {
      $form['description'] = $element->getDoc();
    }

    $form['elements'] = $this->buildElements($element, $options);

    return $form;
  }

  /**
   * Build elements.
   *
   * @phpstan-param array<string, mixed> $options
   * @phpstan-return array<string, mixed>
   */
  private function buildElements(ElementItem $element, array $options): array {
    if (!$element instanceof Item) {
      throw new BuildException(sprintf('Found element of type %s; %s expected', get_class($element), Item::class));
    }

    $type = $element->getType();

    $elements = [];

    if ($type instanceof ComplexType) {
      foreach ($type->getElements() as $element) {
        assert($element instanceof ElementItem);
        $element = $this->buildElementItem($element);
        $key = $element['key'];
        unset($element['key']);
        $elements[$key] = $element;
      }
    }
    else {
      throw new BuildException(sprintf('Unhandled type %s', get_class($type)));
    }

    return $elements;
  }

  /**
   * Build element item.
   *
   * @param \GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem $elementItem
   *   The element item.
   * @param \GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem[] $rootPath
   *   The root path.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildElementItem(ElementItem $elementItem, array $rootPath = []): array {
    $title = NULL;
    if ($elementItem instanceof SchemaItem) {
      $title = $elementItem->getDoc();
    }
    if (empty($title)) {
      $title = $elementItem->getName();
    }
    $item = [
      'key' => $this->computeElementKey($elementItem, $rootPath),
      '#title' => $title,
    ];

    if ($elementItem instanceof ElementDef) {
    }
    elseif ($elementItem instanceof ElementRef) {
    }
    elseif ($elementItem instanceof Element) {
    }
    else {
      throw new BuildException(sprintf('Unhandled element %s', get_class($elementItem)));
    }

    $type = $elementItem->getType();
    $item['#type'] = 'textfield';

    if ($type instanceof ComplexType) {
      $item['#type'] = 'webform_section';

      array_push($rootPath, $elementItem);
      foreach ($type->getElements() as $element) {
        assert($elementItem instanceof ElementItem);
        $element = $this->buildElementItem($element, $rootPath);
        $key = $element['key'];
        unset($element['key']);
        $item[$key] = $element;
      }
      array_pop($rootPath);
    }
    elseif ($type instanceof ComplexTypeSimpleContent) {
    }
    elseif ($type instanceof SimpleType) {
    }
    else {
      throw new BuildException(sprintf('Unhandled type %s', get_class($type)));
    }

    return array_filter($item);
  }

  /**
   * Compute webform element key.
   *
   * An element key must contain only lowercase letters, numbers, and
   * underscores.
   *
   * @param \GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem $element
   *   The element item.
   * @param \GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem[] $rootPath
   *   The root path.
   */
  private function computeElementKey(ElementItem $element, array $rootPath): string {
    $names = [$element->getName(), ...array_map(static function (ElementItem $element) {
      return $element->getName();
    }, $rootPath),
    ];
    $key = implode('_', $names);

    // Convert to snake_case.
    // @see https://stackoverflow.com/a/19533226/2502647
    $key = ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $key)), '_');

    // Replace sequences of disallowed characters to underscore.
    $key = preg_replace('/[^a-z0-9_]+/', '_', $key);

    $maxLength = 64;
    if (strlen($key) > $maxLength) {
      $id = uniqid();
      $key = substr($key, 0, $maxLength - strlen($id) - 1) . '_' . $id;
    }

    return $key;
  }

}
