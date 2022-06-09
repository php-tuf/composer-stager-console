<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Domain\Core\Beginner\BeginnerInterface;
use PhpTuf\ComposerStager\Domain\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Application;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class BeginCommand extends AbstractCommand
{
    private const NAME = 'begin';

    /** @var \PhpTuf\ComposerStager\Domain\Core\Beginner\BeginnerInterface */
    private $beginner;

    /** @var \PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactory */
    private $pathFactory;

    public function __construct(BeginnerInterface $beginner, PathFactoryInterface $pathFactory)
    {
        parent::__construct(self::NAME);

        $this->beginner = $beginner;
        $this->pathFactory = $pathFactory;
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        assert(is_string($activeDir));
        $activeDir = $this->pathFactory::create($activeDir);

        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);
        assert(is_string($stagingDir));
        $stagingDir = $this->pathFactory::create($stagingDir);

        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {
            $this->beginner->begin(
                $activeDir,
                $stagingDir,
                null,
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
