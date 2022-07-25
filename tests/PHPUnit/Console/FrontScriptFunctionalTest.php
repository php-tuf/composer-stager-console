<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Tests\PHPUnit\Console;

use PhpTuf\ComposerStagerConsole\Tests\PHPUnit\TestCase;

/**
 * @coversNothing This actually covers the front script, obviously, but PHPUnit
 *   currently has no way to indicate coverage of a file as opposed to a class.
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/3794
 */
final class FrontScriptFunctionalTest extends TestCase
{
    /** @covers \PhpTuf\ComposerStagerConsole\Console\Application::__construct */
    public function testBasicExecution(): void
    {
        $process = self::runFrontScript(['--version']);
        $output = $process->getOutput();

        self::assertSame('Composer Stager' . PHP_EOL, $output);
    }

    public function testCommandList(): void
    {
        $process = self::runFrontScript(['--format=json', 'list']);
        $output = $process->getOutput();

        $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $commands = array_map(static fn ($value) => $value['name'], $data['commands']);

        self::assertSame([
            'begin',
            'clean',
            'commit',
            'help',
            'list',
            'stage',
        ], $commands);
    }
}
