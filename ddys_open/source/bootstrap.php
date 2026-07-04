<?php

!defined('DEBUG') AND exit('Access Denied.');

define('DDYS_OPEN_XIUNO_VERSION', '0.1.0');
define('DDYS_OPEN_XIUNO_ID', 'ddys_open');
define('DDYS_OPEN_XIUNO_API_DEFAULT', 'https://ddys.io/api/v1');
define('DDYS_OPEN_XIUNO_SITE_DEFAULT', 'https://ddys.io');
define('DDYS_OPEN_XIUNO_SETTING_KEY', 'ddys_open_settings');

function ddys_open_bootstrap()
{
    static $loaded = FALSE;
    if ($loaded) {
        return;
    }
    $loaded = TRUE;

    $base = APP_PATH . 'plugin/ddys_open/source/';
    include_once $base . 'security.php';
    include_once $base . 'cache.php';
    include_once $base . 'client.php';
    include_once $base . 'render.php';
    include_once $base . 'shortcode.php';
}

function ddys_open_install()
{
    $settings = ddys_open_settings();
    ddys_open_save_settings($settings);
    ddys_open_cache_dir();
}

function ddys_open_uninstall()
{
    ddys_open_cache_clear();
}
