<?php

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

define('BFE_DIR', plugin_dir_url(__FILE__));
define('BFE_ELEMENTOR_ICONS_HANDLE', 'elementor-icons-');
// Here we assume that FontAwesome is the first shared icon library added to Elementor (because it currently is), and that is why the style handle
// is 'elementor-icons-shared-0', but this WILL break at some point.
define('BFE_ELEMENTOR_ICONS_HANDLE_SHARED', BFE_ELEMENTOR_ICONS_HANDLE . 'shared-0');
// TODO : allow us to pick a version (above 6)
define('BFE_FONTAWESOME_VERSION', '6.7.2');

add_action('init', function () {

  // Define icons and editor tabs
  $mainStylesheet = BFE_DIR . 'assets/fontawesome/css/fontawesome.min.css';
  $icons = [
    'fa-regular' => [
      'name' => 'fa-regular',
      'label' => "Font Awesome - Regular - v" . BFE_FONTAWESOME_VERSION,
      'url' => BFE_DIR . 'assets/fontawesome/css/regular.min.css',
      'enqueue' => [$mainStylesheet],
      'prefix' => 'fa-',
      'displayPrefix' => 'far',
      'labelIcon' => 'fab fa-font-awesome-alt',
      'ver' => BFE_FONTAWESOME_VERSION,
      'fetchJson' => BFE_DIR . 'assets/shims/regular.json',
      'native' => true,
    ],
    'fa-solid' => [
      'name' => 'fa-solid',
      'label' => "Font Awesome - Solid - v" . BFE_FONTAWESOME_VERSION,
      'url' => BFE_DIR . 'assets/fontawesome/css/solid.min.css',
      'enqueue' => [$mainStylesheet],
      'prefix' => 'fa-',
      'displayPrefix' => 'fas',
      'labelIcon' => 'fab fa-font-awesome',
      'ver' => BFE_FONTAWESOME_VERSION,
      'fetchJson' => BFE_DIR . 'assets/shims/solid.json',
      'native' => true,
    ],
    'fa-brands' => [
      'name' => 'fa-brands',
      'label' => "Font Awesome - Brands - v" . BFE_FONTAWESOME_VERSION,
      'url' => BFE_DIR . 'assets/fontawesome/css/brands.min.css',
      'enqueue' => [$mainStylesheet],
      'prefix' => 'fa-',
      'displayPrefix' => 'fab',
      'labelIcon' => 'fab fa-font-awesome-flag',
      'ver' => BFE_FONTAWESOME_VERSION,
      'fetchJson' => BFE_DIR . 'assets/shims/brands.json',
      'native' => true,
    ]
  ];

  // This hook allows us to add and/or remove icons library to Elementor (see Elementor documentation for PHP Hooks)
  add_filter('elementor/icons_manager/native', function ($initial_tabs) use ($icons) {
    // Replace default Integration
    // See wp-content/plugins/elementor/includes/managers/icons.php (line 128)
    return array_merge($initial_tabs, $icons);
  }, 10);

  // We register and enqueue FontAwesome even outside Elementor pages (TODO : make this configurable ?)
  add_action('wp_enqueue_scripts', function () use ($icons, $mainStylesheet) {

    // Default
    BFE_enqueue_style_if_not_exists(
      BFE_ELEMENTOR_ICONS_HANDLE_SHARED,
      $mainStylesheet,
      [],
      BFE_FONTAWESOME_VERSION
    );

    foreach ($icons as $icon) {
      BFE_enqueue_style_if_not_exists(
        BFE_ELEMENTOR_ICONS_HANDLE . $icon['name'],
        $icon['url'],
        [BFE_ELEMENTOR_ICONS_HANDLE_SHARED],
        BFE_FONTAWESOME_VERSION
      );
    }
  }, 99);
});

/**
 * Checks if a style is already registered/enqueued and handles it appropriately
 *
 * @param string $handle      The style handle.
 * @param string $src         The source URL of the style.
 * @param array  $deps        Optional. An array of registered style handles this style depends on.
 * @param string $ver         Optional. String specifying the style version number.
 * @param string $media       Optional. The media for which this stylesheet has been defined.
 * @return bool               True if style was enqueued, false if it was already enqueued.
 */
function BFE_enqueue_style_if_not_exists($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
  // Check if the style is already registered
  if (wp_style_is($handle, 'registered')) {
    // Style is registered but not enqueued, so just enqueue it
    if (!wp_style_is($handle, 'enqueued')) {
      wp_enqueue_style($handle);
      return true;
    } else {
      // Style is already enqueued
      return false;
    }
  } else {
    // Style is not registered, so register and enqueue it
    wp_register_style($handle, $src, $deps, $ver, $media);
    wp_enqueue_style($handle);
    return true;
  }
}
