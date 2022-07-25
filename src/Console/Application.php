<?php declare(strict_types=1);

namespace PhpTuf\ComposerStagerConsole\Console;

use Symfony\Component\Console\Application as DefaultApplication;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends DefaultApplication
{
    public const ACTIVE_DIR_OPTION = 'active-dir';
    public const STAGING_DIR_OPTION = 'staging-dir';

    public const ACTIVE_DIR_DEFAULT = '.';
    public const STAGING_DIR_DEFAULT = '.composer_staging';

    private const NAME = 'Composer Stager';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    /** @throws \Symfony\Component\Console\Exception\LogicException */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        try {
            $inputDefinition->addOption(
                new InputOption(
                    self::ACTIVE_DIR_OPTION,
                    'd',
                    InputOption::VALUE_REQUIRED,
                    'Use the given directory as active directory',
                    self::ACTIVE_DIR_DEFAULT,
                ),
            );
            $inputDefinition->addOption(
                new InputOption(
                    self::STAGING_DIR_OPTION,
                    's',
                    InputOption::VALUE_REQUIRED,
                    'Use the given directory as staging directory',
                    self::STAGING_DIR_DEFAULT,
                ),
            );
        } catch (InvalidArgumentException $e) {
            throw new LogicException($e->getMessage(), $e->getCode(), $e);
        }

        return $inputDefinition;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $output->getFormatter()->setStyle(
            'error',
            // Red foreground, no background.
            new OutputFormatterStyle('red'),
        );
    }
}
