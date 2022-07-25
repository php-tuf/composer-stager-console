<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console;

use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends TestCase
{
    protected const INERT_COMMAND = '--version';

    /** The command tester. */
    private ?CommandTester $commandTester = null;

    /**
     * Creates a command object to test.
     *
     * @return \Symfony\Component\Console\Command\Command A command object with
     *   mocked dependencies injected.
     */
    abstract protected function createSut(): Command;

    /**
     * Executes a given command with the command tester.
     *
     * @param array $args The command arguments.
     * @param array<string> $inputs An array of strings representing each input
     *   passed to the command input stream.
     */
    protected function executeCommand(array $args = [], array $inputs = []): void
    {
        $tester = $this->getCommandTester();
        $tester->setInputs($inputs);
        $commandName = $this->createSut()::getDefaultName();
        $args = array_merge(['command' => $commandName], $args);
        $tester->execute($args);
    }

    /**
     * Gets the command tester.
     *
     * @return \Symfony\Component\Console\Tester\CommandTester A command tester.
     */
    protected function getCommandTester(): CommandTester
    {
        if ($this->commandTester !== null) {
            return $this->commandTester;
        }

        $application = new Application();

        $createdCommand = $this->createSut();
        $application->add($createdCommand);
        $foundCommand = $application->find($createdCommand->getName());

        $this->commandTester = new CommandTester($foundCommand);

        return $this->commandTester;
    }

    /**
     * Gets the display returned by the last execution of the command.
     *
     * @return string The display.
     */
    protected function getDisplay(): string
    {
        return $this->getCommandTester()->getDisplay();
    }

    /**
     * Gets the status code returned by the last execution of the command.
     *
     * @return int The exit code.
     */
    protected function getStatusCode(): int
    {
        return $this->getCommandTester()->getStatusCode();
    }
}
