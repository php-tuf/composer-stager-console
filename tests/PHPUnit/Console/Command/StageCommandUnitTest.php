<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand;
use PhpTuf\ComposerStagerConsole\Console\Command\StageCommand;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\CommandTestCase;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Translation\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStagerConsole\Console\Command\StageCommand
 *
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\StageCommand::__construct
 *
 * @uses \PhpTuf\ComposerStagerConsole\Console\Application
 * @uses \PhpTuf\ComposerStagerConsole\Console\Command\StageCommand
 * @uses \PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback
 */
final class StageCommandUnitTest extends CommandTestCase
{
    private PathFactoryInterface|ObjectProphecy $pathFactory;
    private StagerInterface|ObjectProphecy $stager;

    protected function setUp(): void
    {
        $this->pathFactory = $this->prophesize(PathFactoryInterface::class);
        $this->stager = $this->prophesize(StagerInterface::class);

        parent::setUp();
    }

    protected function createSut(): Command
    {
        $pathFactory = $this->container()->get(PathFactory::class);
        $stager = $this->stager->reveal();

        return new StageCommand($pathFactory, $stager);
    }

    /** @covers ::configure */
    public function testBasicConfiguration(): void
    {
        $command = $this->createSut();

        $definition = $command->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();
        $composerCommandArgument = $definition->getArgument('composer-command');

        self::assertSame('stage', $command->getName(), 'Set correct name.');
        self::assertSame([], $command->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($command->getDescription(), 'Set a description.');
        self::assertSame(['composer-command'], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
        self::assertNotEmpty($command->getUsages(), 'Set usages.');
        self::assertNotEmpty($command->getHelp(), 'Set help.');

        self::assertTrue($composerCommandArgument->isRequired(), 'Required Composer command option.');
        self::assertTrue($composerCommandArgument->isArray(), 'Set Composer command to array.');
        self::assertNotEmpty($composerCommandArgument->getDescription(), 'Description provided.');
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerBasicExecution
     */
    public function testBasicExecution($composerCommand, $activeDir, $stagingDir): void
    {
        $activeDirPath = $this->path($activeDir);
        $stagingDirPath = $this->path($stagingDir);
        $this->stager
            ->stage($composerCommand, $activeDirPath, $stagingDirPath, Argument::any())
            ->shouldBeCalledOnce();

        $this->executeCommand([
            'composer-command' => $composerCommand,
            sprintf('--%s', Application::ACTIVE_DIR_OPTION) => $activeDir,
            sprintf('--%s', Application::STAGING_DIR_OPTION) => $stagingDir,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerBasicExecution(): array
    {
        return [
            [
                'composerCommand' => [self::INERT_COMMAND],
                'activeDir' => 'one/two',
                'stagingDir' => 'three/four',
            ],
            [
                'composerCommand' => [
                    'update',
                    '--with-all-dependencies',
                ],
                'activeDir' => 'five/six',
                'stagingDir' => 'siven/eight',
            ],
        ];
    }

    /** @covers ::execute */
    public function testCommandFailure(): void
    {
        $message = 'Dolor';
        $exception = new InvalidArgumentException(new TestTranslatableMessage('Dolor'));

        $this->stager
            ->stage(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand(['composer-command' => [self::INERT_COMMAND]]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }
}
