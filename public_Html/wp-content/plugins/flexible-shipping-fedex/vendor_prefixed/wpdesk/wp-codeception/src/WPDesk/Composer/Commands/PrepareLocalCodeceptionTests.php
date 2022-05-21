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
class PrepareLocalCodeceptionTests extends \FedExVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests
{
    use LocalCodeceptionTrait;
    /**
     * Configure command.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('prepare-local-codeception-tests')->setDescription('Prepare local codeception tests.');
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
        $this->prepareLocalCodeceptionTests($input, $output, \false);
    }
    /**
     * @param array $theme_files
     * @param $theme_folder
     *
     * @throws FilesystemException
     */
    private function copyThemeFiles(array $theme_files, $theme_folder)
    {
        foreach ($theme_files as $theme_file) {
            if (!\copy($theme_file, $this->trailingslashit($theme_folder) . \basename($theme_file))) {
                throw new \FedExVendor\Composer\Downloader\FilesystemException('Error copying theme file: ' . $theme_file);
            }
        }
    }
}
