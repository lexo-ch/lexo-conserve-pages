<?php

/**
 * Plugin Name:       LEXO Conserve Pages
 * Plugin URI:        https://github.com/lexo-ch/lexo-conserve-pages/
 * Description:       Groups marked pages.
 * Version:           1.0.1
 * Requires at least: 4.7
 * Requires PHP:      7.4.1
 * Author:            LEXO GmbH
 * Author URI:        https://www.lexo.ch
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cp
 * Domain Path:       /languages
 * Update URI:        lexo-conserve-pages
 */

namespace LEXO\CP;

use Exception;
use LEXO\CP\Activation;
use LEXO\CP\Deactivation;
use LEXO\CP\Uninstalling;
use LEXO\CP\Core\Bootloader;

// Prevent direct access
!defined('WPINC')
    && die;

// Define Main plugin file
!defined('LEXO\CP\FILE')
    && define('LEXO\CP\FILE', __FILE__);

// Define plugin name
!defined('LEXO\CP\PLUGIN_NAME')
    && define('LEXO\CP\PLUGIN_NAME', get_file_data(FILE, [
        'Plugin Name' => 'Plugin Name'
    ])['Plugin Name']);

// Define plugin slug
!defined('LEXO\CP\PLUGIN_SLUG')
    && define('LEXO\CP\PLUGIN_SLUG', get_file_data(FILE, [
        'Update URI' => 'Update URI'
    ])['Update URI']);

// Define Basename
!defined('LEXO\CP\BASENAME')
    && define('LEXO\CP\BASENAME', plugin_basename(FILE));

// Define internal path
!defined('LEXO\CP\PATH')
    && define('LEXO\CP\PATH', plugin_dir_path(FILE));

// Define assets path
!defined('LEXO\CP\ASSETS')
    && define('LEXO\CP\ASSETS', trailingslashit(PATH) . 'assets');

// Define internal url
!defined('LEXO\CP\URL')
    && define('LEXO\CP\URL', plugin_dir_url(FILE));

// Define internal version
!defined('LEXO\CP\VERSION')
    && define('LEXO\CP\VERSION', get_file_data(FILE, [
        'Version' => 'Version'
    ])['Version']);

// Define min PHP version
!defined('LEXO\CP\MIN_PHP_VERSION')
    && define('LEXO\CP\MIN_PHP_VERSION', get_file_data(FILE, [
        'Requires PHP' => 'Requires PHP'
    ])['Requires PHP']);

// Define min WP version
!defined('LEXO\CP\MIN_WP_VERSION')
    && define('LEXO\CP\MIN_WP_VERSION', get_file_data(FILE, [
        'Requires at least' => 'Requires at least'
    ])['Requires at least']);

// Define Text domain
!defined('LEXO\CP\DOMAIN')
    && define('LEXO\CP\DOMAIN', get_file_data(FILE, [
        'Text Domain' => 'Text Domain'
    ])['Text Domain']);

// Define locales folder (with all translations)
!defined('LEXO\CP\LOCALES')
    && define('LEXO\CP\LOCALES', 'languages');

!defined('LEXO\CP\CACHE_KEY')
    && define('LEXO\CP\CACHE_KEY', DOMAIN . '_cache_key_update');

!defined('LEXO\CP\UPDATE_PATH')
    && define('LEXO\CP\UPDATE_PATH', 'https://wprepo.lexo.ch/public/lexo-conserve-pages/info.json');

if (!file_exists($composer = PATH . '/vendor/autoload.php')) {
    wp_die('Error locating autoloader in LEXO Conserve Pages.
        Please run a following command:<pre>composer install</pre>', 'cp');
}

require $composer;

register_activation_hook(FILE, function () {
    (new Activation())->run();
});

register_deactivation_hook(FILE, function () {
    (new Deactivation())->run();
});

if (!function_exists('cp_uninstall')) {
    function cp_uninstall()
    {
        (new Uninstalling())->run();
    }
}
register_uninstall_hook(FILE, __NAMESPACE__ . '\cp_uninstall');

try {
    Bootloader::getInstance()->run();
} catch (Exception $e) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

    deactivate_plugins(FILE);

    wp_die($e->getMessage());
}
