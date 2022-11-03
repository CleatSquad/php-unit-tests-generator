<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Model;


/**
 * Class UnitTestGenerator
 * @package CleatSquad\PhpUnitTestGenerator\Model
 */
class UnitTestGenerator extends \Magento\Framework\Code\Generator\EntityAbstract
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
     * Generate code
     *
     * @return string
     */
    protected function _generateCode()
    {
        $this->addClassExtends();
        return str_replace(" = null;", ";", parent::_generateCode());
    }

    /**
     * Get default constructor definition for generated class
     *
     * @return array
     */
    protected function _getDefaultConstructorDefinition()
    {
        $beforepParams = $constructorParams = '';
        if (count($this->getConstructorArgumentsClass())) {
            $beforepParams = "\t" . \implode(
                    "\n",
                    \array_map(
                        function (\ReflectionParameter $argument) {
                            $type = $argument->getType();
                            $alias = $this->uses[$type->getName()];
                            $factoryInstance = $createFactory = '';
                            if (\preg_match('/\w+Factory$/', $argument->getName()) === 1) {
                                //todo test is buiultin
                                $class = \substr($type->getName(), 0, -7);
                                $argumentName = \substr($argument->getName(), 0, -7) . 'Instance';
                                try {
                                    $classInstance =  new \ReflectionClass($class);
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
                        function (\ReflectionParameter $argument) {
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
                    $beforepParams,
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
            \PHPUnit\Framework\TestCase::class
        );
        $this->_classGenerator->setExtendedClass(\PHPUnit\Framework\TestCase::class);
    }

    /**
     * Add a class to "use" classes
     *
     * @param  string $use
     * @param  string|null $useAlias
     */
    public function addUse($use, $useAlias = null)
    {
        $useValue = $useAlias ? $useAlias : $use;
        $useKeys = explode('\\', $useValue);
        $useKey = last($useKeys);

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
    protected function _getClassProperties()
    {
        $properties = [];
        if (count($this->getConstructorArgumentsClass())) {
            $this->addUse(
                \PHPUnit\Framework\MockObject\MockObject::class
            );
            $properties = \array_map(
                function(\ReflectionParameter $argument) {
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
     */
    protected function _getClassMethods()
    {
        return array_merge(
            [$this->_getDefaultConstructorDefinition()],
            $this->getTestMethods()
        );
    }

    /**
     * @return array
     */
    protected function getTestMethods(): array
    {
        $methods = [];
        try {
            $sourceReflectionClass = new \ReflectionClass($this->getSourceClassName());
            $publicMethods = $sourceReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($publicMethods as $method) {
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
                            '$this->' . lcfirst($this->getSourceClassNameWithoutNamespace()) . '->' .
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
        } catch (\ReflectionException $e) {
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
                /** @var \ReflectionParameter  $constructorArgument */
                $this->constructorArguments = array_filter(
                    $constructor->getParameters(),
                    function(\ReflectionParameter $argument) {
                        return !$argument->isOptional() && $argument->getType() && !$argument->getType()->isBuiltin();
                    }
                );
            } catch (\ReflectionException $e) {
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
    protected function _getClassDocBlock()
    {
        $description = '@covers ' . $this->getSourceClassName();
        return ['shortDescription' => $description];
    }
}
