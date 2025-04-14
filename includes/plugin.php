<?php

namespace Better_Fontawesome;

if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class Better_Fontawesome
{
  /**
   * Default Fontawesome version
   *
   * @var string Default Fontawesome version that will be downloaded.
   */
  const DEFAULT_FONTAWESOME_VERSION = '6.7.2';

  /**
   * Fontawesome CDN URL to download versions
   */
  const FONTAWESOME_CDN_URL = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/';

  /**
   * Download directory for Fontawesome assets
   */
  const DOWNLOAD_DIRECTORY = 'better-fontawesome';

  /**
   * Minimum Elementor Version
   *
   * @var string Minimum Elementor version required to run the addon.
   */
  const MINIMUM_ELEMENTOR_VERSION = '3.20.0';

  /**
   * Minimum PHP Version
   *
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
   * Name in wp_options
   */
  const OPTION_NAME = 'bf_options';

  /**
   * Options array from get_option
   *
   * @var array
   */
  private $options = null;

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
  public $fontawesome_version = null;

  /**
   * Available Fontawesome versions to choose from
   * @var array
   */
  private $available_versions = [
    '6.7.2' => [
      'label' => 'v6.7.2 (Latest)',
      'files' => ['main' => 'fontawesome.min.css', 'regular' => 'regular.min.css', 'solid' => 'solid.min.css', 'brands' => 'brands.min.css'],
      'font_files' => [
        'fa-brands-400.woff2',
        'fa-brands-400.ttf',
        'fa-regular-400.woff2',
        'fa-regular-400.ttf',
        'fa-solid-900.woff2',
        'fa-solid-900.ttf',
        'fa-v4compatibility.woff2',
        'fa-v4compatibility.ttf'
      ]
    ],
    '5.15.3' => [
      'label' => 'v5.15.4',
      'files' => ['main' => 'fontawesome.min.css', 'regular' => 'regular.min.css', 'solid' => 'solid.min.css', 'brands' => 'brands.min.css'],
      'font_files' => [
        'fa-brands-400.woff2',
        'fa-brands-400.ttf',
        'fa-regular-400.woff2',
        'fa-regular-400.ttf',
        'fa-solid-900.woff2',
        'fa-solid-900.ttf'
      ],
    ]
  ];

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
   * @access public
   */
  public function __construct()
  {
    $this->plugin_url = BETTER_FONTAWESOME_URL;
    // First installation
    register_activation_hook(BETTER_FONTAWESOME_FILE, [$this, '_activate']);
    // We hook on 'plugins_loaded' because we need Elementor to be loaded
    add_action('plugins_loaded', [$this, '_load']);
    // Register admin page
    add_action('admin_menu', [$this, '_add_admin_page']);
    // Register settings using the Settings API
    add_action('admin_init', [$this, '_register_settings']);
  }

  /**
   * Plugin installation
   * Fired by Wordpress activation hook
   * Create options in database and download FontAwesome
   *
   * @return void
   */
  public function _activate(): void
  {
    // Add default options to database
    $options = [
      'version' => self::DEFAULT_FONTAWESOME_VERSION
    ];

    // Create or update (we reset to plugin defaults on reactivation, maybe change that behavior later idk)
    if (get_option(self::OPTION_NAME)) {
      update_option(self::OPTION_NAME, $options);
    } else {
      add_option(self::OPTION_NAME, $options);
    }

    // Download default version
    $this->download_fontawesome(self::DEFAULT_FONTAWESOME_VERSION);
  }

  /**
   * Initialize
   *
   * Load the plugin functionality only after FontAwesome is downloaded
   *
   * Fired by Wordpress `plugins_loaded` action hook.
   *
   * @access public
   */
  public function _load(): void
  {
    // Load options from database, this sets $this->fontawesome_version to the currently selected version of Fontawesome
    $this->load_options();

    // Check if files exist for current version of FA, or otherwise download
    if (!$this->is_fontawesome_downloaded()) {
      $this->download_fontawesome();
    }

    // Check if Elementor installed and activated
    if (did_action('elementor/loaded')) {
      $this->use_elementor = true;
    }

    if ($this->is_compatible()) {
      // If Elementor is installed and activated, bind to hook and alter default icons
      if ($this->use_elementor) {
        add_action('elementor/icons_manager/native', [$this, 'register_elementor_icons']);
      }

      // With or without Elementor, we enqueue Fontawesome
      add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
  }

  /**
   * Load options from database
   *
   * @return void
   */
  public function load_options()
  {
    $options = get_option(self::OPTION_NAME);
    $this->options = $options;
    $this->fontawesome_version = isset($options['version']) ? $options['version'] : self::DEFAULT_FONTAWESOME_VERSION;
  }

  /**
   * Download FontAwesome version
   *
   * @param string|null $version - FontAwesome version to download, or null to download currently selected version
   * @return bool true if successful
   */
  public function download_fontawesome($version = null): bool
  {
    // If no version provided, set default version to currently selected version, of plugin default
    if ($version === null) {
      $version = $this->fontawesome_version ? $this->fontawesome_version : self::DEFAULT_FONTAWESOME_VERSION;
    }

    // Check if version exists
    if (!isset($this->available_versions[$version])) {
      return false;
    }

    // Get WordPress file system
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      require_once(ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }

    // Create subdirectories in uploads directory
    $upload_dir = wp_upload_dir()['basedir'];
    $download_dir = $upload_dir . '/' . self::DOWNLOAD_DIRECTORY . '/' . $version;
    $css_dir = $download_dir . '/css';
    $webfonts_dir = $download_dir . '/webfonts';
    if (!file_exists($download_dir)) {
      wp_mkdir_p($css_dir);
      wp_mkdir_p($webfonts_dir);
    }

    $downloadError = false;

    // Download CSS files
    foreach ($this->available_versions[$version]['files'] as $file) {
      $css_url = self::FONTAWESOME_CDN_URL . $version . '/css/' . $file;
      $css_content = wp_remote_retrieve_body(wp_remote_get($css_url));
      if (!empty($css_content)) {
        $wp_filesystem->put_contents($css_dir . '/' . $file, $css_content);
      } else {
        $downloadError = true;
        break;
      }
    }

    // Download webfonts files
    foreach ($this->available_versions[$version]['font_files'] as $file) {
      $file_url = self::FONTAWESOME_CDN_URL . $version . '/webfonts/' . $file;
      $file_content = wp_remote_retrieve_body(wp_remote_get($file_url));
      if (!empty($file_content)) {
        $wp_filesystem->put_contents($webfonts_dir . '/' . $file, $file_content);
      } else {
        $downloadError = true;
        break;
      }
    }

    if (!$downloadError) {
      // Update options
      $options = get_option(self::OPTION_NAME);
      update_option(self::OPTION_NAME, $options);

      return true;
    } else {
      throw new \Error("BFA : Download error");
    }

    return false;
  }

  /**
   * Check if this version of FontAwesome has been downloaded
   *
   * @return boolean true if the files are present
   */
  public function is_fontawesome_downloaded(): bool
  {
    $upload_dir = wp_upload_dir()['basedir'];
    $download_dir = $upload_dir . '/' . self::DOWNLOAD_DIRECTORY . '/' . $this->fontawesome_version;
    return file_exists($download_dir);
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
        'url' => $this->get_regular_stylesheet_url(),
        'enqueue' => [$this->get_main_stylesheet_url()],
        'prefix' => 'fa-',
        'displayPrefix' => 'far',
        'labelIcon' => 'fab fa-font-awesome-alt',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->get_json_url($this->fontawesome_version) . 'regular.json',
        'native' => true,
      ],
      'fa-solid' => [
        'name' => 'fa-solid',
        'label' => "Font Awesome - Solid - v" . $this->fontawesome_version,
        'url' => $this->get_solid_stylesheet_url(),
        'enqueue' => [$this->get_main_stylesheet_url()],
        'prefix' => 'fa-',
        'displayPrefix' => 'fas',
        'labelIcon' => 'fab fa-font-awesome',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->get_json_url($this->fontawesome_version) . 'solid.json',
        'native' => true,
      ],
      'fa-brands' => [
        'name' => 'fa-brands',
        'label' => "Font Awesome - Brands - v" . $this->fontawesome_version,
        'url' => $this->get_brands_stylesheet_url(),
        'enqueue' => [$this->get_main_stylesheet_url()],
        'prefix' => 'fa-',
        'displayPrefix' => 'fab',
        'labelIcon' => 'fab fa-font-awesome-flag',
        'ver' => $this->fontawesome_version,
        'fetchJson' => $this->get_json_url($this->fontawesome_version) . 'brands.json',
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
      $this->get_main_stylesheet_url(),
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
   * Add admin page
   *
   * @return void
   */
  public function _add_admin_page()
  {
    add_options_page(
      'Better Fontawesome',
      'Better Fontawesome',
      'manage_options',
      'better-fontawesome',
      [$this, '_admin_page']
    );
  }

  /**
   * Admin page
   */
  public function _admin_page(): void
  {
    // Check user capability
    if (!current_user_can('manage_options')) {
      return;
    }

    ob_start();
    include 'pages/admin-page.php';
    echo ob_get_clean();
  }

  /**
   * Registers sections and fields for the admin page, using the Settings API
   *
   * @return void
   */
  public function _register_settings(): void
  {
    // Register settings group name for specified option in database
    register_setting('better_fontawesome', self::OPTION_NAME);

    // Registers a section using the Settings API (retrieved with do_settings_sections() in the admin page)
    add_settings_section(
      'better_fontawesome_section_version',
      'Font Awesome Version',
      [$this, '_settings_section_version'],
      'better-fontawesome'
    );

    // Registers a field using the Settings API (retrieved with settings_fields() in the admin page)
    add_settings_field(
      'better_fontawesome_field_version',
      'Version',
      [$this, '_settings_field_version'],
      'better-fontawesome',
      'better_fontawesome_section_version'
    );
  }

  public function _settings_section_version(): void
  {
    ob_start();
    include 'settings/section-version.php';
    echo ob_get_clean();
  }

  public function _settings_field_version(): void
  {
    ob_start();
    include 'settings/field-version.php';
    echo ob_get_clean();
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
   * @access public
   */
  public function is_compatible(): bool
  {

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

  /**
   * Get the upload directory of a specific Fontawesome version
   *
   * @param string $version - Fontawesome version
   * @return string - upload directory with end trailing slash
   */
  public function get_fa_directory(): string
  {
    return wp_get_upload_dir()['basedir'] . '/' . self::DOWNLOAD_DIRECTORY . '/' . $this->fontawesome_version . '/';
  }

  /**
   * Get the public URL for the files of a specific Fontawesome version
   *
   * @param string $version - Fontawesome version
   * @return string - public url with end trailing slash
   */
  public function get_fa_url(): string
  {
    return wp_get_upload_dir()['baseurl'] . '/' . self::DOWNLOAD_DIRECTORY . '/' . $this->fontawesome_version . '/css/';
  }

  public function get_main_stylesheet_url(): string
  {
    return $this->get_fa_url() . $this->available_versions[$this->fontawesome_version]['files']['main'];
  }

  public function get_regular_stylesheet_url(): string
  {
    return $this->get_fa_url() . $this->available_versions[$this->fontawesome_version]['files']['regular'];
  }

  public function get_solid_stylesheet_url(): string
  {
    return $this->get_fa_url() . $this->available_versions[$this->fontawesome_version]['files']['solid'];
  }

  public function get_brands_stylesheet_url(): string
  {
    return $this->get_fa_url() . $this->available_versions[$this->fontawesome_version]['files']['brands'];
  }

  /**
   * Get the public URL to the JSON files containing the icon list for the Elementor Icon Library
   *
   * @param string $version
   * @return string - public url with end trailing slash
   */
  public function get_json_url($version): string
  {
    return $this->plugin_url . 'assets/json/' . $version . '/';
  }
}
