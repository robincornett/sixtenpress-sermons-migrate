<?php
/**
 * @package           SixTenPressSermonsMigrate
 * @author            Robin Cornett
 * @link              https://bitbucket.org/robincornett/sixtenpress-sermons-migrate
 * @copyright         2015-2016 Robin Cornett
 * @license           GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Six/Ten Press Sermons Migration
 * Plugin URI:        https://bitbucket.org/robincornett/sixtenpress-sermons
 * Description:       This plugin converts Sermon Manager posts to Six/Ten Press Sermons.
 * Version:           0.1.0
 * Author:            Robin Cornett
 * Author URI:        https://robincornett.com/
 * Text Domain:       sixtenpress-sermons-migrate
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://bitbucket.org/robincornett/sixtenpress-sermons
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'SIXTENPRESSSERMONSMIGRATE_BASENAME' ) ) {
	/**
	 * Constant for plugin basename.
	 */
	define( 'SIXTENPRESSSERMONSMIGRATE_BASENAME', plugin_basename( __FILE__ ) );
}

function sixtenpresssermonsmigrate_require() {
	$files = array(
		'class-sixtenpresssermons-migrate',
	);

	foreach ( $files as $file ) {
		require plugin_dir_path( __FILE__ ) . 'includes/' . $file . '.php';
	}
}
sixtenpresssermonsmigrate_require();

// Instantiate dependent classes
$sixtenpresssermonsmigrate = new SixTenPressSermonsMigrate();
add_action( 'admin_init', array( $sixtenpresssermonsmigrate, 'run' ) );
