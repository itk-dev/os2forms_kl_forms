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
        $id = $element['id'];
        unset($element['id']);
        $elements[$id] = $element;
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
   * @phpstan-return array<string, mixed>
   */
  private function buildElementItem(ElementItem $element): array {
    $item = [
      'id' => $element->getName(),
      '#title' => $element instanceof SchemaItem ? $element->getDoc() : $element->getName(),
    ];

    if ($element instanceof ElementDef) {
    }
    elseif ($element instanceof ElementRef) {
    }
    elseif ($element instanceof Element) {
    }
    else {
      throw new BuildException(sprintf('Unhandled element %s', get_class($element)));
    }

    $type = $element->getType();
    $item['#type'] = 'textfield';

    if ($type instanceof ComplexType) {
      $item['#type'] = 'webform_section';

      foreach ($type->getElements() as $element) {
        assert($element instanceof ElementItem);
        $element = $this->buildElementItem($element);
        $id = $element['id'];
        unset($element['id']);
        $item[$id] = $element;
      }
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

}
