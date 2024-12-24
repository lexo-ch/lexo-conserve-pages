<?php

namespace LEXO\CP\Core;

use LEXO\CP\Core\Abstracts\Singleton;
use LEXO\CP\Core\Plugin\PluginService;
use LEXO\CP\Core\Notices\Notices;
use LEXO\CP\Core\Plugin\Conserver;

use const LEXO\CP\{
    DOMAIN,
    PATH,
    LOCALES
};

class Bootloader extends Singleton
{
    protected static $instance = null;

    public function run()
    {
        add_action('init', [$this, 'onInit'], 10);
        add_action(DOMAIN . '/localize/admin-cp.js', [$this, 'onAdminCpJsLoad']);
        add_action('after_setup_theme', [$this, 'onAfterSetupTheme']);
        add_action('admin_init', [$this, 'onAdminInit'], 10);
        add_filter('manage_pages_columns', [$this, 'addConservePageColumn']);
        add_action('manage_pages_custom_column', [$this, 'displayConservePageCheckbox'], 10, 2);
        add_action('wp_ajax_toggle_conserve_page', [$this, 'toggleConservePageStatus']);
        add_action('parse_query', [$this, 'filterConservePageQuery']);
        add_action('save_post', [$this, 'setConservePageForChild'], 10, 3);
        add_filter('views_edit-page', [$this, 'filterPageCount'], 10, 1);
    }

    public function onAdminInit()
    {
        $plugin_settings = PluginService::getInstance();
    }

    public function onInit()
    {
        do_action(DOMAIN . '/init');

        $plugin_settings = PluginService::getInstance();
        $plugin_settings->setNamespace(DOMAIN);
        $plugin_settings->registerNamespace();
        $plugin_settings->addPluginLinks();
        $plugin_settings->noUpdatesNotice();
        $plugin_settings->updateSuccessNotice();

        (new Notices())->run();
    }

    public function onAdminCpJsLoad()
    {
        PluginService::getInstance()->addAdminLocalizedScripts();
    }

    public function onAfterSetupTheme()
    {
        $this->loadPluginTextdomain();
        PluginService::getInstance()->updater()->run();
    }

    public function loadPluginTextdomain()
    {
        load_plugin_textdomain(DOMAIN, false, trailingslashit(trailingslashit(basename(PATH)) . LOCALES));
    }

    public function addConservePageColumn($columns)
    {
        return Conserver::handleAddConservePageColumn($columns);
    }

    public function displayConservePageCheckbox($column, $post_id)
    {
        return Conserver::handleDisplayConservePageCheckbox($column, $post_id);
    }

    public function toggleConservePageStatus()
    {
        Conserver::handleToggleConservePageStatus();
    }

    public function setConservePageForChild($post_id, $post, $update)
    {
        return Conserver::handleSetConservePageForChild($post_id, $post, $update);
    }

    public function filterConservePageQuery($query)
    {
        Conserver::handleFilterConservePageQuery($query);
    }

    public function filterPageCount($views)
    {
        return Conserver::handleFilterPageCount($views);
    }
}
