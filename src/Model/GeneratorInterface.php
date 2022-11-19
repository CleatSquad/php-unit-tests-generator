<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;

/**
 * Class GeneratorInterface
 * @package CleatSquad\PhpUnitTestGenerator\Api
 */
interface GeneratorInterface
{
    /**
     * @param string $path
     * @return mixed
     */
    public function generate(string $path);

    /**
     * @return array
     */
    public function getErrors(): array;
}
