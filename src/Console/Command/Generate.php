<?php
/**
 * @category    CleatSquad
 * @package     CleatSquad_PhpUnitTestGenerator
 * @copyright   Copyright (c) 2022 CleatSquad, Inc. (https://www.cleatsquad.com)
 */
declare(strict_types=1);

namespace CleatSquad\PhpUnitTestGenerator\Console\Command;

use CleatSquad\PhpUnitTestGenerator\Model\GeneratorInterface;
use CleatSquad\PhpUnitTestGenerator\Exception\ClassIgnoredFromCoverageException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Generate
 * @package CleatSquad\PhpUnitTestGenerator\Console\Command
 */
class Generate extends \Symfony\Component\Console\Command\Command
{
    const ARGUMENT_PATH = 'filepath';
    const CONSOLE_COMMAND_NAME = 'dev:unit:test-generate';

    private GeneratorInterface $generator;

    /**
     * @param GeneratorInterface $generator
     */
    public function __construct(
        GeneratorInterface $generator,
        string $name = null
    ) {
        $this->generator = $generator;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::CONSOLE_COMMAND_NAME)
            ->setDescription('Generate unit test structure for defined class');

        $this->addArgument(
            self::ARGUMENT_PATH,
            InputArgument::REQUIRED,
            'Class name or path to file to generate unit tests for'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $path = $input->getArgument(static::ARGUMENT_PATH);
            $results = $this->generator->generate($path);
            if (count($results)) {
                $output->writeln('<info>The php unit test is generated successfully, for the following classes:</info>');
                foreach ($results as $result) {
                    $output->writeln("<info>$result</info>");
                }
            } else {
                $output->writeln('<info>There is no generated php unit test.</info>');
            }
            if ($this->generator->getErrors()) {
                foreach ($this->generator->getErrors() as $error) {
                    $output->writeln('<error>' . $error . '</error>');
                }
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }
}
