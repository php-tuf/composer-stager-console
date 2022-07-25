<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Domain\Core\Stager\StagerInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class StageCommand extends AbstractCommand
{
    private const NAME = 'stage';

    private StagerInterface $stager;

    public function __construct(PathFactoryInterface $pathFactory, StagerInterface $stager)
    {
        parent::__construct(self::NAME, $pathFactory);

        $this->stager = $stager;
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    protected function configure(): void
    {
        $this
            ->setDescription('Executes a Composer command in the staging directory')
            ->addArgument(
                'composer-command',
                // This argument uses array mode so that it's automatically parsed
                // and escaped by the Console component. This approach, though
                // safer and easier, requires the command string to be preceded
                // by a double-hyphen (" -- "). If it's not, the Composer command
                // parts will be misinterpreted as options to the console command.
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The Composer command to stage, without "composer". This MUST be preceded '
                    . 'by a double-hyphen (" -- ") to prevent confusion of command options. See "Usage"',
            )
            ->addUsage('[options] -- <composer-command>...')
            ->addUsage('-- update --with-all-dependencies')
            ->addUsage('-- require lorem/ipsum:"^1 || ^2"')
            ->addUsage('-- --help')
            ->setHelp('If you are getting unexpected behavior from command options, be sure you are '
                . 'preceding the "composer-command" argument with a double-hyphen (" -- "). See "Usage"')
        ;
    }

    /**
     * @return int
     *   The exit code.
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var array<string> $composerCommand */
            $composerCommand = $input->getArgument('composer-command');
        } catch (InvalidArgumentException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }

        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {
            $this->stager->stage(
                $composerCommand,
                $this->getActiveDir(),
                $this->getStagingDir(),
                new ProcessOutputCallback($input, $output),
            );

            return self::SUCCESS;
        } catch (ExceptionInterface $e) {
            // Error-handling specifics may differ for your application. This
            // example outputs errors to the terminal.
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }
    }
}
