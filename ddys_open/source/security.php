<?php

!defined('DEBUG') AND exit('Access Denied.');

function ddys_open_defaults()
{
    return array(
        'api_base_url' => DDYS_OPEN_XIUNO_API_DEFAULT,
        'site_base_url' => DDYS_OPEN_XIUNO_SITE_DEFAULT,
        'api_key' => '',
        'timeout' => 12,
        'default_cache_ttl' => 300,
        'dictionary_cache_ttl' => 86400,
        'fresh_cache_ttl' => 300,
        'list_cache_ttl' => 600,
        'detail_cache_ttl' => 1800,
        'community_cache_ttl' => 120,
        'theme' => 'auto',
        'layout' => 'grid',
        'columns' => 4,
        'target' => '_blank',
        'show_source_link' => 1,
        'enable_styles' => 1,
        'enable_request_form' => 0,
        'show_nav' => 1,
        'enable_pretty_urls' => 0,
        'pretty_base_path' => 'ddys',
        'default_limit' => 12,
        'request_interval' => 60,
        'debug' => 0
    );
}

function ddys_open_settings()
{
    $defaults = ddys_open_defaults();
    $saved = array();
    if (function_exists('kv_get')) {
        $value = kv_get(DDYS_OPEN_XIUNO_SETTING_KEY);
        if (is_array($value)) {
            $saved = $value;
        }
    }
    $settings = array_merge($defaults, $saved);
    return ddys_open_normalize_settings($settings);
}

function ddys_open_normalize_settings($settings)
{
    $settings['api_base_url'] = ddys_open_normalize_base_url($settings['api_base_url'], DDYS_OPEN_XIUNO_API_DEFAULT);
    $settings['site_base_url'] = ddys_open_normalize_base_url($settings['site_base_url'], DDYS_OPEN_XIUNO_SITE_DEFAULT);
    $settings['api_key'] = trim((string)$settings['api_key']);
    $settings['timeout'] = ddys_open_int_range($settings['timeout'], 12, 1, 30);
    $settings['default_cache_ttl'] = ddys_open_int_range($settings['default_cache_ttl'], 300, 0, 604800);
    $settings['dictionary_cache_ttl'] = ddys_open_int_range($settings['dictionary_cache_ttl'], 86400, 0, 604800);
    $settings['fresh_cache_ttl'] = ddys_open_int_range($settings['fresh_cache_ttl'], 300, 0, 604800);
    $settings['list_cache_ttl'] = ddys_open_int_range($settings['list_cache_ttl'], 600, 0, 604800);
    $settings['detail_cache_ttl'] = ddys_open_int_range($settings['detail_cache_ttl'], 1800, 0, 604800);
    $settings['community_cache_ttl'] = ddys_open_int_range($settings['community_cache_ttl'], 120, 0, 604800);
    $settings['theme'] = ddys_open_choice($settings['theme'], array('auto', 'light', 'dark'), 'auto');
    $settings['layout'] = ddys_open_choice($settings['layout'], array('grid', 'list', 'compact'), 'grid');
    $settings['columns'] = ddys_open_int_range($settings['columns'], 4, 1, 6);
    $settings['target'] = ddys_open_choice($settings['target'], array('_blank', '_self'), '_blank');
    $settings['default_limit'] = ddys_open_int_range($settings['default_limit'], 12, 1, 50);
    $settings['request_interval'] = ddys_open_int_range($settings['request_interval'], 60, 10, 3600);
    foreach (array('show_source_link', 'enable_styles', 'enable_request_form', 'show_nav', 'enable_pretty_urls', 'debug') as $key) {
        $settings[$key] = ddys_open_bool($settings[$key]) ? 1 : 0;
    }
    $settings['pretty_base_path'] = ddys_open_normalize_base_path($settings['pretty_base_path'], 'ddys');
    return $settings;
}

function ddys_open_save_settings($settings)
{
    if (!function_exists('kv_set')) {
        return FALSE;
    }
    return kv_set(DDYS_OPEN_XIUNO_SETTING_KEY, ddys_open_normalize_settings($settings));
}

function ddys_open_reserved_routes()
{
    return array('index', 'thread', 'forum', 'user', 'my', 'attach', 'post', 'mod', 'browser', 'plugin', 'admin');
}

function ddys_open_route_base()
{
    $settings = ddys_open_settings();
    return $settings['pretty_base_path'];
}

function ddys_open_is_plugin_route($route)
{
    $route = ddys_open_scalar($route);
    return $route === 'ddys' || $route === ddys_open_route_base();
}

