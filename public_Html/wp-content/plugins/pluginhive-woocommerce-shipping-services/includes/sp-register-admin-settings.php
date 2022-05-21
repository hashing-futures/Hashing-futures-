<?php
if (!defined('ABSPATH'))  exit;

if (!class_exists('SP_Register_Admin_Settings')) {
  class SP_Register_Admin_Settings
  {
    public function __construct()
    {
      require_once 'settings/sp-create-account.php';
    }
  }

  new SP_Register_Admin_Settings();
}
