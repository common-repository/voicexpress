<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ersolucoesewb.com.br
 * @since             1.0.0
 * @package           Voicexpress
 *
 * @wordpress-plugin
 * Plugin Name:       Voicexpress
 * Plugin URI:        https://voicexpress.app
 * Description:       Converta seus posts WordPress em áudio
 * Version:           1.2.2
 * Author:            ER Soluções Web LTDA
 * Author URI:        https://ersolucoesweb.com.br/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       voicexpress
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require 'src/class-serviceprovider.php';

if ( ! defined( 'WPINC' ) ) {
	die;
}

$voicexpress = new Voicexpress\ServiceProvider();
$voicexpress->boot();

register_activation_hook(
	__FILE__,
	function () use ( $voicexpress ) {
		$voicexpress->voicexpress_install();
	}
);

