<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;

use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;

/**
 * Class Block
 * @package CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator
 */
class Block extends UnitTestGenerator
{

    /**
     * @param array $methods
     * @return array
     */
    protected function getTestMethods(array $methods = []): array
    {
        try {
            $sourceReflectionClass = new \ReflectionClass($this->getSourceClassName());
            $publicMethods = $sourceReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($publicMethods as $method) {
                if ($method->getDocComment() && str_contains($method->getDocComment(), '@codeCoverageIgnore')) {
                    continue;
                }
                $declaringClass = '\\' . $method->getDeclaringClass()->getName();
                if (!($method->isConstructor() ||
                        $method->isFinal() ||
                        $method->isStatic() ||
                        $method->isDestructor()) &&
                    !$method->isAbstract() &&
                    $declaringClass == $this->getSourceClassName() &&
                    !(\strpos($method->getName(), '__') === 0)
                ) {
                    $testMethodName = 'test' . \ucfirst($method->getName());
                    $methods[] = [
                        'name' => $testMethodName,
                        'parameters' => [],
                        'body' =>
                            '$expected = "";' . "\n" .
                            \implode(
                                "\n",
                                \array_map(
                                    function (\ReflectionParameter $argument) {
                                        if ($argument->getType() && !$argument->getType()->isBuiltin()) {
                                            $type = $argument->getType();
                                            $this->addUse($type->getName());
                                            $alias = $this->uses[$type->getName()];
                                            return '$' .
                                                $argument->getName() .
                                                'Mock = $this->getMockBuilder(' .
                                                $alias .
                                                '::class)' .
                                                "\n\t->disableOriginalConstructor()" .
                                                "\n\t->getMock();";
                                        } else {
                                            return '$' . $argument->getName() . 'Mock =  null;';
                                        };
                                    },
                                    $method->getParameters()
                                )
                            ) . "\n" .
                            '$result = $this->' . lcfirst($this->getSourceClassNameWithoutNamespace()) . '->' .
                            $method->getName() .
                            '(' .
                            \implode(
                                ', ',
                                \array_map(
                                    function (\ReflectionParameter $argument) {
                                        return '$' . $argument->getName() . 'Mock';
                                    },
                                    $method->getParameters()
                                )
                            ) .
                            ');' ."\n" . '$this->assertEquals($expected, $result);',
                        'docblock' => [
                            'tags' => [
                                ['name' => 'return', 'description' => 'void']
                            ]
                        ],
                        'returnType' => 'void'
                    ];
                }
            }
        } catch (\ReflectionException $e) {
            return $methods;
        }

        return $methods;
    }
}
