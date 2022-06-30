<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\Domain\Core\Committer\CommitterInterface;
use PhpTuf\ComposerStager\Domain\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\Domain\Exception\RuntimeException;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory;
use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand;
use PhpTuf\ComposerStagerConsole\Console\Command\CommitCommand;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStagerConsole\Console\Command\CommitCommand
 *
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\CommitCommand::__construct
 *
 * @uses \PhpTuf\ComposerStagerConsole\Console\Application
 * @uses \PhpTuf\ComposerStagerConsole\Console\Command\CommitCommand
 * @uses \PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback
 *
 * @property \PhpTuf\ComposerStager\Domain\Core\Committer\CommitterInterface|\Prophecy\Prophecy\ObjectProphecy committer
 */
final class CommitCommandUnitTest extends CommandTestCase
{
    protected function setUp(): void
    {
        $this->committer = $this->prophesize(CommitterInterface::class);
        $this->committer
            ->commit(Argument::cetera());

        parent::setUp();
    }

    protected function createSut(): Command
    {
        $committer = $this->committer->reveal();
        $pathFactory = new PathFactory();

        return new CommitCommand($committer, $pathFactory);
    }

    /** @covers ::configure */
    public function testBasicConfiguration(): void
    {
        $sut = $this->createSut();

        $definition = $sut->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('commit', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     *
     * @dataProvider providerBasicExecution
     */
    public function testBasicExecution($activeDir, $stagingDir): void
    {
        $activeDirPath = PathFactory::create($activeDir);
        $stagingDirPath = PathFactory::create($stagingDir);
        $this->committer
            ->commit($stagingDirPath, $activeDirPath, null, Argument::type(ProcessOutputCallback::class))
            ->shouldBeCalledOnce();

        $this->executeCommand([
            '--' . Application::ACTIVE_DIR_OPTION => $activeDir,
            '--' . Application::STAGING_DIR_OPTION => $stagingDir,
            '--no-interaction' => true,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerBasicExecution(): array
    {
        return [
            [
                'activeDir' => '/one/two',
                'stagingDir' => '/three/four',
            ],
            [
                'activeDir' => '/five/six',
                'stagingDir' => '/seven/eight',
            ],
        ];
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     *
     * @dataProvider providerConfirmationPrompt
     */
    public function testConfirmationPrompt($input, $calls, $exit): void
    {
        $this->committer
            ->commit(Argument::cetera())
            ->shouldBeCalledTimes($calls);

        $this->executeCommand([], [$input]);

        self::assertStringContainsString('Continue?', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame($exit, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerConfirmationPrompt(): array
    {
        return [
            [
                'input' => 'yes',
                'calls' => 1,
                'exit' => AbstractCommand::SUCCESS,
            ],
            [
                'input' => 'no',
                'calls' => 0,
                'exit' => AbstractCommand::FAILURE,
            ],
        ];
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerCommandFailure
     */
    public function testCommandFailure($exception, $message): void
    {
        $this->committer
            ->commit(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['--no-interaction' => true]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new InvalidArgumentException('Ipsum'), 'message' => 'Ipsum'],
            ['exception' => new RuntimeException('Dolor'), 'message' => 'Dolor'],
        ];
    }
}
