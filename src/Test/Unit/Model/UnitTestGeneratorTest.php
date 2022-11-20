<?php
namespace CleatSquad\PhpUnitTestGenerator\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;

/**
 * @covers \CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator
 */
class UnitTestGeneratorTest extends TestCase
{
    /**
     * Class to test instance
     *
     * @var UnitTestGenerator
     */
    private $unitTestGenerator;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->unitTestGenerator = new UnitTestGenerator();
    }

    /**
     * @return void
     */
    public function testAddUse() : void
    {
        $useMock;
        $useAliasMock;
        $this->unitTestGenerator->addUse($useMock, $useAliasMock);
    }
}
