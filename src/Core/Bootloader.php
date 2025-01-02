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
        $converter = (new Conserver());

        add_action('init', [$this, 'onInit'], 10);
        add_action(DOMAIN . '/localize/admin-cp.js', [$this, 'onAdminCpJsLoad']);
        add_action('after_setup_theme', [$this, 'onAfterSetupTheme']);
        add_action('wp_ajax_toggle_conserve_page', [$converter, 'toggleConservePageStatus']);
        add_action('manage_pages_custom_column', [$converter, 'displayConservePageCheckbox'], 10, 2);
        add_action('parse_query', [$converter, 'filterConservePageQuery']);

        add_filter('manage_pages_columns', [$converter, 'addConservePageColumn']);
        add_filter('views_edit-page', [$converter, 'filterPageCount'], 10, 1);
        add_filter('post_class', [$converter, 'applyCustomClassToConvertedPages'], 10, 3);
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
}
