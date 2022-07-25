<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit;

use PHPUnit\Framework\TestCase as DefaultTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

abstract class TestCase extends DefaultTestCase
{
    use ProphecyTrait;

    protected const TEST_ENV = __DIR__ . '/../../var/phpunit/test-env';
    protected const TEST_ENV_WORKING_DIR = self::TEST_ENV . '/working-dir';
    protected const ACTIVE_DIR = 'active-dir';
    protected const STAGING_DIR = 'staging-dir';
    protected const ORIGINAL_CONTENT = '';
    protected const CHANGED_CONTENT = 'changed';

    protected static function createTestEnvironment(string $activeDir): void
    {
        $filesystem = new Filesystem();

        // Create the test environment,
        $filesystem->mkdir(self::TEST_ENV_WORKING_DIR);
        chdir(self::TEST_ENV_WORKING_DIR);

        // Create the active directory only. The staging directory is created
        // when the "begin" command is exercised.
        $filesystem->mkdir($activeDir);
    }

    protected static function runFrontScript(array $args, string $cwd = __DIR__): Process
    {
        $command = array_merge([
            'bin' => 'php',
            'scriptPath' => realpath(__DIR__ . '/../../bin/composer-stage'),
        ], $args);
        $process = new Process($command, $cwd);
        $process->mustRun();

        return $process;
    }

    protected static function createFile(string $baseDir, string $filename): void
    {
        $filename = "{$baseDir}/{$filename}";
        $dirname = dirname($filename);

        if (!file_exists($dirname)) {
            self::assertTrue(mkdir($dirname, 0777, true), "Created directory {$dirname}.");
        }

        self::assertTrue(touch($filename), "Created file {$filename}.");
        self::assertNotFalse(realpath($filename), "Got absolute path of {$filename}.");
    }

    protected static function assertStagingDirectoryDoesNotExist(): void
    {
        self::assertFileDoesNotExist(self::STAGING_DIR, 'Staging directory does not exist.');
    }

    protected static function assertActiveAndStagingDirectoriesSame(): void
    {
        self::assertSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are the same.',
        );
    }

    protected static function assertActiveAndStagingDirectoriesNotSame(): void
    {
        self::assertNotSame(
            '',
            self::getActiveAndStagingDirectoriesDiff(),
            'Active and staging directories are not the same.',
        );
    }

    protected static function getActiveAndStagingDirectoriesDiff(): string
    {
        $process = new Process([
            'diff',
            '--recursive',
            self::ACTIVE_DIR,
            self::STAGING_DIR,
        ]);
        $process->run();

        return $process->getOutput();
    }

    protected static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR !== '/';
    }
}
