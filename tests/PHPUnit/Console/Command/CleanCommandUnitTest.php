<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\API\Core\CleanerInterface;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand;
use PhpTuf\ComposerStagerConsole\Console\Command\CleanCommand;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\CommandTestCase;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Translation\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStagerConsole\Console\Command\CleanCommand
 *
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\CleanCommand::__construct
 *
 * @uses \PhpTuf\ComposerStagerConsole\Console\Application
 * @uses \PhpTuf\ComposerStagerConsole\Console\Command\CleanCommand::configure
 * @uses \PhpTuf\ComposerStagerConsole\Console\Command\CleanCommand::confirm
 * @uses \PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback::__construct
 */
final class CleanCommandUnitTest extends CommandTestCase
{
    private CleanerInterface|ObjectProphecy $cleaner;

    protected function setUp(): void
    {
        $this->cleaner = $this->prophesize(CleanerInterface::class);
        $this->cleaner
            ->clean(Argument::cetera());

        parent::setUp();
    }

    protected function createSut(): Command
    {
        $cleaner = $this->cleaner->reveal();
        $pathFactory = new PathFactory();

        return new CleanCommand($cleaner, $pathFactory);
    }

    /** @covers ::configure */
    public function testBasicConfiguration(): void
    {
        $sut = $this->createSut();

        $definition = $sut->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('clean', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     */
    public function testBasicExecution(): void
    {
        $activeDir = $this->path(self::ACTIVE_DIR);
        $stagingDir = $this->path(self::STAGING_DIR);
        $this->cleaner
            ->clean($activeDir, $stagingDir, Argument::type(ProcessOutputCallback::class))
            ->shouldBeCalledOnce();

        $this->executeCommand([
            sprintf('--%s', Application::ACTIVE_DIR_OPTION) => self::ACTIVE_DIR,
            sprintf('--%s', Application::STAGING_DIR_OPTION) => self::STAGING_DIR,
            '--no-interaction' => true,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    /**
     * @covers ::confirm
     * @covers ::execute
     *
     * @dataProvider providerConfirmationPrompt
     */
    public function testConfirmationPrompt($input, $calls, $exit): void
    {
        $this->cleaner
            ->clean(Argument::cetera())
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

    /** @covers ::execute */
    public function testCommandFailure(): void
    {
        $message = 'Lorem';
        $exception = new RuntimeException(new TestTranslatableMessage($message));

        $this->cleaner
            ->clean(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['--no-interaction' => true]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }
}
