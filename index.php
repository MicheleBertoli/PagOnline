<?php

/*
Plugin Name: WPEC Unicredit PagOnline
Plugin URI: http://github.com/MicheleBertoli/PagOnline
Description: Italian Unicredit PagOnline Payment Gateway for WP e-Commerce (http://getshopped.org)
Version: 1.0
Author: Michele Bertoli @ Gummy Industries
Author URI: http://gummyindustries.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!class_exists('pagonline_loader'))
{
	class pagonline_loader
	{
		private $source;
		private $destination;

		public function __construct()
		{
			$this->source = WP_PLUGIN_DIR . '/pagonline/pagonline.php';
			$this->destination = WP_PLUGIN_DIR . '/wp-e-commerce/wpsc-merchants/pagonline.php';	
		}

		public function load()
		{
			register_activation_hook(__file__, array(&$this, 'activate'));
			register_deactivation_hook(__file__, array(&$this, 'deactivate'));
		}

		public function activate()
		{
			if (!file_exists($this->destination)) 
			{
				copy($this->source, $this->destination);
			}
		}

		public function deactivate()
		{
			if (file_exists($this->destination)) 
			{
				unlink($this->destination);
			}
		}
	}

	$loader = new pagonline_loader();
	$loader->load();
}

?>