function ddys_open_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ddys_open_attr($value)
{
    return ddys_open_h($value);
}

function ddys_open_get($key, $default = '')
{
    if (isset($_GET[$key])) {
        return ddys_open_scalar($_GET[$key], $default);
    }
    return $default;
}

function ddys_open_post($key, $default = '')
{
    if (isset($_POST[$key])) {
        return ddys_open_scalar($_POST[$key], $default);
    }
    return $default;
}

function ddys_open_scalar($value, $default = '')
{
    if (is_array($value) || is_object($value)) {
        return $default;
    }
    return trim(str_replace("\0", '', (string)$value));
}

function ddys_open_bool($value)
{
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower(trim((string)$value)), array('1', 'true', 'yes', 'on'), TRUE);
}

function ddys_open_int_range($value, $fallback, $min, $max)
{
    if (!is_numeric($value)) {
        return $fallback;
    }
    $value = (int)$value;
    if ($value < $min) {
        return $min;
    }
    if ($value > $max) {
        return $max;
    }
    return $value;
}

function ddys_open_choice($value, $allowed, $fallback)
{
    $value = strtolower(trim((string)$value));
    return in_array($value, $allowed, TRUE) ? $value : $fallback;
}

function ddys_open_normalize_base_url($value, $fallback)
{
    $value = trim((string)$value);
    if ($value === '' || !preg_match('#^https?://#i', $value)) {
        return $fallback;
    }
    $parts = parse_url($value);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) {
        return $fallback;
    }
    return rtrim($value, '/');
}

function ddys_open_normalize_base_path($value, $fallback)
{
    $value = trim((string)$value);
    $value = trim($value, "/ \t\r\n");
    $lower = strtolower($value);
    if ($value === '' || strpos($value, '..') !== FALSE || !preg_match('#^[a-zA-Z0-9_]+$#', $value) || in_array($lower, ddys_open_reserved_routes(), TRUE)) {
        return $fallback;
    }
    return $value;
}

function ddys_open_normalize_query_value($key, $value)
{
    $value = ddys_open_scalar($value);
    if ($value === '') {
        return '';
    }
    if ($key === 'limit' || $key === 'per_page') {
        return ddys_open_int_range($value, 12, 1, 50);
    }
    if ($key === 'page') {
        return ddys_open_int_range($value, 1, 1, 999);
    }
    if ($key === 'year') {
        return ddys_open_int_range($value, 0, 0, 2099);
    }
    if ($key === 'month') {
        return ddys_open_int_range($value, 0, 0, 12);
    }
    return $value;
}

function ddys_open_route_tail($start, $fallback = '')
{
    $parts = array();
    for ($i = (int)$start; $i < 20; $i++) {
        if (!isset($_REQUEST[$i])) {
            break;
        }
        $part = ddys_open_scalar($_REQUEST[$i]);
        if ($part !== '') {
            $parts[] = $part;
        }
    }
    if (empty($parts)) {
        return $fallback;
    }
    $tail = implode('-', $parts);
    return preg_match('#^[a-zA-Z0-9_\-]+$#', $tail) ? $tail : $fallback;
}

function ddys_open_build_query($source, $keys)
{
    $query = array();
    foreach ($keys as $key) {
        if (isset($source[$key]) && ddys_open_scalar($source[$key]) !== '') {
            $query[$key] = ddys_open_normalize_query_value($key, $source[$key]);
        }
    }
    return $query;
}

