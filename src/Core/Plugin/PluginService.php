<?php

namespace LEXO\CP\Core\Plugin;

use LEXO\CP\Core\Abstracts\Singleton;
use LEXO\CP\Core\Traits\Helpers;
use LEXO\CP\Core\Loader\Loader;
use LEXO\CP\Core\Updater\PluginUpdater;

use const LEXO\CP\{
    ASSETS,
    PLUGIN_NAME,
    PLUGIN_SLUG,
    VERSION,
    MIN_PHP_VERSION,
    MIN_WP_VERSION,
    DOMAIN,
    BASENAME,
    CACHE_KEY,
    UPDATE_PATH,
};

class PluginService extends Singleton
{
    use Helpers;

    private static string $namespace    = 'custom-plugin-namespace';
    protected static $instance          = null;

    private const CHECK_UPDATE          = 'check-update-' . PLUGIN_SLUG;

    public function setNamespace(string $namespace)
    {
        self::$namespace = $namespace;
    }

    public function registerNamespace()
    {
        $config = require_once trailingslashit(ASSETS) . 'config/config.php';

        $loader = Loader::getInstance();

        $loader->registerNamespace(self::$namespace, $config);

        add_action('admin_post_' . self::CHECK_UPDATE, [$this, 'checkForUpdateManually']);
    }

    public function addAdminLocalizedScripts()
    {
        $vars = [
            'plugin_name'       => PLUGIN_NAME,
            'plugin_slug'       => PLUGIN_SLUG,
            'plugin_version'    => VERSION,
            'min_php_version'   => MIN_PHP_VERSION,
            'min_wp_version'    => MIN_WP_VERSION,
            'text_domain'       => DOMAIN
        ];

        $vars = apply_filters(self::$namespace . '/admin_localized_script', $vars);

        wp_localize_script(trailingslashit(self::$namespace) . 'admin-' . DOMAIN . '.js', DOMAIN . 'AdminLocalized', $vars);
    }

    public function addPluginLinks()
    {
        add_filter(
            'plugin_action_links_' . BASENAME,
            [$this, 'setPluginLinks']
        );
    }

    public function setPluginLinks($links)
    {
        $update_check_url = self::getManualUpdateCheckLink();
        $update_check_link = "<a href='{$update_check_url}'>" . __('Update check', 'cp') . '</a>';

        array_push(
            $links,
            $update_check_link
        );

        return $links;
    }

    public function updater()
    {
        return (new PluginUpdater())
            ->setBasename(BASENAME)
            ->setSlug(PLUGIN_SLUG)
            ->setVersion(VERSION)
            ->setRemotePath(UPDATE_PATH)
            ->setCacheKey(CACHE_KEY)
            ->setCacheExpiration(HOUR_IN_SECONDS)
            ->setCache(true);
    }

    public function checkForUpdateManually()
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], self::CHECK_UPDATE)) {
            wp_die(__('Security check failed.', 'cp'));
        }

        $plugin_settings = PluginService::getInstance();

        if (!$plugin_settings->updater()->hasNewUpdate()) {
            set_transient(
                DOMAIN . '_no_updates_notice',
                sprintf(
                    __('Plugin %s is up to date.', 'cp'),
                    PLUGIN_NAME
                ),
                HOUR_IN_SECONDS
            );
        } else {
            delete_transient(CACHE_KEY);
        }

        wp_safe_redirect(admin_url('plugins.php'));

        exit;
    }

    public function noUpdatesNotice()
    {
        $message = get_transient(DOMAIN . '_no_updates_notice');
        delete_transient(DOMAIN . '_no_updates_notice');

        if (!$message) {
            return false;
        }

        $this->notices->add(
            $this->notice->message($message)->type('success')
        );
    }

    public function updateSuccessNotice()
    {
        $message = get_transient(DOMAIN . '_update_success_notice');
        delete_transient(DOMAIN . '_update_success_notice');

        if (!$message) {
            return false;
        }

        $this->notices->add(
            $this->notice->message($message)->type('success')
        );
    }

    public static function getManualUpdateCheckLink(): string
    {
        return esc_url(
            add_query_arg(
                [
                    'action' => self::CHECK_UPDATE,
                    'nonce' => wp_create_nonce(self::CHECK_UPDATE)
                ],
                admin_url('admin-post.php')
            )
        );
    }
}
