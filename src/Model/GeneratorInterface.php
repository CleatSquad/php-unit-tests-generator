<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;

use Magento\Framework\Exception\FileSystemException;

/**
 * Class GeneratorInterface
 * @package CleatSquad\PhpUnitTestGenerator\Api
 */
interface GeneratorInterface
{
    /**
     * @param string $path
     * @return array
     * @throws FileSystemException
     * @throws ReflectionException
     */
    public function generate(string $path): array;

    /**
     * @return array
     */
    public function getErrors(): array;
}
