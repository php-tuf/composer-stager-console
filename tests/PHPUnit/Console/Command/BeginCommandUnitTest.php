<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\Command;

use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\RuntimeException;
use PhpTuf\ComposerStager\Internal\Path\Factory\PathFactory;
use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand;
use PhpTuf\ComposerStagerConsole\Console\Command\BeginCommand;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console\CommandTestCase;
use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Translation\TestTranslatableMessage;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @coversDefaultClass \PhpTuf\ComposerStagerConsole\Console\Command\BeginCommand
 *
 * @covers ::__construct
 * @covers \PhpTuf\ComposerStagerConsole\Console\Command\AbstractCommand
 *
 * @uses \PhpTuf\ComposerStagerConsole\Console\Application
 * @uses \PhpTuf\ComposerStagerConsole\Console\Command\BeginCommand
 * @uses \PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback
 */
final class BeginCommandUnitTest extends CommandTestCase
{
    private BeginnerInterface|ObjectProphecy $beginner;

    protected function setUp(): void
    {
        $this->beginner = $this->prophesize(BeginnerInterface::class);
        $this->beginner
            ->begin(Argument::cetera());

        parent::setUp();
    }

    protected function createSut(): Command
    {
        $beginner = $this->beginner->reveal();
        $pathFactory = new PathFactory();

        return new BeginCommand($beginner, $pathFactory);
    }

    /** @covers ::configure */
    public function testBasicConfiguration(): void
    {
        $sut = $this->createSut();

        $definition = $sut->getDefinition();
        $arguments = $definition->getArguments();
        $options = $definition->getOptions();

        self::assertSame('begin', $sut->getName(), 'Set correct name.');
        self::assertSame([], $sut->getAliases(), 'Set correct aliases.');
        self::assertNotEmpty($sut->getDescription(), 'Set a description.');
        self::assertSame([], array_keys($arguments), 'Set correct arguments.');
        self::assertSame([], array_keys($options), 'Set correct options.');
    }

    /** @covers ::execute */
    public function testBasicExecution(): void
    {
        $activeDir = 'one/two';
        $activeDirPath = $this->path('one/two');
        $stagingDir = 'three/four';
        $stagingDirPath = $this->path('three/four');
        $this->beginner
            ->begin($activeDirPath, $stagingDirPath, null, Argument::type(ProcessOutputCallback::class))
            ->shouldBeCalledOnce();

        $this->executeCommand([
            '--' . Application::ACTIVE_DIR_OPTION => $activeDir,
            '--' . Application::STAGING_DIR_OPTION => $stagingDir,
        ]);

        self::assertSame('', $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::SUCCESS, $this->getStatusCode(), 'Returned correct status code.');
    }

    /**
     * @covers ::execute
     *
     * @dataProvider providerCommandFailure
     */
    public function testCommandFailure($exception, $message): void
    {
        $this->beginner
            ->begin(Argument::cetera())
            ->willThrow($exception);

        $this->executeCommand([]);

        self::assertSame($message . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
        self::assertSame(AbstractCommand::FAILURE, $this->getStatusCode(), 'Returned correct status code.');
    }

    public function providerCommandFailure(): array
    {
        return [
            ['exception' => new InvalidArgumentException(new TestTranslatableMessage('Lorem')), 'message' => 'Lorem'],
            ['exception' => new RuntimeException(new TestTranslatableMessage('Ipsum')), 'message' => 'Ipsum'],
        ];
    }
}
