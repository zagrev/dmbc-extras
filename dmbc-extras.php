<?php
/**
 * Plugin Name:       DMBC Extras
 * Plugin URI:        https://daytonmetrobarbershopchorus.org
 * Description:       Adds in functionality to bind other plugins together
 *                    and perform functions necessary to running the chorus.
 * Version:           1.0.0
 * Author:            Steve Betts
 * Author URI:        https://github.com/zagrev
 * License:           CC BY-NC-ND
 * Text Domain:       dmbc-extras
 *
* dmbc-extras is free software: you can redistribute it and/or modify
* it under the terms of the CC-BY-NC-ND but you cannot make or sell
* a commercial product or derivitive commercial product using this code.
* See https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode.txt
* 
* dmbc-extras is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
* 
*/
 */

// Security Check: Prevent direct file access outside of WordPress
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require 'plugin-update-checker-5.7/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/zagrev/dmbc-extras',
	__FILE__,
	'dmbc-extras'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
//$myUpdateChecker->setAuthentication('your-token-here');

dmbc_activate()
{
}

dmbc_deactivate()
{
}
   
register_activation_hook(__FILE__,'dmbc_activate');
register_deactivation_hook(__FILE__,'dmbc_deactivate');


