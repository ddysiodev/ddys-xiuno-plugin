<?php

!defined('DEBUG') AND exit('Access Denied.');

function ddys_open_shortcodes()
{
    return array(
        'ddys_movies',
        'ddys_latest',
        'ddys_hot',
        'ddys_search',
        'ddys_suggest',
        'ddys_calendar',
        'ddys_movie',
        'ddys_sources',
        'ddys_related',
        'ddys_comments',
        'ddys_collections',
        'ddys_collection',
        'ddys_shares',
        'ddys_share',
        'ddys_requests',
        'ddys_activities',
        'ddys_user',
        'ddys_types',
        'ddys_genres',
        'ddys_regions',
        'ddys_request_form'
    );
}

function ddys_open_start_output_buffer()
{
    static $started = FALSE;
    if ($started || !function_exists('ob_start')) {
        return;
    }
    $started = TRUE;
    ob_start('ddys_open_parse_shortcodes');
}

function ddys_open_parse_shortcodes($content)
{
    if (strpos($content, '[ddys_') === FALSE) {
        return $content;
    }
    return preg_replace_callback('/\[(ddys_[a-z_]+)([^\]]*)\]/i', 'ddys_open_shortcode_callback', $content);
}

function ddys_open_shortcode_callback($matches)
{
    $tag = strtolower($matches[1]);
    $atts = ddys_open_parse_atts(isset($matches[2]) ? $matches[2] : '');
    return ddys_open_render_shortcode($tag, $atts);
}

function ddys_open_parse_atts($text)
{
    $atts = array();
    if (preg_match_all('/([a-zA-Z0-9_:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $value = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : $match[4]);
            $atts[strtolower($match[1])] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        }
    }
    return $atts;
}

function ddys_open_common_atts($atts)
{
    $settings = ddys_open_settings();
    $defaults = array(
        'layout' => $settings['layout'],
        'theme' => $settings['theme'],
        'columns' => $settings['columns'],
        'target' => $settings['target'],
        'cache_ttl' => '',
        'show_poster' => '1',
        'show_rating' => '1'
    );
    return array_merge($defaults, $atts);
}

function ddys_open_cache_options($atts)
{
    if (isset($atts['cache_ttl']) && $atts['cache_ttl'] !== '') {
        return array('cache_ttl' => (int)$atts['cache_ttl']);
    }
    return array();
}

function ddys_open_render_get($path, $params, $atts, $renderer)
{
    $payload = ddys_open_api_get($path, $params, ddys_open_cache_options($atts));
    if ($renderer === 'detail') {
        return ddys_open_render_detail($payload, $atts);
    }
    if ($renderer === 'sources') {
        return ddys_open_render_sources($payload, $atts);
    }
    if ($renderer === 'calendar') {
        return ddys_open_render_calendar($payload, $atts);
    }
    if ($renderer === 'dictionary') {
        return ddys_open_render_dictionary($payload, $atts);
    }
    return ddys_open_render_list($payload, $atts);
}

