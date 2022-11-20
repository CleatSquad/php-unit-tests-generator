<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;

use CleatSquad\PhpUnitTestGenerator\Exception\ClassNotCoveredByTestException;
use CleatSquad\PhpUnitTestGenerator\Exception\ClassWithoutMethodsToTestException;
use Magento\Framework\Code\Generator\EntityAbstract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionParameter;

/**
 * Class UnitTestGenerator
 * @package CleatSquad\PhpUnitTestGenerator\Model
 */
class UnitTestGenerator extends EntityAbstract
{
    /**
     * @var array|null
     */
    private ?array $constructorArguments = null;

    /**
     * @var array
     */
    protected array $uses = [];

    /**
     * Validate data
     *
     * @return bool
     * @throws ClassNotCoveredByTestException
     * @throws ReflectionException
     */
    protected function _validateData(): bool
    {
        $classInfos = new \ReflectionClass($this->getSourceClassName());
        if ($classInfos->getDocComment() && str_contains($classInfos->getDocComment(), '@codeCoverageIgnore')) {
            throw new ClassNotCoveredByTestException(__('Class %1 is ignored from coverage', $this->getSourceClassName()));
        }

        return parent::_validateData();
    }

    /**
     * Generate code
     *
     * @return string
     */
    protected function _generateCode(): string
    {
        $this->addClassExtends();
        return str_replace(" = null;", ";", parent::_generateCode());
    }

    /**
     * Get default constructor definition for generated class
     *
     * @return array
     */
    protected function _getDefaultConstructorDefinition(): array
    {
        $beforeParams = $constructorParams = '';
        if (count($this->getConstructorArgumentsClass())) {
            $beforeParams = "\t" . \implode(
                    "\n",
                    \array_map(
                        function (ReflectionParameter $argument) {
                            $type = $argument->getType();
                            $alias = $this->uses[$type->getName()];
                            $factoryInstance = $createFactory = '';
                            if (\preg_match('/\w+Factory$/', $argument->getName()) === 1) {
                                //todo test is buiultin
                                $class = \substr($type->getName(), 0, -7);
                                $argumentName = \substr($argument->getName(), 0, -7) . 'Instance';
                                try {
                                    $this->addUse($class);
                                    $aliasInstance = $this->uses[$class];
                                    $factoryInstance =
                                        "$" .
                                        $argumentName .
                                        'Mock = $this->getMockBuilder(' .
                                        $aliasInstance .
                                        '::class)' .
                                        "\n\t->disableOriginalConstructor()" .
                                        "\n\t->getMock();\n";

                                    $createFactory = "\n" . '$this->' .
                                        $argument->getName() .'Mock' .
                                        "\n\t" . '->expects($this->atMost(1))' .
                                        "\n\t->method('create')".
                                        "\n\t->willReturn($" .
                                        $argumentName
                                        ."Mock);";
                                } catch (\Exception $e) {
                                    //do nothing
                                }
                            }

                            return "\n" . $factoryInstance . '$this->' .
                                $argument->getName() .
                                'Mock = $this->getMockBuilder(' .
                                $alias .
                                '::class)' .
                                "\n\t->disableOriginalConstructor()" .
                                "\n\t->getMock();" . $createFactory;
                        },
                        $this->getConstructorArgumentsClass()
                    )
                ) . "\n\n";
        }
        if (count($this->getConstructorArgumentsClass())) {
            $constructorParams = "\n\t" . \implode(
                    ',' . "\n\t",
                    \array_map(
                        function (ReflectionParameter $argument) {
                            return '$this->' . $argument->getName() . 'Mock';
                        },
                        $this->getConstructorArgumentsClass()
                    )
                ) . "\n";
        }
        return [
            'name' => 'setUp',
            'parameters' => [],
            'body' =>
                sprintf(
                    '%s$this->%s = new %s(%s);',
                    $beforeParams,
                    lcfirst($this->getSourceClassNameWithoutNamespace()),
                    $this->getSourceClassNameWithoutNamespace(),
                    $constructorParams
                ),
            'docblock' => [
                'tags' => [
                    ['name' => 'return', 'description' => 'void']
                ]
            ],
            'returnType' => 'void'
        ];
    }

    /**
     * add extends from testcase class
     */
    private function addClassExtends()
    {
        $this->addUse(
            TestCase::class
        );
        $this->_classGenerator->setExtendedClass(TestCase::class);
    }

