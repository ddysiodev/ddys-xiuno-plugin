<?php exit;
if (isset($route) && function_exists('ddys_open_is_plugin_route') && ddys_open_is_plugin_route($route) && function_exists('ddys_open_route_dispatch')) {
    define('SKIP_ROUTE', TRUE);
    include _include(APP_PATH . 'plugin/ddys_open/route/ddys.php');
}
