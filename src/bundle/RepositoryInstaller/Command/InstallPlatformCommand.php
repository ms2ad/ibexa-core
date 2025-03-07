<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\RepositoryInstaller\Command;

use Doctrine\DBAL\Connection;
use Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider;
use Ibexa\Bundle\Core\Command\BackwardCompatibleCommand;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class InstallPlatformCommand extends Command implements BackwardCompatibleCommand
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var \Symfony\Component\Console\Output\OutputInterface */
    private $output;

    /** @var \Psr\Cache\CacheItemPoolInterface */
    private $cachePool;

    /** @var string */
    private $environment;

    /** @var \Ibexa\Bundle\RepositoryInstaller\Installer\Installer[] */
    private $installers = [];

    /** @var \Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider */
    private $repositoryConfigurationProvider;

    public const EXIT_GENERAL_DATABASE_ERROR = 4;
    public const EXIT_PARAMETERS_NOT_FOUND = 5;
    public const EXIT_UNKNOWN_INSTALL_TYPE = 6;
    public const EXIT_MISSING_PERMISSIONS = 7;

    public function __construct(
        Connection $connection,
        array $installers,
        CacheItemPoolInterface $cachePool,
        string $environment,
        RepositoryConfigurationProvider $repositoryConfigurationProvider
    ) {
        $this->connection = $connection;
        $this->installers = $installers;
        $this->cachePool = $cachePool;
        $this->environment = $environment;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('ibexa:install');
        $this->setAliases($this->getDeprecatedAliases());
        $this->addArgument(
            'type',
            InputArgument::OPTIONAL,
            'The type of install. Available options: ' . implode(', ', array_keys($this->installers)),
            'ibexa-oss'
        );
        $this->addOption(
            'skip-indexing',
            null,
            InputOption::VALUE_NONE,
            'Skip indexing (ibexa:reindex)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->checkPermissions();
        $this->checkParameters();
        $this->checkCreateDatabase($output);

        $type = $input->getArgument('type');
        $siteaccess = $input->getOption('siteaccess');
        $installer = $this->getInstaller($type);
        if ($installer === false) {
            $output->writeln(
                "Unknown install type '$type', available options in currently installed Ibexa package: " .
                implode(', ', array_keys($this->installers))
            );
            exit(self::EXIT_UNKNOWN_INSTALL_TYPE);
        }

        $installer->setOutput($output);

        $installer->importSchema();
        $installer->importData();
        $installer->importBinaries();
        $this->cacheClear($output);

        if (!$input->getOption('skip-indexing')) {
            $this->indexData($output, $siteaccess);
        }

        return 0;
    }

    private function checkPermissions()
    {
        // @todo should take var-dir etc. from composer config or fallback to flex directory scheme
        if (!is_writable('public') && !is_writable('public/var')) {
            $this->output->writeln('[public/ | public/var] is not writable');
            exit(self::EXIT_MISSING_PERMISSIONS);
        }
    }

    private function checkParameters()
    {
        // @todo doesn't make sense to check for parameters.yml in sf4 and flex
        return;
        $parametersFile = 'app/config/parameters.yml';
        if (!is_file($parametersFile)) {
            $this->output->writeln("Required configuration file '$parametersFile' not found");
            exit(self::EXIT_PARAMETERS_NOT_FOUND);
        }
    }

    private function checkCreateDatabase(OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                'Creating database <comment>%s</comment> if it does not exist, using doctrine:database:create --if-not-exists',
                $this->connection->getDatabase()
            )
        );
        try {
            $bufferedOutput = new BufferedOutput();
            $connectionName = $this->repositoryConfigurationProvider->getStorageConnectionName();
            $command = sprintf('doctrine:database:create --if-not-exists --connection=%s', $connectionName);
            $this->executeCommand($bufferedOutput, $command);
            $output->writeln($bufferedOutput->fetch());
        } catch (\RuntimeException $exception) {
            $this->output->writeln(
                sprintf(
                    "<error>The configured database '%s' does not exist or cannot be created (%s).</error>",
                    $this->connection->getDatabase(),
                    $exception->getMessage()
                )
            );
            $this->output->writeln("Please check the database configuration in 'app/config/parameters.yml'");
            exit(self::EXIT_GENERAL_DATABASE_ERROR);
        }
    }

    /**
     * Clear all content related cache (persistence cache).
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function cacheClear(OutputInterface $output)
    {
        $this->cachePool->clear();
    }

    /**
     * Calls indexing commands.
     *
     * @todo This should not be needed once/if the Installer starts using API in the future.
     *       So temporary measure until it is not raw SQL based for the data itself (as opposed to the schema).
     *       This is done after cache clearing to make sure no cached data from before sql import is used.
     *
     * IMPORTANT: This is done using a command because config has change, so container and all services are different.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string|null $siteaccess
     */
    private function indexData(OutputInterface $output, $siteaccess = null)
    {
        $output->writeln(
            sprintf('Search engine re-indexing, executing command ibexa:reindex')
        );

        $command = 'ibexa:reindex';
        if ($siteaccess) {
            $command .= sprintf(' --siteaccess=%s', $siteaccess);
        }

        $this->executeCommand($output, $command);
    }

    /**
     * @param $type
     *
     * @return \Ibexa\Bundle\RepositoryInstaller\Installer\Installer
     */
    private function getInstaller($type)
    {
        if (!isset($this->installers[$type])) {
            return false;
        }

        return $this->installers[$type];
    }

    /**
     * Executes a Symfony command in separate process.
     *
     * Typically useful when configuration has changed, or you are outside of Symfony context (Composer commands).
     *
     * Based on {@see \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler::executeCommand}.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $cmd Ibexa command to execute, like 'ezplatform:solr_create_index'
     *               Escape any user provided arguments, like: 'assets:install '.escapeshellarg($webDir)
     * @param int $timeout
     */
    private function executeCommand(OutputInterface $output, $cmd, $timeout = 300)
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find(false)) {
            throw new \RuntimeException('The php executable could not be found. Add it to your PATH environment variable and try again');
        }

        // We don't know which php arguments where used so we gather some to be on the safe side
        $arguments = $phpFinder->findArguments();
        if (false !== ($ini = php_ini_loaded_file())) {
            $arguments[] = '--php-ini=' . $ini;
        }

        // Pass memory_limit in case this was specified as php argument, if not it will most likely be same as $ini.
        if ($memoryLimit = ini_get('memory_limit')) {
            $arguments[] = '-d memory_limit=' . $memoryLimit;
        }

        $phpArgs = implode(' ', array_map('escapeshellarg', $arguments));
        $php = escapeshellarg($phpPath) . ($phpArgs ? ' ' . $phpArgs : '');

        // Make sure to pass along relevant global Symfony options to console command
        $console = escapeshellarg('bin/console');
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $console .= ' -' . str_repeat('v', $output->getVerbosity() - 1);
        }

        if ($output->isDecorated()) {
            $console .= ' --ansi';
        }

        $console .= ' --env=' . escapeshellarg($this->environment);

        $process = Process::fromShellCommandline(
            implode(' ', [$php, $console, $cmd]),
            null,
            null,
            null,
            $timeout
        );

        $process->run(static function ($type, $buffer) use ($output) { $output->write($buffer, false); });
        if (!$process->getExitCode() === 1) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }

    public function getDeprecatedAliases(): array
    {
        return ['ezplatform:install'];
    }
}

class_alias(InstallPlatformCommand::class, 'EzSystems\PlatformInstallerBundle\Command\InstallPlatformCommand');
