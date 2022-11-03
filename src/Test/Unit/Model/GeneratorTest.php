<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Test\Unit\Model;

use Magento\Framework\Exception\FileSystemException;

/**
 * @covers \CleatSquad\PhpUnitTestGenerator\Unit\Model\Generator
 */
class GeneratorTest extends \PHPUnit\Framework\TestCase
{
    private \CleatSquad\PhpUnitTestGenerator\Model\Generator $generator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->generator = new \CleatSquad\PhpUnitTestGenerator\Model\Generator(

        );
    }

    /**
     * @return array
     */
    public function dataProviderForTestParseClassName()
    {
        return [
            'basic Magento class' => [
                'path' =>  __DIR__ . '/_files/class.php',
                'expectedResult' => '\Olmer\UnitTestsGenerator\Code\Generator\ClassNameParser'
            ]
        ];
    }

    /**
     * @dataProvider dataProviderForTestParseClassName
     */
    public function testGenerate(string $path, string $expected)
    {
        dump($path);
        $result = $this->generator->generate($path);
        dump($result);
        $this->assertEquals($expected, $result);
    }
}
