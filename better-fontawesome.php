<?php

/**
 * Plugin Name:     Better Fontawesome (compatible with Elementor)
 * Plugin URI:      https://wordpress.org/plugins/better-fontawesome
 * Version:         1.2.1
 * Description:     Allows you to choose which version of FontAwesome to use with WP and Elementor (currently 5.13.3 or 6.7.2)
 * Author:          Keyvan ESTERMANN
 * Author URI:      https://github.com/keyvanestermann
 * License: GPL-2
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */

use Better_Fontawesome\Better_Fontawesome;

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

define('BETTER_FONTAWESOME_FILE', __FILE__);
define('BETTER_FONTAWESOME_URL', plugins_url('/', BETTER_FONTAWESOME_FILE));

// Load plugin file
require_once(__DIR__ . '/includes/plugin.php');

// Run the plugin
new Better_Fontawesome();