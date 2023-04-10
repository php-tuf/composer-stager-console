<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use PhpTuf\ComposerStager\Domain\Value\Path\PathInterface;
use PhpTuf\ComposerStager\Infrastructure\Factory\Path\PathFactoryInterface;
use PhpTuf\ComposerStagerConsole\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
abstract class AbstractCommand extends Command
{
    // sysexits-compatible exit codes.
    // See https://tldp.org/LDP/abs/html/exitcodes.html
    // @todo As of Symfony 5.2 these are defined in \Symfony\Component\Console\Command\Command.
    //   Remove them once we drop support for Symfony 4.x.
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    private PathInterface $activeDir;

    private PathInterface $stagingDir;

    public function __construct(string $name, protected PathFactoryInterface $pathFactory)
    {
        parent::__construct($name);
    }

    public function getStagingDir(): PathInterface
    {
        return $this->stagingDir;
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        assert(is_string($activeDir));
        $this->activeDir = $this->pathFactory::create($activeDir);

        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);
        assert(is_string($stagingDir));
        $this->stagingDir = $this->pathFactory::create($stagingDir);
    }

    protected function getActiveDir(): PathInterface
    {
        return $this->activeDir;
    }
}
