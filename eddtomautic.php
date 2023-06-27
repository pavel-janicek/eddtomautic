<?php
/*
Plugin Name: Easy Digital Downloads Export to Mautic
Plugin URL: http://cleverstart.cz
Description: Export emails given by the customers when downloading to Mautic
Version: 3.1.18
Author: Pavel Janíček
Author URI: http://cleverstart.cz
*/

include_once __DIR__ . '/vendor/autoload.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://plugins.cleverstart.cz/?action=get_metadata&slug=eddtomautic',
	__FILE__, //Full path to the main plugin file or functions.php.
	'eddtomautic'
);

require_once  __DIR__ . '/libs/class_edd_mautic.php';

$mautic = new Clvr_EDD_Mautic();
