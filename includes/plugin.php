<?php

namespace Better_Fontawesome;

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class Better_Fontawesome
{
  /**
   * Plugin Version
   *
   * @since 1.0.0
   * @var string The plugin version.
   */
  const VERSION = '1.0.0';

  /**
   * Minimum Elementor Version
   *
   * @since 1.0.0
   * @var string Minimum Elementor version required to run the addon.
   */
  const MINIMUM_ELEMENTOR_VERSION = '3.20.0';

  /**
   * Minimum PHP Version
   *
   * @since 1.0.0
   * @var string Minimum PHP version required to run the addon.
   */
  const MINIMUM_PHP_VERSION = '7.4';

  /**
   * Plugin directory
   */
  const PLUGIN_DIR = WP_PLUGIN_DIR  . '/better-fontawesome/';

  /**
   * @var string Handle used for enqueuing the different Fontawesome stylesheets (regular, solid, brands...)
   */
  const ELEMENTOR_ICONS_HANDLE = 'elementor-icons-';

  /**
   * @var string Handle used for enqueuing the main Fontawesome stylesheet
   * @todo We assume that FontAwesome is the first shared icon library added to Elementor, and that is why the style handle
   * is 'elementor-icons-shared-0', but this might break at some point.
   */
  const ELEMENTOR_ICONS_HANDLE_SHARED = self::ELEMENTOR_ICONS_HANDLE . 'shared-0';

  /**
   * @var bool If true, Elementor is installed and activated
   */
  private $use_elementor = false;

  /**
   * Public URL to plugin directory
   * @var string URL to plugin directory with trailing slash
   */
  public $plugin_url = null;

  /**
   * Fontawesome Version used by this plugin
   *
   * @var string Fontawesome version number 
   */
  public $fontawesome_version = "6.7.2";

  /**
   * Icons configuration array
   *
   * @var array|null Icons configuration array
   */
  private $icons = null;

  /**
   * URL to Fontawesome main stylesheet
   *
   * @var string|null URL to Fontawesome main stylesheet
   */
  private $main_stylesheet = null;


  /**
   * Constructor
   *
   * Perform some compatibility checks to make sure basic requirements are meet.
   * If all compatibility checks pass, initialize the functionality.
   *
   * @since 1.0.0
   * @access public
   */
  public function __construct()
  {
    $this->plugin_url = BETTER_FONTAWESOME_URL;
    $this->main_stylesheet = $this->plugin_url  . 'assets/fontawesome/css/fontawesome.min.css';

    if ($this->is_compatible()) {
      add_action('init', [$this, 'init']);
    }
  }

  /**
   * Initialize
   *
   * Load the addons functionality only after Elementor is initialized.
   *
   * Fired by `elementor/init` action hook.
   *
   * @since 1.0.0
   * @access public
   */
  public function init(): void
  {

    if ($this->use_elementor) {
      add_action('elementor/icons_manager/native', [$this, 'register_elementor_icons']);
    }

    add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
  }

  /**
   * Retrieve icon configuration for both the Elementor Icon Picker and our enqueue_scripts() function
   *
   * @return array Icons configuration
   */
  public function get_icons(): array
  {
    if ($this->icons) {
      return $this->icons;
    }

    $this->icons = [
      'fa-regular' => [
        'name' => 'fa-regular',
        'label' => "Font Awesome - Regular - v" . $this->fontawesome_version,
        'url' => $this->plugin_url . 'assets/fontawesome/css/regular.min.css',
        'enqueue' => [$this->main_stylesheet],
        'prefix' => 'fa-',
        'displayPrefix' => 'far',
        'labelIcon' => 'fab fa-font-awesome-alt',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->plugin_url . 'assets/shims/regular.json',
        'native' => true,
      ],
      'fa-solid' => [
        'name' => 'fa-solid',
        'label' => "Font Awesome - Solid - v" . $this->fontawesome_version,
        'url' => $this->plugin_url . 'assets/fontawesome/css/solid.min.css',
        'enqueue' => [$this->main_stylesheet],
        'prefix' => 'fa-',
        'displayPrefix' => 'fas',
        'labelIcon' => 'fab fa-font-awesome',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->plugin_url . 'assets/shims/solid.json',
        'native' => true,
      ],
      'fa-brands' => [
        'name' => 'fa-brands',
        'label' => "Font Awesome - Brands - v" . $this->fontawesome_version,
        'url' => $this->plugin_url . 'assets/fontawesome/css/brands.min.css',
        'enqueue' => [$this->main_stylesheet],
        'prefix' => 'fa-',
        'displayPrefix' => 'fab',
        'labelIcon' => 'fab fa-font-awesome-flag',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->plugin_url . 'assets/shims/brands.json',
        'native' => true,
      ]
    ];
    return $this->icons;
  }

  /**
   * Enqueue scripts and styles, with our without Elementor present
   *
   * @return void
   */
  public function enqueue_scripts(): void
  {
    // Main stylesheet
    $this->enqueue_style_if_not_exists(
      self::ELEMENTOR_ICONS_HANDLE_SHARED,
      $this->main_stylesheet,
      [],
      $this->fontawesome_version
    );

    // Additionnal stylesheets (icons)
    foreach ($this->get_icons() as $icon) {
      $this->enqueue_style_if_not_exists(
        self::ELEMENTOR_ICONS_HANDLE . $icon['name'],
        $icon['url'],
        [self::ELEMENTOR_ICONS_HANDLE_SHARED],
        $this->fontawesome_version
      );
    }
  }

  /**
   * register_elementor_icons
   * Handler for the 'elementor/icons_manager/native' Wordpress Hook
   *
   * @param  array $initial_tabs - intial configuration for the Elementor Icon Picker
   * @see          wp-content/plugins/elementor/includes/managers/icons.php
   * @return array Altered configuration array for the Elementor Icon Picker (with our added Fontawesome icons)
   */
  public function register_elementor_icons($initial_tabs): array
  {
    return array_merge($initial_tabs, $this->get_icons());
  }

  /**
   * Compatibility Checks
   *
   * Checks whether the site meets the addon requirement.
   *
   * @since 1.0.0
   * @access public
   */
  public function is_compatible(): bool
  {

    // Check if Elementor installed and activated
    if (did_action('elementor/loaded')) {
      $this->use_elementor = true;
    }

    // Check for required Elementor version
    if ($this->use_elementor && ! version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
      return false;
    }

    // Check for required PHP version
    if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
      return false;
    }

    return true;
  }

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
  public function enqueue_style_if_not_exists($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
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
}
