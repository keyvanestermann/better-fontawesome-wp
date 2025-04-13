<?php

/**
 * Plugin Name:     Better Fontawesome For Wordpress & Elementor
 * Plugin URI:      https://wordpress.org/plugins/better-fontawesome
 * Version:         1.0.0
 * Description:     Allows you to use any version of Fontawesome with Wordpress & Elementor
 * Author:          Keyvan ESTERMANN
 * Author URI:      https://swift-dev.fr/
 * License: GPL-2
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */

use Better_Fontawesome\Better_Fontawesome;

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

define('BETTER_FONTAWESOME_FILE', __FILE__);
define('BETTER_FONTAWESOME_URL', plugins_url('/', BETTER_FONTAWESOME_FILE));

add_action('plugins_loaded', function () {
  // Load plugin file
  require_once(__DIR__ . '/includes/plugin.php');

  // Run the plugin
  new Better_Fontawesome();
});