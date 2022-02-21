<?php

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console;

use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @coversDefaultClass \PhpTuf\ComposerStagerConsole\Console\Application
 * @covers \PhpTuf\ComposerStagerConsole\Console\Application::__construct
 * @covers \PhpTuf\ComposerStagerConsole\Console\Application::getDefaultInputDefinition
 * @uses \PhpTuf\ComposerStagerConsole\Console\Application
 */
class ApplicationUnitTest extends TestCase
{
    private const TEST_COMMAND = ['command' => 'test'];

    protected function createSut(): Application
    {
        $createdCommand = new class() extends Command {
            protected static $defaultName = 'test';

            protected function execute(
                InputInterface $input,
                OutputInterface $output
            ): int {
                return AbstractCommand::SUCCESS;
            }
        };

        $application = new Application();
        $application->setAutoExit(false);
        $application->add($createdCommand);
        return $application;
    }

    /**
     * @uses \PhpTuf\ComposerStagerConsole\Console\Application::getDefaultInputDefinition
     */
    public function testDefaultOptions(): void
    {
        $baseOptions = (new \Symfony\Component\Console\Application())
            ->getDefinition()
            ->getOptionDefaults();
        $sutOptions = $this->createSut()
            ->getDefinition()
            ->getOptionDefaults();

        $addedOptions = array_diff_key($sutOptions, $baseOptions);

        self::assertSame([
            Application::ACTIVE_DIR_OPTION,
            Application::STAGING_DIR_OPTION,
        ], array_keys($addedOptions), 'Set correct options');
    }

    /**
     * @covers ::getDefaultInputDefinition
     *
     * @dataProvider providerGlobalOptionDefinitions
     */
    public function testGlobalOptionDefinitions(
        $name,
        $descriptionContains,
        $shortcut,
        $default
    ): void {
        $application = $this->createSut();
        $input = $application->getDefinition();
        $option = $input->getOption($name);

        self::assertSame($name, $option->getName(), 'Set correct name.');
        self::assertSame($shortcut, $option->getShortcut(), 'Set correct shortcut.');
        self::assertSame($default, $option->getDefault(), 'Set correct default.');
        self::assertStringContainsString($descriptionContains, $option->getDescription(), 'Set correct description.');
    }

    public function providerGlobalOptionDefinitions(): array
    {
        return [
            [
                'name' => Application::ACTIVE_DIR_OPTION,
                'descriptionContains' => 'active',
                'shortcut' => 'd',
                'default' => Application::ACTIVE_DIR_DEFAULT,
            ],
            [
                'name' => Application::STAGING_DIR_OPTION,
                'descriptionContains' => 'staging',
                'shortcut' => 's',
                'default' => Application::STAGING_DIR_DEFAULT,
            ],
        ];
    }

    /**
     * @covers ::configureIO
     */
    public function testGlobalOutputStyleOverrides(): void
    {
        $application = $this->createSut();
        $applicationTester = new ApplicationTester($application);

        $applicationTester->run(static::TEST_COMMAND);

        $expectedStyle = new OutputFormatterStyle('red');
        $actualStyle = $applicationTester->getOutput()
            ->getFormatter()
            ->getStyle('error');

        self::assertEquals($expectedStyle, $actualStyle, 'Overrode error style.');
    }
}
