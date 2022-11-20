<?php
namespace CleatSquad\PhpUnitTestGenerator\Test\Unit\Model;

use CleatSquad\PhpUnitTestGenerator\Model\Exist;
use CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassBlock;
use CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassCommand;
use CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassObserver;
use CleatSquad\PhpUnitTestGenerator\Model\Generator\Foo;
use Magento\Framework\Exception\FileSystemException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Setup\Module\Di\Code\Reader\ClassesScanner\Proxy;
use Magento\Setup\Module\Di\Code\Reader\FileClassScannerFactory;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGeneratorFactory;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\BlockFactory;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\ConsoleCommandFactory;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\RepositoryFactory;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\ObserverFactory;
use CleatSquad\PhpUnitTestGenerator\Model\Generator;
use Magento\Setup\Module\Di\Code\Reader\FileClassScanner;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\Block;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\ConsoleCommand;
use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\Observer;
use ReflectionException;
use SplFileInfo;

/**
 * @covers \CleatSquad\PhpUnitTestGenerator\Model\Generator
 */
class GeneratorTest extends TestCase
{
    /**
     * Mock Proxy
     *
     * @var Proxy|MockObject
     */
    private $classesScannerMock;

    /**
     * Mock FileClassScannerFactory
     *
     * @var FileClassScannerFactory|MockObject
     */
    private $fileClassScannerFactoryMock;

    /**
     * Mock UnitTestGeneratorFactory
     *
     * @var UnitTestGeneratorFactory|MockObject
     */
    private $unitTestGeneratorFactoryMock;

    /**
     * Mock BlockFactory
     *
     * @var BlockFactory|MockObject
     */
    private $unitTestGeneratorBlockFactoryMock;

    /**
     * Mock ConsoleCommandFactory
     *
     * @var ConsoleCommandFactory|MockObject
     */
    private $unitTestGeneratorConsoleCommandFactoryMock;

    /**
     * Mock RepositoryFactory
     *
     * @var RepositoryFactory|MockObject
     */
    private $unitTestGeneratorRepositoryFactoryMock;

    /**
     * Class to test instance
     *
     * @var Generator
     */
    private $generator;

    /**
     * Recursive `glob()`.
     *
     * @param string $path
     * @param string|null $flags Behavior bitmask
     * @return array|bool
     */
    private function rglob(string $path, ?string $flags = null): array
    {
        $result = glob($path . '/**', $flags);
        foreach ($result as $key => $item) {
            if (is_dir($item)) {
                $this->rglob($item, $flags);
                array_push($result, ...$this->rglob($item, $flags));
                unset($result[$key]);
            }
        }
        return $result;
    }

