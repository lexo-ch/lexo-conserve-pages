<?php

namespace LEXO\CP\Core;

use LEXO\CP\Core\Abstracts\Singleton;
use LEXO\CP\Core\Plugin\PluginService;
use LEXO\CP\Core\Notices\Notices;

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
}
