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
use Magento\Setup\Module\Di\Code\Reader\FileClassScanner;

/**
 * Class Generator
 * @package CleatSquad\PhpUnitTestGenerator\Model
 */
class Generator implements GeneratorInterface
{
    private array $excludePatterns = ["?Test/?"];

    /**
     * @param string $path
     * @return mixed|void
     */
    public function generate(string $path)
    {
        $realPath = realpath($path);
        if (!(bool)$realPath) {
            throw new FileSystemException(
                new \Magento\Framework\Phrase('The "%1" path is invalid. Verify the path and try again.', [$path])
            );
        }
        $recursiveIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realPath, \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $classes = $this->extract($recursiveIterator);
        dump($classes);


        //$fileContents = $this->reader->fileGetContents($path);

        /*$reflector = new \ReflectionClass('Foo');
        echo $reflector->getFileName();*/
        /*$sourceClass = $this->getClassName($path);
        if (!\class_exists($sourceClass)) {
            return null;
        }*/
        return 'ola pma';
    }

    /**
     * Extracts all the classes from the recursive iterator
     *
     * @param \RecursiveIteratorIterator $recursiveIterator
     * @return array
     */
    private function extract(\RecursiveIteratorIterator $recursiveIterator)
    {
        $classes = [];
        foreach ($recursiveIterator as $fileItem) {
            /** @var $fileItem \SplFileInfo */
            if ($fileItem->isDir() || $fileItem->getExtension() !== 'php' || $fileItem->getBasename()[0] == '.') {
                continue;
            }
            $fileItemPath = $fileItem->getRealPath();
            foreach ($this->excludePatterns as $excludePatterns) {
                if ($this->isExclude($fileItemPath, $excludePatterns)) {
                    continue 2;
                }
            }
            $fileScanner = new FileClassScanner($fileItemPath);
            $className = $fileScanner->getClassName();
            if (!empty($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Find out if file should be excluded
     *
     * @param string $fileItemPath
     * @param string $patterns
     * @return bool
     */
    private function isExclude($fileItemPath, $patterns)
    {
        if (!is_array($patterns)) {
            $patterns = (array)$patterns;
        }
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, str_replace('\\', '/', $fileItemPath))) {
                return true;
            }
        }
        return false;
    }
}
