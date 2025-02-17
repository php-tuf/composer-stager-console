<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console\Command;

use FilesystemIterator;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;
use PhpTuf\ComposerStagerConsole\Console\Application;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    private PathListInterface $exclusions;

    public function __construct(
        string $name,
        protected PathFactoryInterface $pathFactory,
        private ?PathListFactoryInterface $pathListFactory = null,
    ) {
        parent::__construct($name);
    }

    public function getStagingDir(): PathInterface
    {
        return $this->stagingDir;
    }

    /**
     * Get a list of all files and directories that are not in the given list, starting from the current directory
     * and descending into subdirectories recursively if a directory in the given list has multiple levels.
     *
     * @param string $currentDir The starting directory path.
     * @param array $includedPaths List of directories or files to include (relative paths).
     *
     * @return array The list of excluded files and directories.
     */
    function getExcludedPaths(string $currentDir, array $includedPaths): array
    {
        // Normalize the included paths (remove trailing slashes for directories).
        $normalizedIncludedPaths = array_map(static fn ($path) => rtrim($path, '/'), $includedPaths);

        // Store excluded paths.
        $excludedPaths = [];

        // Recursively iterate over all files and directories.
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($currentDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            // Get the relative path for the current file or directory.
            $relativePath = str_replace($currentDir . DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Normalize the relative path.
            $normalizedPath = rtrim($relativePath, '/');

            // Check if the current path or its parent is included.
            $isExcluded = true;

            foreach ($normalizedIncludedPaths as $includedPath) {
                // Allow exact matches or paths that are children of an included directory.
                if ($normalizedPath === $includedPath || // Exact match
                    strpos($normalizedPath, $includedPath . '/') === 0 || // Is a child of the included path
                    strpos($includedPath, $normalizedPath . '/') === 0 // Is a parent of the included path
                ) {
                    $isExcluded = false;

                    break;
                }
            }

            // check if the current paths parent is already excluded
            foreach ($excludedPaths as $excludedPath) {
                // Allow exact matches or paths that are children of an included directory.
                if (strpos($normalizedPath, $excludedPath . '/') === 0 // Is a child of the excluded path
                ) {
                    $isExcluded = false;

                    break;
                }
            }

            // If it's excluded, add it to the list.
            if (!$isExcluded) {
                continue;
            }

            $excludedPaths[] = $relativePath;
        }

        return $excludedPaths;
    }

    /** @throws \Symfony\Component\Console\Exception\InvalidArgumentException */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $activeDir = $input->getOption(Application::ACTIVE_DIR_OPTION);
        assert(is_string($activeDir));
        $this->activeDir = $this->pathFactory->create($activeDir);

        $stagingDir = $input->getOption(Application::STAGING_DIR_OPTION);
        assert(is_string($stagingDir));
        $this->stagingDir = $this->pathFactory->create($stagingDir);

        if (!$this->pathListFactory) {
            return;
        }

        $exclusions = [];
        $includeDirs = $input->getOption(Application::INCLUDE_DIR_OPTION);

        if (! empty($includeDirs)) {
            // Filter the list to exclude the entries not in the included array
            $exclusions = $this->getExcludedPaths($this->activeDir->absolute(), $includeDirs);
        }

        $excludeDirs = $input->getOption(Application::EXCLUDE_DIR_OPTION);

        $this->exclusions = $this->pathListFactory->create(...$exclusions, ...$excludeDirs);
    }

    protected function getActiveDir(): PathInterface
    {
        return $this->activeDir;
    }

    protected function getExclusions(): PathListInterface
    {
        return $this->exclusions;
    }
}