    /**
     * Add a class to "use" classes
     *
     * @param string $use
     * @param string|null $useAlias
     */
    private function addUse(string $use, string $useAlias = null)
    {
        if (isset($this->uses[$use])) {
            return;
        }
        $useValue = $useAlias ? $useAlias : $use;
        $useKeys = explode('\\', $useValue);
        $useKey = end($useKeys);

        if (in_array($useKey, $this->uses)) {
            if (count($useKeys) > 1) {
                $useAlias = $useKeys[count($useKeys) - 2] . $useKey;
                $this->addUse($use, $useAlias);
            } else {
                $existedUse = \array_unique(\array_flip($this->uses))[$useKey];
                $this->_classGenerator->removeUse($existedUse);
                unset($this->uses[$existedUse]);
                $this->addUse($use, $useAlias);
                $this->addUse($existedUse, $useAlias);
            }
        } else {
            $this->uses[$use] = $useKey;
            $this->_classGenerator->addUse($use, $useAlias);
        }
    }

    /**
     * Returns list of properties for class generator
     *
     * @return array
     */
    protected function _getClassProperties(): array
    {
        $properties = [];
        if (count($this->getConstructorArgumentsClass())) {
            $this->addUse(
                MockObject::class
            );
            $properties = \array_map(
                function(ReflectionParameter $argument) {
                    $type = $argument->getType();
                    $this->addUse($type->getName());
                    $alias = $this->uses[$type->getName()];
                    return [
                        'name' => $argument->getName() . 'Mock',
                        'visibility' => 'private',
                        'docblock' => [
                            'shortDescription' => "Mock {$alias}",
                            'tags' => [
                                [
                                    'name' => 'var',
                                    'description' => ucfirst($alias) . "|MockObject"
                                ]
                            ],
                        ]
                    ];
                },
                $this->getConstructorArgumentsClass()
            );
        }
        $this->addUse(ltrim($this->getSourceClassName(), '\\'));
        $properties[] = [
            'name' => lcfirst($this->getSourceClassNameWithoutNamespace()),
            'visibility' => 'private',
            'docblock' => [
                'shortDescription' => 'Class to test instance',
                'tags' => [
                    [
                        'name' => 'var',
                        'description' => $this->getSourceClassNameWithoutNamespace()
                    ]
                ]
            ],

        ];
        return $properties;
    }

    /**
     * Returns list of methods for class generator
     *
     * @return array
     * @throws ClassWithoutMethodsToTestException
     */
    protected function _getClassMethods(): array
    {
        if (!count($this->getTestMethods())) {
            throw new ClassWithoutMethodsToTestException(__('Class %1 does not contain any tested methods.', $this->getSourceClassName()));
        }
        return array_merge(
            [$this->_getDefaultConstructorDefinition()],
            $this->getTestMethods()
        );
    }

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
                            \implode(
                                "\n",
                                \array_map(
                                    function (ReflectionParameter $argument) {
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
                                            return '$' . $argument->getName() . 'Mock = null;';
                                        };
                                    },
                                    $method->getParameters()
                                )
                            ) . "\n" .
                            '$this->' . lcfirst($this->getSourceClassNameWithoutNamespace()) . '->' .
                            $method->getName() .
                            '(' .
                            \implode(
                                ', ',
                                \array_map(
                                    function (ReflectionParameter $argument) {
                                        return '$' . $argument->getName() . 'Mock';
                                    },
                                    $method->getParameters()
                                )
                            ) .
                            ');',
                        'docblock' => [
                            'tags' => [
                                ['name' => 'return', 'description' => 'void']
                            ]
                        ],
                        'returnType' => 'void'
                    ];
                }
            }

        } catch (ReflectionException $e) {
            return $methods;
        }

        return $methods;
    }

    /**
     * @return array
     */
    private function getConstructorArgumentsClass(): array
    {
       if ($this->constructorArguments === null) {
            try {
                $constructor = new \ReflectionMethod($this->getSourceClassName(), '__construct');
                /** @var ReflectionParameter  $constructorArgument */
                $this->constructorArguments = array_filter(
                    $constructor->getParameters(),
                    function(ReflectionParameter $argument) {
                        return !$argument->isOptional() && $argument->getType() && !$argument->getType()->isBuiltin();
                    }
                );
            } catch (ReflectionException $e) {
                $this->constructorArguments = [];
            }
        }

        return $this->constructorArguments;
    }

    /**
     * Get class DocBlock
     *
     * @return array
     */
    protected function _getClassDocBlock(): array
    {
        $description = '@covers ' . $this->getSourceClassName();
        return ['shortDescription' => $description];
    }
}
