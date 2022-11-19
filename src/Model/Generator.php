<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;

use CleatSquad\PhpUnitTestGenerator\Model\GeneratorInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Setup\Module\Di\Code\Reader\ClassesScanner\Proxy as ClassesScanner;
use Magento\Setup\Module\Di\Code\Reader\FileClassScannerFactory;

/**
 * Class Generator
 * @package CleatSquad\PhpUnitTestGenerator\Model
 */
class Generator implements GeneratorInterface
{
    private array $excludePatterns = ["?Test/?"];
    private ClassesScanner $classesScanner;
    private FileClassScannerFactory $fileClassScannerFactory;
    private array $errors = [];

    public function __construct(
        ClassesScanner $classesScanner,
        FileClassScannerFactory $fileClassScannerFactory,
        UnitTestGeneratorFactory $unitTestGeneratorFactory,
        \CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\BlockFactory $unitTestGeneratorBlockFactory,
        \CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\RepositoryFactory $unitTestGeneratorRepositoryFactory,
        \CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\ObserverFactory $unitTestGeneratorObserverFactory
    ) {
        $this->classesScanner = $classesScanner;
        $this->fileClassScannerFactory = $fileClassScannerFactory;
        $this->unitTestGeneratorFactory = $unitTestGeneratorFactory;
        $this->unitTestGeneratorBlockFactory = $unitTestGeneratorBlockFactory;
        $this->unitTestGeneratorRepositoryFactory = $unitTestGeneratorRepositoryFactory;
        $this->unitTestGeneratorObserverFactory = $unitTestGeneratorObserverFactory;
    }

    /**
     * @param string $path
     * @return mixed|void
     */
    public function generate(string $path)
    {
        $resultClasses = [];
        foreach ($this->loadClasses($path) as $sourceClass) {
            $resultClass = \explode('\\', trim($sourceClass, '\\'));
            \array_splice($resultClass, 2, 0, 'Test\\Unit');
            $resultClass = \implode('\\', $resultClass) . 'Test';
            if (class_exists($resultClass)) {
                $this->addError('Unit test for ' . $sourceClass . ' is already genearted.');
                continue;
            }
            /**
             * elseif (str_contains(strtolower($sourceClass), 'repository')) {
            $generator = $this->unitTestGeneratorRepositoryFactory->create([
            'sourceClassName' => $sourceClass,
            'resultClassName' => $resultClass,
            ]);
            }
             */
            $class = new \ReflectionClass($sourceClass);
            if ($class->isSubclassOf(\Magento\Framework\View\Element\Template::class)) {
                $generator = $this->unitTestGeneratorBlockFactory->create([
                    'sourceClassName' => $sourceClass,
                    'resultClassName' => $resultClass,
                ]);
            }  if ($class->isSubclassOf(ObserverInterface::class)) {
                $generator = $this->unitTestGeneratorObserverFactory->create([
                    'sourceClassName' => $sourceClass,
                    'resultClassName' => $resultClass,
                ]);
            }  else {
                $generator = $this->unitTestGeneratorFactory->create([
                    'sourceClassName' => $sourceClass,
                    'resultClassName' => $resultClass
                ]);
            }
            $generator->generate();
            if ($generator->getErrors()) {
                $this->errors = array_merge($this->errors, $generator->getErrors());
            } else {
                $resultClasses[] = $sourceClass;
            }
        }
        return $resultClasses;
    }

    /**
     * @param string $error
     * @return void
     */
    private function addError(string $error)
    {
        $this->errors[] = $error;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array|string[]
     * @throws FileSystemException
     */
    private function loadClasses(string $path) : array
    {
        $classes = [];
        $fileinfo = new \SplFileInfo($path);
        $this->classesScanner->addExcludePatterns($this->excludePatterns);

        if ($fileinfo->isFile()) {
            $classes = [$this->getClassName($fileinfo->getRealPath())];
            $recursiveIterator[] = $fileinfo;
        } elseif ($fileinfo->isDir()) {
            $classes = $this->classesScanner->getList($path);
        } else {
            throw new FileSystemException(
                new \Magento\Framework\Phrase('The "%1" path is invalid. Verify the path and try again.', [$path])
            );
        }
        return $classes;
    }

    /**
     * @param string $fileItem
     * @return string
     */
    private function getClassName(string $fileItem)
    {
        $fileScanner = $this->fileClassScannerFactory->create([$fileItem]);
        return $fileScanner->getClassName();
    }
}
