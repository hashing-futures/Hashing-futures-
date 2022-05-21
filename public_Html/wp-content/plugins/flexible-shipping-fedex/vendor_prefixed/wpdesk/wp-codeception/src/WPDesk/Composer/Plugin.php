<?php

namespace FedExVendor\WPDesk\Composer\Codeception;

use FedExVendor\Composer\Composer;
use FedExVendor\Composer\IO\IOInterface;
use FedExVendor\Composer\Plugin\Capable;
use FedExVendor\Composer\Plugin\PluginInterface;
/**
 * Composer plugin.
 *
 * @package WPDesk\Composer\Codeception
 */
class Plugin implements \FedExVendor\Composer\Plugin\PluginInterface, \FedExVendor\Composer\Plugin\Capable
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;
    public function activate(\FedExVendor\Composer\Composer $composer, \FedExVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function deactivate(\FedExVendor\Composer\Composer $composer, \FedExVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    /**
     * @inheritDoc
     */
    public function uninstall(\FedExVendor\Composer\Composer $composer, \FedExVendor\Composer\IO\IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
    public function getCapabilities()
    {
        return [\FedExVendor\Composer\Plugin\Capability\CommandProvider::class => \FedExVendor\WPDesk\Composer\Codeception\CommandProvider::class];
    }
}
