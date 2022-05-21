<?php

namespace FedExVendor\WPDesk\Composer\Codeception;

use FedExVendor\WPDesk\Composer\Codeception\Commands\CreateCodeceptionTests;
use FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareCodeceptionDb;
use FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTests;
use FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTestsWithCoverage;
use FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareParallelCodeceptionTests;
use FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareWordpressForCodeception;
use FedExVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests;
use FedExVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTests;
use FedExVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTestsWithCoverage;
/**
 * Links plugin commands handlers to composer.
 */
class CommandProvider implements \FedExVendor\Composer\Plugin\Capability\CommandProvider
{
    public function getCommands()
    {
        return [new \FedExVendor\WPDesk\Composer\Codeception\Commands\CreateCodeceptionTests(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\RunCodeceptionTests(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTests(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\RunLocalCodeceptionTestsWithCoverage(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareCodeceptionDb(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareWordpressForCodeception(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTests(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareLocalCodeceptionTestsWithCoverage(), new \FedExVendor\WPDesk\Composer\Codeception\Commands\PrepareParallelCodeceptionTests()];
    }
}
