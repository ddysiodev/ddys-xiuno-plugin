<?php

!defined('DEBUG') AND exit('Access Denied.');

function ddys_open_payload_data($payload)
{
    if (is_array($payload) && array_key_exists('data', $payload)) {
        return $payload['data'];
    }
    return $payload;
}

function ddys_open_payload_meta($payload)
{
    return is_array($payload) && isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : array();
}

function ddys_open_is_assoc($array)
{
    return is_array($array) && !empty($array) && array_keys($array) !== range(0, count($array) - 1);
}

function ddys_open_to_list($data)
{
    if (!is_array($data)) {
        return array();
    }
    foreach (array('items', 'movies', 'results', 'shares', 'requests', 'activities', 'comments') as $key) {
        if (isset($data[$key]) && is_array($data[$key])) {
            return $data[$key];
        }
    }
    if (ddys_open_is_assoc($data) && (isset($data['slug']) || isset($data['id']) || isset($data['title']) || isset($data['name']))) {
        return array($data);
    }
    return ddys_open_is_assoc($data) ? array() : $data;
}

function ddys_open_value($item, $keys, $fallback = '')
{
    if (!is_array($item)) {
        return $fallback;
    }
    foreach ($keys as $key) {
        if (isset($item[$key]) && $item[$key] !== '') {
            return $item[$key];
        }
    }
    return $fallback;
}

function ddys_open_wrap($html, $args = array())
{
    $settings = ddys_open_settings();
    $layout = isset($args['layout']) && $args['layout'] !== '' ? $args['layout'] : $settings['layout'];
    $theme = isset($args['theme']) && $args['theme'] !== '' ? $args['theme'] : $settings['theme'];
    $columns = isset($args['columns']) && $args['columns'] !== '' ? (int)$args['columns'] : (int)$settings['columns'];
    $layout = ddys_open_choice($layout, array('grid', 'list', 'compact'), $settings['layout']);
    $theme = ddys_open_choice($theme, array('auto', 'light', 'dark'), $settings['theme']);
    $columns = ddys_open_int_range($columns, 4, 1, 6);
    return '<div class="ddys-xiuno ddys-xiuno-theme-' . ddys_open_attr($theme) . ' ddys-xiuno-layout-' . ddys_open_attr($layout) . '" style="--ddys-xiuno-columns:' . $columns . '">' . $html . '</div>';
}

function ddys_open_render_error($payload, $args = array())
{
    $message = is_array($payload) && isset($payload['message']) ? $payload['message'] : '低端影视内容加载失败。';
    return ddys_open_wrap('<div class="ddys-xiuno-alert ddys-xiuno-alert-error">' . ddys_open_h($message) . '</div>', $args);
}

function ddys_open_render_empty($message, $args = array())
{
    return ddys_open_wrap('<div class="ddys-xiuno-empty">' . ddys_open_h($message) . '</div>', $args);
}

