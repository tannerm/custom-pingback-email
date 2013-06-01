<?php
/**
 * Plugin Name: Custom Pingback Email
 * Plugin URI:  http://wordpress.org/extend/plugins
 * Description: Add the option for a pingback notification to go to an email other than the site admin email.
 * Version:     0.1.0
 * Author:      Tanner Moushey
 * Author URI:  tannermoushey.com
 * License:     GPLv2+
 * Text Domain: cpe
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Tanner Moushey (email : tanner@moushey.us)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'CPE_VERSION', '0.1.0' );
define( 'CPE_URL',     plugin_dir_url( __FILE__ ) );
define( 'CPE_PATH',    dirname( __FILE__ ) . '/' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function cpe_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'cpe' );
	load_textdomain( 'cpe', WP_LANG_DIR . '/cpe/cpe-' . $locale . '.mo' );
	load_plugin_textdomain( 'cpe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Activate the plugin
 */
function cpe_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	cpe_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cpe_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function cpe_deactivate() {

}
register_deactivation_hook( __FILE__, 'cpe_deactivate' );

// Wireup actions
add_action( 'init', 'cpe_init' );

// Wireup filters

// Wireup shortcodes
