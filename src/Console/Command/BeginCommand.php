<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Output\ProcessOutputCallback;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class BeginCommand extends AbstractCommand
{
    private const NAME = 'begin';

    public function __construct(private readonly BeginnerInterface $beginner, PathFactoryInterface $pathFactory, PathListFactoryInterface $pathListFactory)
    {
        parent::__construct(self::NAME, $pathFactory, $pathListFactory);
    }

    protected function configure(): void
    {
        $this->setDescription('Begins the staging process by copying the active directory to the staging directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ---------------------------------------------------------------------
        // (!) Here is the substance of the example. Invoke the Composer Stager
        //     API; be sure to catch the appropriate exceptions.
        // ---------------------------------------------------------------------
        try {

            $this->beginner->begin(
                $this->getActiveDir(),
                $this->getStagingDir(),
	            $this->getExclusions(),
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
