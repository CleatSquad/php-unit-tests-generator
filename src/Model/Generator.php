<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;

use CleatSquad\PhpUnitTestGenerator\Api\GeneratorInterface;
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

    public function __construct(
        ClassesScanner $classesScanner,
        FileClassScannerFactory $fileClassScannerFactory,
        UnitTestGeneratorFactory $unitTestGeneratorFactory,
        \CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator\BlockFactory $unitTestGeneratorBlockFactory
    ) {
        $this->classesScanner = $classesScanner;
        $this->fileClassScannerFactory = $fileClassScannerFactory;
        $this->unitTestGeneratorFactory = $unitTestGeneratorFactory;
        $this->unitTestGeneratorBlockFactory = $unitTestGeneratorBlockFactory;
    }

    /**
     * @param string $path
     * @return mixed|void
     */
    public function generate(string $path)
    {
        foreach ($this->loadClasses($path) as $sourceClass) {
            $resultClass = \explode('\\', trim($sourceClass, '\\'));
            \array_splice($resultClass, 2, 0, 'Test\\Unit');
            $resultClass = \implode('\\', $resultClass) . 'Test';
            if (class_exists($resultClass)) {
                continue;
            }
            $class = new \ReflectionClass($sourceClass);
            if ($class->isSubclassOf(\Magento\Framework\View\Element\Template::class)) {
                $generator = $this->unitTestGeneratorBlockFactory->create([
                    'sourceClassName' => $sourceClass,
                    'resultClassName' => $resultClass,
                ]);
            } else {
                $generator = $this->unitTestGeneratorFactory->create([
                    'sourceClassName' => $sourceClass,
                    'resultClassName' => $resultClass
                ]);
            }

            $generator->generate();
        }
        return $resultClass;
    }

    /**
     * @return array|string[]
     * @throws FileSystemException
     */
    private function loadClasses(string $path) : array
    {
        $classes = [];
        $fileinfo = new \SplFileInfo($path);

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