function ddys_open_site_root()
{
    if (function_exists('http_url_path')) {
        return rtrim(http_url_path(), '/') . '/';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $path = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    return $host === '' ? './' : $scheme . '://' . $host . rtrim(str_replace('\\', '/', $path), '/') . '/';
}

function ddys_open_plugin_url()
{
    return ddys_open_site_root() . 'plugin/ddys_open/';
}

function ddys_open_page_url($view = 'latest', $params = array())
{
    $base_route = ddys_open_route_base();
    $view = ddys_open_choice($view, array('latest', 'hot', 'search', 'calendar', 'movie', 'collections', 'collection', 'requests'), 'latest');
    if (function_exists('url')) {
        if ($view === 'latest') {
            $url = url($base_route);
        } elseif ($view === 'movie') {
            $slug = isset($params['slug']) ? ddys_open_scalar($params['slug']) : '';
            $url = $slug !== '' ? url($base_route . '-movie-' . $slug) : url($base_route);
            unset($params['slug']);
        } elseif ($view === 'collection') {
            $slug = isset($params['slug']) ? ddys_open_scalar($params['slug']) : '';
            $url = $slug !== '' ? url($base_route . '-collection-' . $slug) : url($base_route . '-collections');
            unset($params['slug']);
        } else {
            $url = url($base_route . '-' . $view);
        }
        return ddys_open_append_query($url, $params);
    }
    $fallback = $view === 'latest' ? ('?' . $base_route . '.htm') : ('?' . $base_route . '-' . $view . '.htm');
    return ddys_open_append_query($fallback, $params);
}

function ddys_open_endpoint_url($endpoint)
{
    $base_route = ddys_open_route_base();
    if (function_exists('url')) {
        if ($endpoint === 'api') {
            return url($base_route . '-api');
        }
        if ($endpoint === 'request') {
            return url($base_route . '-request-submit');
        }
    }
    if ($endpoint === 'request') {
        return '?' . $base_route . '-request-submit.htm';
    }
    return '?' . $base_route . '-' . $endpoint . '.htm';
}

function ddys_open_admin_front_url($url)
{
    if (preg_match('#^(https?:)?//#i', $url) || substr($url, 0, 1) === '/') {
        return $url;
    }
    return '../' . ltrim($url, './');
}

function ddys_open_append_query($url, $params)
{
    $clean = array();
    foreach ((array)$params as $key => $value) {
        $value = ddys_open_scalar($value);
        if ($value !== '') {
            $clean[$key] = $value;
        }
    }
    if (empty($clean)) {
        return $url;
    }
    return $url . (strpos($url, '?') === FALSE ? '?' : '&') . http_build_query($clean, '', '&');
}

function ddys_open_nonce()
{
    $seed = ddys_open_nonce_seed();
    $slot = floor(time() / 3600);
    return ddys_open_hash($seed . '|' . $slot);
}

function ddys_open_verify_nonce($nonce)
{
    $seed = ddys_open_nonce_seed();
    $slot = floor(time() / 3600);
    return ddys_open_hash_equals(ddys_open_hash($seed . '|' . $slot), $nonce)
        || ddys_open_hash_equals(ddys_open_hash($seed . '|' . ($slot - 1)), $nonce);
}

function ddys_open_nonce_seed()
{
    global $sid, $conf;
    $auth = isset($conf['auth_key']) ? $conf['auth_key'] : 'ddys-open-xiuno';
    return $auth . '|' . (isset($sid) ? $sid : '') . '|' . ddys_open_user_ip();
}

function ddys_open_hash($value)
{
    if (function_exists('hash_hmac')) {
        global $conf;
        $key = isset($conf['auth_key']) ? $conf['auth_key'] : 'ddys-open-xiuno';
        return hash_hmac('sha256', $value, $key);
    }
    return sha1($value);
}

function ddys_open_hash_equals($known, $user)
{
    if (function_exists('hash_equals')) {
        return hash_equals((string)$known, (string)$user);
    }
    $known = (string)$known;
    $user = (string)$user;
    if (strlen($known) !== strlen($user)) {
        return FALSE;
    }
    $result = 0;
    for ($i = 0; $i < strlen($known); $i++) {
        $result |= ord($known[$i]) ^ ord($user[$i]);
    }
    return $result === 0;
}

function ddys_open_user_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function ddys_open_json_response($payload, $status = 200)
{
    if ($status === 200 && ddys_open_is_error($payload) && !empty($payload['status'])) {
        $status = ddys_open_int_range($payload['status'], 500, 400, 599);
    }
    if (!headers_sent()) {
        if (function_exists('http_response_code')) {
            http_response_code($status);
        }
        header('Content-Type: application/json; charset=utf-8', TRUE, $status);
    }
    echo json_encode($payload, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
    exit;
}

function ddys_open_error($message, $status = 0, $payload = array())
{
    return array(
        'ddys_error' => TRUE,
        'success' => FALSE,
        'message' => (string)$message,
        'status' => (int)$status,
        'payload' => $payload
    );
}

function ddys_open_is_error($value)
{
    return is_array($value) && !empty($value['ddys_error']);
}

function ddys_open_safe_media_url($value)
{
    $value = trim((string)$value);
    return preg_match('#^https?://#i', $value) ? $value : '';
}

function ddys_open_substr($value, $start, $length)
{
    $value = (string)$value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, $start, $length, 'UTF-8');
    }
    return substr($value, $start, $length);
}

function ddys_open_strlen($value)
{
    $value = (string)$value;
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    return strlen($value);
}
