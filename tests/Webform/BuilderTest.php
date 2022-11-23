<?php

namespace Drupal\os2forms_kl_forms\Webform;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Builder test.
 */
final class BuilderTest extends TestCase {

  /**
   * Test Builder::build().
   *
   * @dataProvider provideBuildData
   */
  public function testBuild(string $xsdFilename, string $expectedFilename, array $options = []) {
    $builder = new Builder();

    $expected = Yaml::parseFile($expectedFilename);
    $actual = $builder->build($xsdFilename);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider.
   */
  public function provideBuildData() {
    yield [
      __DIR__ . '/resources/xsd/profile/KLB_ApplicationToCareForCloselyConnectedPersons_PN151.xsd',
      __DIR__ . '/resources/webform/KLB_ApplicationToCareForCloselyConnectedPersons_PN151.yaml',
    ];
  }

}