function ddys_open_item_url($item)
{
    $settings = ddys_open_settings();
    $url = ddys_open_value($item, array('url', 'link', 'href'), '');
    if ($url !== '' && preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if ($url !== '' && substr($url, 0, 1) === '/') {
        return rtrim($settings['site_base_url'], '/') . $url;
    }
    $slug = ddys_open_value($item, array('slug'), '');
    if ($slug !== '') {
        return rtrim($settings['site_base_url'], '/') . '/movie/' . rawurlencode($slug);
    }
    return '';
}

function ddys_open_render_card($item, $settings)
{
    if (!is_array($item)) {
        return '';
    }
    $title = ddys_open_value($item, array('title', 'name', 'cn_name', 'username'), 'Untitled');
    $poster = ddys_open_safe_media_url(ddys_open_value($item, array('poster', 'cover', 'image', 'avatar'), ''));
    $url = ddys_open_item_url($item);
    $meta = array();
    foreach (array('year', 'type', 'type_code', 'region', 'quality', 'episode') as $key) {
        if (!empty($item[$key])) {
            $meta[] = $item[$key];
        }
    }
    if (!empty($item['rating'])) {
        $meta[] = '评分 ' . $item['rating'];
    }
    $summary = ddys_open_value($item, array('description', 'intro', 'summary', 'note', 'content'), '');

    $html = '<article class="ddys-xiuno-card">';
    if ($poster !== '') {
        $html .= '<div class="ddys-xiuno-poster"><img src="' . ddys_open_attr($poster) . '" alt="' . ddys_open_attr($title) . '" loading="lazy"></div>';
    }
    $html .= '<div class="ddys-xiuno-card-body">';
    $html .= '<h3 class="ddys-xiuno-card-title">';
    if ($url !== '' && !empty($settings['show_source_link'])) {
        $html .= '<a href="' . ddys_open_attr($url) . '" target="' . ddys_open_attr($settings['target']) . '" rel="noopener">' . ddys_open_h($title) . '</a>';
    } else {
        $html .= ddys_open_h($title);
    }
    $html .= '</h3>';
    if (!empty($meta)) {
        $html .= '<div class="ddys-xiuno-meta">' . ddys_open_h(implode(' / ', $meta)) . '</div>';
    }
    if ($summary !== '') {
        $html .= '<div class="ddys-xiuno-summary">' . ddys_open_h(ddys_open_substr(strip_tags((string)$summary), 0, 150)) . '</div>';
    }
    $html .= '</div></article>';
    return $html;
}

function ddys_open_render_list($payload, $args = array())
{
    if (ddys_open_is_error($payload)) {
        return ddys_open_render_error($payload, $args);
    }
    $items = ddys_open_to_list(ddys_open_payload_data($payload));
    if (empty($items)) {
        return ddys_open_render_empty('暂无低端影视内容。', $args);
    }
    $settings = ddys_open_settings();
    $html = '<div class="ddys-xiuno-items">';
    foreach ($items as $item) {
        $html .= ddys_open_render_card($item, $settings);
    }
    $html .= '</div>' . ddys_open_render_pagination_meta(ddys_open_payload_meta($payload));
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_detail($payload, $args = array())
{
    if (ddys_open_is_error($payload)) {
        return ddys_open_render_error($payload, $args);
    }
    $data = ddys_open_payload_data($payload);
    if (!is_array($data)) {
        return ddys_open_render_empty('暂无详情。', $args);
    }
    $settings = ddys_open_settings();
    $html = '<div class="ddys-xiuno-detail">';
    $html .= ddys_open_render_card($data, $settings);
    $intro = ddys_open_value($data, array('intro', 'description', 'summary', 'note', 'content'), '');
    if ($intro !== '') {
        $html .= '<div class="ddys-xiuno-description">' . nl2br(ddys_open_h($intro)) . '</div>';
    }
    if (!empty($data['movies']) && is_array($data['movies'])) {
        $html .= '<h3>影片</h3><div class="ddys-xiuno-items">';
        foreach ($data['movies'] as $item) {
            $html .= ddys_open_render_card($item, $settings);
        }
        $html .= '</div>';
    }
    if (!empty($data['resources']) || !empty($data['sources']) || !empty($data['online']) || !empty($data['download'])) {
        $html .= ddys_open_render_sources(array('data' => $data), $args, TRUE);
    }
    $html .= '</div>';
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_sources($payload, $args = array(), $inner = FALSE)
{
    if (ddys_open_is_error($payload)) {
        return ddys_open_render_error($payload, $args);
    }
    $data = ddys_open_payload_data($payload);
    if (!is_array($data)) {
        return ddys_open_render_empty('暂无资源。', $args);
    }
    $groups = array();
    if (isset($data['online']) || isset($data['download'])) {
        if (!empty($data['online'])) {
            $groups['在线播放'] = $data['online'];
        }
        if (!empty($data['download'])) {
            $groups['下载资源'] = $data['download'];
        }
    } elseif (isset($data['resources'])) {
        $groups['资源'] = $data['resources'];
    } elseif (isset($data['sources'])) {
        $groups['资源'] = $data['sources'];
    } else {
        $groups = ddys_open_is_assoc($data) ? $data : array('资源' => $data);
    }

    $html = '<div class="ddys-xiuno-sources">';
    foreach ($groups as $name => $resources) {
        if (!is_array($resources)) {
            continue;
        }
        $html .= '<section class="ddys-xiuno-source-group"><h3>' . ddys_open_h($name) . '</h3>';
        foreach ($resources as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            $title = ddys_open_value($resource, array('title', 'name', 'label', 'download_type', 'type'), '资源');
            $url = ddys_open_value($resource, array('url', 'link', 'href'), '');
            $html .= '<p class="ddys-xiuno-resource">';
            $html .= ddys_open_render_resource_links($title, $url);
            $html .= '</p>';
        }
        $html .= '</section>';
    }
    $html .= '</div>';
    return $inner ? $html : ddys_open_wrap($html, $args);
}

function ddys_open_render_resource_links($title, $url)
{
    if ($url === '') {
        return ddys_open_h($title);
    }
    $parts = explode('#', $url);
    $links = array();
    foreach ($parts as $index => $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $label = $title;
        $href = $part;
        if (strpos($part, '$') !== FALSE) {
            $pair = explode('$', $part, 2);
            $label = $pair[0] !== '' ? $pair[0] : $title;
            $href = isset($pair[1]) ? $pair[1] : '';
        } elseif (count($parts) > 1) {
            $label = $title . ' ' . ($index + 1);
        }
        if (preg_match('#^(https?:|magnet:|ed2k:|thunder:)#i', $href)) {
            $links[] = '<a href="' . ddys_open_attr($href) . '" target="_blank" rel="noopener">' . ddys_open_h($label) . '</a>';
        }
    }
    return empty($links) ? ddys_open_h($title) : implode(' ', $links);
}

function ddys_open_render_calendar($payload, $args = array())
{
    if (ddys_open_is_error($payload)) {
        return ddys_open_render_error($payload, $args);
    }
    $data = ddys_open_payload_data($payload);
    $days = isset($data['days']) && is_array($data['days']) ? $data['days'] : $data;
    if (!is_array($days)) {
        return ddys_open_render_list($payload, $args);
    }
    $settings = ddys_open_settings();
    $html = '<div class="ddys-xiuno-calendar">';
    foreach ($days as $day => $items) {
        if (is_array($items) && isset($items['shows']) && is_array($items['shows'])) {
            $items = $items['shows'];
        }
        $html .= '<section class="ddys-xiuno-calendar-day"><h3>' . ddys_open_h($day) . '</h3><div class="ddys-xiuno-items">';
        if (is_array($items)) {
            foreach ($items as $item) {
                $html .= ddys_open_render_card($item, $settings);
            }
        }
        $html .= '</div></section>';
    }
    $html .= '</div>';
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_dictionary($payload, $args = array())
{
    if (ddys_open_is_error($payload)) {
        return ddys_open_render_error($payload, $args);
    }
    $items = ddys_open_to_list(ddys_open_payload_data($payload));
    if (empty($items)) {
        return ddys_open_render_empty('暂无字典数据。', $args);
    }
    $html = '<div class="ddys-xiuno-tags">';
    foreach ($items as $item) {
        $label = is_array($item) ? ddys_open_value($item, array('name', 'title', 'label', 'value'), '') : $item;
        $code = is_array($item) ? ddys_open_value($item, array('code', 'slug', 'id'), '') : '';
        if ($label !== '') {
            $html .= '<span>' . ddys_open_h($label) . ($code !== '' ? ' <code>' . ddys_open_h($code) . '</code>' : '') . '</span>';
        }
    }
    $html .= '</div>';
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_search($args = array())
{
    $q = ddys_open_get('q', ddys_open_get('ddys_q', isset($args['q']) ? $args['q'] : ''));
    $type = ddys_open_get('type', ddys_open_get('ddys_type', isset($args['type']) ? $args['type'] : 'movie'));
    $type = ddys_open_choice($type, array('movie', 'share', 'request'), 'movie');
    $html = '<form class="ddys-xiuno-search" method="get" action="' . ddys_open_attr(ddys_open_page_url('search')) . '">';
    $html .= '<input type="search" name="q" value="' . ddys_open_attr($q) . '" placeholder="搜索低端影视">';
    $html .= '<select name="type"><option value="movie"' . ($type === 'movie' ? ' selected' : '') . '>影片</option><option value="share"' . ($type === 'share' ? ' selected' : '') . '>分享</option><option value="request"' . ($type === 'request' ? ' selected' : '') . '>求片</option></select>';
    $html .= '<button type="submit">搜索</button></form>';
    if ($q !== '') {
        $payload = ddys_open_api_get('/search', array('q' => $q, 'type' => $type, 'per_page' => isset($args['per_page']) ? $args['per_page'] : 12), array());
        $html .= ddys_open_render_list($payload, $args);
    }
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_request_form($args = array())
{
    $settings = ddys_open_settings();
    if (empty($settings['enable_request_form'])) {
        return ddys_open_render_empty('求片表单未启用。', $args);
    }
    $html = '<form class="ddys-xiuno-request-form" method="post" action="' . ddys_open_attr(ddys_open_endpoint_url('request')) . '" data-ddys-xiuno-request-form>';
    $html .= '<input type="hidden" name="ddys_nonce" value="' . ddys_open_attr(ddys_open_nonce()) . '">';
    $html .= '<label>片名<input type="text" name="title" maxlength="255" required></label>';
    $html .= '<label>年份<input type="number" name="year" min="1900" max="2099"></label>';
    $html .= '<label>类型<select name="type"><option value=""></option><option value="movie">电影</option><option value="series">剧集</option><option value="variety">综艺</option><option value="anime">动漫</option></select></label>';
    $html .= '<label>豆瓣 ID<input type="text" name="douban_id" maxlength="30"></label>';
    $html .= '<label>IMDb ID<input type="text" name="imdb_id" maxlength="30"></label>';
    $html .= '<label>备注<textarea name="description" maxlength="1000"></textarea></label>';
    $html .= '<button type="submit">提交求片</button><p class="ddys-xiuno-status" role="status"></p></form>';
    return ddys_open_wrap($html, $args);
}

function ddys_open_render_pagination_meta($meta)
{
    if (!is_array($meta) || empty($meta['total'])) {
        return '';
    }
    $page = isset($meta['page']) ? (int)$meta['page'] : 1;
    return '<div class="ddys-xiuno-page-meta">第 ' . ddys_open_h($page) . ' 页，共 ' . ddys_open_h($meta['total']) . ' 条</div>';
}

function ddys_open_frontend_assets()
{
    static $printed = FALSE;
    if ($printed) {
        return '';
    }
    $printed = TRUE;
    $settings = ddys_open_settings();
    $base = ddys_open_plugin_url();
    $html = '';
    if (!empty($settings['enable_styles'])) {
        $html .= "\n" . '<link rel="stylesheet" href="' . ddys_open_attr($base . 'static/css/frontend.css?v=' . DDYS_OPEN_XIUNO_VERSION) . '">';
    }
    $html .= "\n" . '<script defer src="' . ddys_open_attr($base . 'static/js/frontend.js?v=' . DDYS_OPEN_XIUNO_VERSION) . '"></script>';
    return $html;
}

function ddys_open_nav_item()
{
    $settings = ddys_open_settings();
    if (empty($settings['show_nav'])) {
        return '';
    }
    return '<li class="nav-item ddys-xiuno-nav" data-active="ddys"><a class="nav-link" href="' . ddys_open_attr(ddys_open_page_url('latest')) . '">低端影视</a></li>';
}

function ddys_open_render_page($view, $params = array())
{
    $view = ddys_open_choice($view, array('latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'collection', 'requests'), 'latest');
    if ($view === 'hot') {
        return ddys_open_render_shortcode('ddys_hot', array('limit' => isset($params['limit']) ? $params['limit'] : 12));
    }
    if ($view === 'search') {
        return ddys_open_render_shortcode('ddys_search', array('q' => isset($params['q']) ? $params['q'] : '', 'type' => isset($params['type']) ? $params['type'] : 'movie'));
    }
    if ($view === 'calendar') {
        return ddys_open_render_shortcode('ddys_calendar', array('year' => isset($params['year']) ? $params['year'] : '', 'month' => isset($params['month']) ? $params['month'] : ''));
    }
    if ($view === 'movie') {
        return ddys_open_render_shortcode('ddys_movie', array('slug' => isset($params['slug']) ? $params['slug'] : ''));
    }
    if ($view === 'collections') {
        return ddys_open_render_shortcode('ddys_collections', array('page' => isset($params['page']) ? $params['page'] : 1));
    }
    if ($view === 'collection') {
        return ddys_open_render_shortcode('ddys_collection', array('slug' => isset($params['slug']) ? $params['slug'] : ''));
    }
    if ($view === 'requests') {
        return ddys_open_render_shortcode('ddys_requests', array('page' => isset($params['page']) ? $params['page'] : 1));
    }
    return ddys_open_render_shortcode('ddys_latest', array('limit' => isset($params['limit']) ? $params['limit'] : 12));
}

function ddys_open_page_tabs($active)
{
    $tabs = array(
        'latest' => '最新',
        'hot' => '热门',
        'search' => '搜索',
        'calendar' => '日历',
        'collections' => '片单',
        'requests' => '求片'
    );
    $html = '<nav class="ddys-xiuno-tabs">';
    foreach ($tabs as $view => $label) {
        $html .= '<a class="' . ($active === $view ? 'active' : '') . '" href="' . ddys_open_attr(ddys_open_page_url($view)) . '">' . ddys_open_h($label) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}