    /**
     * @return void
     */
    public function setUp() : void
    {
        foreach ($this->rglob(__DIR__ . '/_files') as $filename) {
            include_once $filename;
        }
        $this->classesScannerMock = $this->getMockBuilder(Proxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileClassScannerFactoryMock = $this->getMockBuilder(FileClassScannerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unitTestGeneratorFactoryMock = $this->getMockBuilder(UnitTestGeneratorFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorBlockFactoryMock = $this->getMockBuilder(BlockFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorConsoleCommandFactoryMock = $this->getMockBuilder(ConsoleCommandFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorRepositoryFactoryMock = $this->getMockBuilder(RepositoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorObserverFactoryMock = $this->getMockBuilder(ObserverFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->generator = new Generator(
            $this->classesScannerMock,
            $this->fileClassScannerFactoryMock,
            $this->unitTestGeneratorFactoryMock,
            $this->unitTestGeneratorBlockFactoryMock,
            $this->unitTestGeneratorConsoleCommandFactoryMock,
            $this->unitTestGeneratorRepositoryFactoryMock,
            $this->unitTestGeneratorObserverFactoryMock
        );
    }

    /**
     * @return array
     */
    public function generatedDataProvider(): array
    {
        return [
            'Class foo' => [
                'path' =>  __DIR__ . '/_files/generator/Foo.php',
                'classNames' => ['CleatSquad\PhpUnitTestGenerator\Model\Generator\Foo']
            ],
            'Class bar' => [
                'path' =>  __DIR__ . '/_files/generator/Bar.php',
                'classNames' => ['CleatSquad\PhpUnitTestGenerator\Model\Generator\Bar']
            ],
            'All classes' => [
                'path' =>  __DIR__ . '/_files/generator',
                'classNames' => [
                    'CleatSquad\PhpUnitTestGenerator\Model\Generator\Foo',
                    'CleatSquad\PhpUnitTestGenerator\Model\Generator\Bar',
                    'CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassObserver',
                    'CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassBlock',
                    'CleatSquad\PhpUnitTestGenerator\Model\Generator\Types\ClassCommand'
                ]
            ]
        ];
    }

    /**
     * @param string $path
     * @param array $classNames
     * @return void
     * @throws FileSystemException
     * @throws ReflectionException
     * @dataProvider generatedDataProvider
     */
    public function testGenerate(string $path, array $classNames = []) : void
    {
        $fileInfo = new SplFileInfo($path);
        if ($fileInfo->isFile()) {
            $fileClassScannerInstanceMock = $this->getMockBuilder(FileClassScanner::class)
                ->disableOriginalConstructor()
                ->getMock();
            $fileClassScannerInstanceMock
                ->expects($this->once())
                ->method('getClassName')
                ->willReturn($classNames[0]);
            $this->fileClassScannerFactoryMock
                ->expects($this->once())
                ->method('create')
                ->with([$fileInfo->getRealPath()])
                ->willReturn($fileClassScannerInstanceMock);
        } elseif ($fileInfo->isDir()) {
            $this->classesScannerMock
                ->expects($this->once())
                ->method('getList')
                ->with($path)
                ->willReturn($classNames);
        }
        $iterators = [
            'observer' => 0,
            'global' => 0,
            'block' => 0,
            'command' => 0
        ];
        foreach ($classNames as $className) {
            $resultClass = $this->getResultClass($className);
            if ($className == ClassObserver::class) {
                $iteratorKey = 'observer';
                $unitTestGeneratorFactory = $this->unitTestGeneratorObserverFactoryMock;
                $unitTestGeneratorInstanceMock = $this->getMockBuilder(Observer::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            } elseif ($className == ClassBlock::class) {
                $iteratorKey = 'block';
                $unitTestGeneratorFactory = $this->unitTestGeneratorBlockFactoryMock;
                $unitTestGeneratorInstanceMock = $this->getMockBuilder(Block::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            } elseif ($className == ClassCommand::class) {
                $iteratorKey = 'command';
                $unitTestGeneratorFactory = $this->unitTestGeneratorConsoleCommandFactoryMock;
                $unitTestGeneratorInstanceMock = $this->getMockBuilder(ConsoleCommand::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            } else {
                $iteratorKey = 'global';
                $unitTestGeneratorFactory = $this->unitTestGeneratorFactoryMock;
                $unitTestGeneratorInstanceMock = $this->getMockBuilder(UnitTestGenerator::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            }
            $unitTestGeneratorInstanceMock
                ->expects($this->at($iterators[$iteratorKey]))
                ->method('generate');
            $unitTestGeneratorFactory
                ->expects($this->at($iterators[$iteratorKey]))
                ->method('create')
                ->with([
                    'sourceClassName' => $className,
                    'resultClassName' => $resultClass
                ])
                ->willReturn($unitTestGeneratorInstanceMock);
            //test has error
            $iterators[$iteratorKey]++;
        }
        $result = $this->generator->generate($path);
        $this->assertEquals($classNames, $result);
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws ReflectionException
     */
    public function testGetErrorsGenerator() : void
    {
        $errors = ['error1', 'error2'];
        $fileClassScannerInstanceMock = $this->getMockBuilder(FileClassScanner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fileClassScannerInstanceMock
            ->expects($this->any())
            ->method('getClassName')
            ->willReturn(Foo::class);
        $this->fileClassScannerFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fileClassScannerInstanceMock);
        $unitTestGeneratorInstanceMock = $this->getMockBuilder(UnitTestGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($unitTestGeneratorInstanceMock);
        $unitTestGeneratorInstanceMock
            ->expects($this->any())
            ->method('getErrors')
            ->willReturn($errors);
        $this->generator->generate(__DIR__ . '/_files/generator/Foo.php');
        $this->assertEquals(
            $errors,
            $this->generator->getErrors()
        );
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws ReflectionException
     */
    public function testGetErrorsResultClassExist() : void
    {
        $fileClassScannerInstanceMock = $this->getMockBuilder(FileClassScanner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fileClassScannerInstanceMock
            ->expects($this->any())
            ->method('getClassName')
            ->willReturn(Exist::class);
        $this->fileClassScannerFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fileClassScannerInstanceMock);

        $unitTestGeneratorInstanceMock = $this->getMockBuilder(UnitTestGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->unitTestGeneratorFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($unitTestGeneratorInstanceMock);
        $result = $this->generator->generate(__DIR__ . '/_files/Exist.php');
        $this->assertEmpty($result);
        $this->assertEquals(
            ['Unit test for CleatSquad\PhpUnitTestGenerator\Model\Exist is already generated.'],
            $this->generator->getErrors()
        );
    }

    /**
     * @return void
     * @throws ReflectionException|FileSystemException
     */
    public function testGenerateFileSystemException() : void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('The "file" path is invalid. Verify the path and try again.');
        $this->generator->generate('file');
    }

    /**
     * @return void
     * @throws ReflectionException|FileSystemException
     */
    public function testGenerateReflectionException() : void
    {
        $fileClassScannerInstanceMock = $this->getMockBuilder(FileClassScanner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fileClassScannerInstanceMock
            ->expects($this->any())
            ->method('getClassName')
            ->willReturn('class');
        $this->fileClassScannerFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fileClassScannerInstanceMock);
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class class does not exist');
        $this->generator->generate(__DIR__ . '/_files/generator/Foo.php');
    }

    /**
     * @param $className
     * @return string
     */
    protected function getResultClass($className): string
    {
        $resultClass = \explode('\\', trim($className, '\\'));
        \array_splice($resultClass, 2, 0, 'Test\\Unit');
        return \implode('\\', $resultClass) . 'Test';
    }
}
