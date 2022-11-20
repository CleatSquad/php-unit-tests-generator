<?php

namespace CleatSquad\PhpUnitTestGenerator\Test\Unit\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\FileSystemException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use CleatSquad\PhpUnitTestGenerator\Model\GeneratorInterface;
use CleatSquad\PhpUnitTestGenerator\Console\Command\Generate;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \CleatSquad\PhpUnitTestGenerator\Console\Command\Generate
 */
class GenerateTest extends TestCase
{
    /**
     * Mock GeneratorInterface
     *
     * @var GeneratorInterface|MockObject
     */
    private $generatorMock;

    /**
     * Class to test instance
     *
     * @var Generate
     */
    private Generate $generate;

    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->generatorMock = $this->getMockBuilder(GeneratorInterface::class)
        	->disableOriginalConstructor()
        	->getMock();

        $this->generate = new Generate(
        	$this->generatorMock
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                ['class1']
            ],
            [
                ['class1', 'class2'],
            ],
            [
                []
            ],
            [
                ['class1'],
                ['error1', 'error2']
            ],
            [
                ['class1', 'class2'],
                ['error1', 'error2']
            ],
            [
                [],
                ['error1', 'error2']
            ],
        ];
    }

    /**
     * @return void
     */
    public function testExecuteInvalidPathArgument()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Not enough arguments');
        $commandTester = new CommandTester($this->generate);
        $commandTester->execute([]);
    }

    /**
     * @param array $results
     * @param array $errors
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $results = [], array $errors = []) : void
    {
        $commandTester = new CommandTester($this->generate);
        $filePath = 'test';

        $this->generatorMock
            ->expects($this->once())
            ->method('generate')
            ->with($filePath)
            ->willReturn($results);
        if (count($errors)) {
            $this->generatorMock
                ->expects($this->exactly(2))
                ->method('getErrors')
                ->willReturn($errors);
        }
        $this->assertEquals(
            Cli::RETURN_SUCCESS,
            $commandTester->execute(['filepath' => $filePath])
        );
        $result = '';
        if (count($results)) {
            $result .= "The php unit test is generated successfully, for the following classes:\n";
        } else {
            $result .= "There is no generated php unit test.\n";
        }
        if (count($results)) {
            $result .=  implode(
                "\n",
                $results
            ). "\n";
        }
        if (count($errors)) {
            $result .=  implode(
                "\n",
                $errors
            ). "\n";
        }
        $this->assertEquals($result, $commandTester->getDisplay());
    }

    /**
     * @return void
     */
    public function testExecuteWithException() : void
    {
        $commandTester = new CommandTester($this->generate);
        $filePath = 'test';

        $this->generatorMock
            ->expects($this->once())
            ->method('generate')
            ->with($filePath)
            ->willThrowException(new FileSystemException(__('error')));

        $this->assertEquals(
            Cli::RETURN_FAILURE,
            $commandTester->execute(['filepath' => $filePath])
        );
        $this->assertEquals("error\n", $commandTester->getDisplay());
    }
}
