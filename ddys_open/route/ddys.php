<?php

!defined('DEBUG') AND exit('Access Denied.');

ddys_open_route_dispatch();

function ddys_open_route_dispatch()
{
    global $header, $conf, $route, $fid;

    $action = function_exists('param') ? param(1, 'latest') : ddys_open_get('view', 'latest');
    $action = $action === '' ? 'latest' : $action;

    if ($action === 'api') {
        ddys_open_json_response(ddys_open_proxy_response());
    }

    if ($action === 'request-submit') {
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            ddys_open_json_response(ddys_open_error('只允许 POST 提交。', 405), 405);
        }
        ddys_open_json_response(ddys_open_handle_request_form());
    }

    $view = ddys_open_choice($action, array('latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'collection', 'requests'), 'latest');
    $params = array(
        'limit' => ddys_open_get('limit', ''),
        'page' => ddys_open_get('page', ''),
        'q' => ddys_open_get('q', ''),
        'type' => ddys_open_get('type', ''),
        'year' => ddys_open_get('year', ''),
        'month' => ddys_open_get('month', '')
    );
    if ($view === 'movie') {
        $params['slug'] = ddys_open_route_tail(2, ddys_open_get('slug', ''));
    }
    if ($view === 'collection') {
        $params['slug'] = ddys_open_route_tail(2, ddys_open_get('slug', ''));
    }

    $title_map = array(
        'latest' => '最新影片',
        'hot' => '热门影片',
        'search' => '搜索',
        'calendar' => '更新日历',
        'movie' => '影片详情',
        'collections' => '片单',
        'collection' => '片单详情',
        'requests' => '求片'
    );
    $page_title = '低端影视' . (isset($title_map[$view]) ? ' - ' . $title_map[$view] : '');
    $header['title'] = $page_title . ' - ' . $conf['sitename'];
    $header['mobile_title'] = '低端影视';
    $header['mobile_link'] = ddys_open_page_url('latest');
    $fid = 0;
    if (isset($_SESSION)) {
        $_SESSION['fid'] = 0;
    }

    $content = ddys_open_render_page($view, $params);

    include _include(APP_PATH . 'view/htm/header.inc.htm');
    echo '<div class="ddys-xiuno-page">';
    echo '<div class="ddys-xiuno-page-head"><h1>' . ddys_open_h($page_title) . '</h1></div>';
    echo ddys_open_page_tabs($view);
    echo $content;
    echo '</div>';
    include _include(APP_PATH . 'view/htm/footer.inc.htm');
}
