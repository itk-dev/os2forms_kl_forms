<?php

namespace Drupal\os2forms_kl_forms;

use Drupal\os2forms_kl_forms\Exception\BuildException;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
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
   * @phpstan-return array<string, mixed>
   */
  public function build(string $url, string $elementName = NULL): array {
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

    return $this->buildElementItem($element);
  }

  /**
   * Build element item.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildElementItem(ElementItem $element): array {
    $item = [
      'name' => $element->getName(),
    ];

    if ($element instanceof SchemaItem) {
      $item['doc'] = $element->getDoc();
    }

    if ($element instanceof ElementDef) {
      // var_dump($element->getReferencedElement()->getDoc());
    }
    elseif ($element instanceof ElementRef) {
      // var_dump($element->getReferencedElement()->getDoc());
    }
    elseif ($element instanceof Element) {
      // var_export([$element->getDefault()]);
    }
    else {
      throw new BuildException(sprintf('Unhandled element %s', get_class($element)));
    }

    $type = $element->getType();

    $item['// #type'] = get_class($type);
    $item['// #type.name'] = $type->getName();

    if ($type instanceof ComplexTypeSimpleContent) {
      $attributes = [];
      foreach ($type->getAttributes() as $attribute) {
        $attributes[] = $attribute->getName();
      }
      $item['attributes'] = $attributes;
    }

    if ($type instanceof ComplexType) {
      $elements = [];
      foreach ($type->getElements() as $element) {
        assert($element instanceof ElementItem);
        $elements[] = $this->buildElementItem($element);
      }
      $item['elements'] = $elements;
    }
    elseif ($type instanceof ComplexTypeSimpleContent) {
      $item['content'] = $type->getDoc();
    }
    elseif ($type instanceof SimpleType) {
    }
    else {
      throw new BuildException(sprintf('Unhandled type %s', get_class($type)));
    }

    return array_filter($item);
  }

}