function ddys_open_render_shortcode($tag, $atts = array())
{
    $settings = ddys_open_settings();
    $atts = ddys_open_common_atts($atts);

    if ($tag === 'ddys_movies') {
        $atts = array_merge(array('type' => '', 'genre' => '', 'region' => '', 'year' => '', 'sort' => 'latest', 'page' => 1, 'per_page' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/movies', ddys_open_build_query($atts, array('type', 'genre', 'region', 'year', 'sort', 'page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_latest') {
        $atts = array_merge(array('type' => '', 'genre' => '', 'region' => '', 'year' => '', 'limit' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/latest', ddys_open_build_query($atts, array('type', 'genre', 'region', 'year', 'limit')), $atts, 'list');
    }
    if ($tag === 'ddys_hot') {
        $atts = array_merge(array('type' => '', 'genre' => '', 'region' => '', 'limit' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/hot', ddys_open_build_query($atts, array('type', 'genre', 'region', 'limit')), $atts, 'list');
    }
    if ($tag === 'ddys_search') {
        return ddys_open_render_search($atts);
    }
    if ($tag === 'ddys_suggest') {
        $atts = array_merge(array('q' => '', 'limit' => 8), $atts);
        return ddys_open_render_get('/suggest', ddys_open_build_query($atts, array('q', 'limit')), $atts, 'list');
    }
    if ($tag === 'ddys_calendar') {
        $atts = array_merge(array('year' => '', 'month' => ''), $atts);
        return ddys_open_render_get('/calendar', ddys_open_build_query($atts, array('year', 'month')), $atts, 'calendar');
    }
    if ($tag === 'ddys_movie') {
        $slug = isset($atts['slug']) ? ddys_open_scalar($atts['slug']) : '';
        if ($slug === '') {
            return ddys_open_render_error(ddys_open_error('缺少影片 slug。', 400), $atts);
        }
        return ddys_open_render_get('/movies/' . rawurlencode($slug), array(), $atts, 'detail');
    }
    if ($tag === 'ddys_sources') {
        $slug = isset($atts['slug']) ? ddys_open_scalar($atts['slug']) : '';
        if ($slug === '') {
            return ddys_open_render_error(ddys_open_error('缺少影片 slug。', 400), $atts);
        }
        return ddys_open_render_get('/movies/' . rawurlencode($slug) . '/sources', array(), $atts, 'sources');
    }
    if ($tag === 'ddys_related') {
        $slug = isset($atts['slug']) ? ddys_open_scalar($atts['slug']) : '';
        if ($slug === '') {
            return ddys_open_render_error(ddys_open_error('缺少影片 slug。', 400), $atts);
        }
        return ddys_open_render_get('/movies/' . rawurlencode($slug) . '/related', array(), $atts, 'list');
    }
    if ($tag === 'ddys_comments') {
        $slug = isset($atts['slug']) ? ddys_open_scalar($atts['slug']) : '';
        if ($slug === '') {
            return ddys_open_render_error(ddys_open_error('缺少影片 slug。', 400), $atts);
        }
        return ddys_open_render_get('/movies/' . rawurlencode($slug) . '/comments', ddys_open_build_query($atts, array('page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_collections') {
        $atts = array_merge(array('page' => 1, 'per_page' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/collections', ddys_open_build_query($atts, array('page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_collection') {
        $slug = isset($atts['slug']) ? ddys_open_scalar($atts['slug']) : '';
        if ($slug === '') {
            return ddys_open_render_error(ddys_open_error('缺少片单 slug。', 400), $atts);
        }
        return ddys_open_render_get('/collections/' . rawurlencode($slug), array(), $atts, 'detail');
    }
    if ($tag === 'ddys_shares') {
        $atts = array_merge(array('page' => 1, 'per_page' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/shares', ddys_open_build_query($atts, array('page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_share') {
        $id = isset($atts['id']) ? (int)$atts['id'] : 0;
        if ($id <= 0) {
            return ddys_open_render_error(ddys_open_error('缺少分享 ID。', 400), $atts);
        }
        return ddys_open_render_get('/shares/' . $id, array(), $atts, 'detail');
    }
    if ($tag === 'ddys_requests') {
        $atts = array_merge(array('page' => 1, 'per_page' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/requests', ddys_open_build_query($atts, array('page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_activities') {
        $atts = array_merge(array('page' => 1, 'per_page' => $settings['default_limit']), $atts);
        return ddys_open_render_get('/activities', ddys_open_build_query($atts, array('page', 'per_page')), $atts, 'list');
    }
    if ($tag === 'ddys_user') {
        $username = isset($atts['username']) ? ddys_open_scalar($atts['username']) : '';
        if ($username === '') {
            return ddys_open_render_error(ddys_open_error('缺少用户名。', 400), $atts);
        }
        return ddys_open_render_get('/user/' . rawurlencode($username), array(), $atts, 'detail');
    }
    if ($tag === 'ddys_types') {
        return ddys_open_render_get('/types', array(), $atts, 'dictionary');
    }
    if ($tag === 'ddys_genres') {
        return ddys_open_render_get('/genres', array(), $atts, 'dictionary');
    }
    if ($tag === 'ddys_regions') {
        return ddys_open_render_get('/regions', array(), $atts, 'dictionary');
    }
    if ($tag === 'ddys_request_form') {
        return ddys_open_render_request_form($atts);
    }
    return '';
}
