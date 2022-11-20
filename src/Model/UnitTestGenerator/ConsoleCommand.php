<?php

namespace CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;

use CleatSquad\PhpUnitTestGenerator\Model\UnitTestGenerator;
use ReflectionException;
use Symfony\Component\Console\Tester\CommandTester;

class ConsoleCommand extends UnitTestGenerator
{
    /**
     * @param array $methods
     * @return array
     */
    protected function getTestMethods(array $methods = []): array
    {
        try {
            $sourceReflectionClass = new \ReflectionClass($this->getSourceClassName());
            $protectedMethods = $sourceReflectionClass->getMethods(\ReflectionMethod::IS_PROTECTED);
            foreach ($protectedMethods as $method) {
                if ($method->getDocComment() && str_contains($method->getDocComment(), '@codeCoverageIgnore')) {
                    continue;
                }
                if ($method->getName() != 'execute') {
                    continue;
                }
                $testMethodName = 'test' . \ucfirst($method->getName());
                $this->addUse(CommandTester::class);
                $methods[] = [
                    'name' => $testMethodName,
                    'parameters' => [],
                    'body' =>
                        '$commandTester = new CommandTester($this->' . lcfirst($this->getSourceClassNameWithoutNamespace()) . ');' . "\n" .
                        '$commandTester->' . $method->getName() . '([]);',
                    'docblock' => [
                        'tags' => [
                            ['name' => 'return', 'description' => 'void']
                        ]
                    ],
                    'returnType' => 'void'
                ];
            }
        } catch (ReflectionException $e) {
            return parent::getTestMethods($methods);
        }
        return parent::getTestMethods($methods);
    }
}
