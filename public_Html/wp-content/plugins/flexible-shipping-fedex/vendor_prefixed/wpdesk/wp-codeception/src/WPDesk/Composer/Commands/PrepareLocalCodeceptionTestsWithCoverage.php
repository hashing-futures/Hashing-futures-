<?php

namespace FedExVendor\WPDesk\Composer\Codeception\Commands;

use FedExVendor\Composer\Downloader\FilesystemException;
use FedExVendor\Symfony\Component\Console\Input\InputArgument;
use FedExVendor\Symfony\Component\Console\Input\InputInterface;
use FedExVendor\Symfony\Component\Console\Output\OutputInterface;
use FedExVendor\Symfony\Component\Yaml\Exception\ParseException;
use FedExVendor\Symfony\Component\Yaml\Yaml;
/**
 * Codeception tests run command.
 *
 * @package WPDesk\Composer\Codeception\Commands
 */
class PrepareLocalCodeceptionTestsWithCoverage extends \FedExVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests
{
    use LocalCodeceptionTrait;
    /**
     * Configure command.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('prepare-local-codeception-tests-with-coverage')->setDescription('Prepare local codeception tests.');
    }
    /**
     * Execute command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(\FedExVendor\Symfony\Component\Console\Input\InputInterface $input, \FedExVendor\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->prepareLocalCodeceptionTests($input, $output, \true);
        $configuration = $this->getWpDeskConfiguration();
        $plugin_file = $configuration->getApacheDocumentRoot() . '/wp-content/plugins/' . $configuration->getPluginFile();
        \file_put_contents($plugin_file, "\ndefine('C3_CODECOVERAGE_ERROR_LOG_FILE', '/tmp/c3_error.log'); include __DIR__ . '/c3.php';", \FILE_APPEND);
    }
}
