<?php

!defined('DEBUG') AND exit('Access Denied.');

function ddys_open_cache_dir()
{
    global $conf;
    $base = isset($conf['tmp_path']) ? $conf['tmp_path'] : (APP_PATH . 'tmp/');
    $dir = rtrim($base, '/\\') . '/ddys_open_cache/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, TRUE);
    }
    return $dir;
}

function ddys_open_cache_key($method, $base, $path, $params)
{
    ksort($params);
    return sha1(strtoupper($method) . '|' . $base . '|' . $path . '|' . json_encode($params));
}

function ddys_open_cache_get($key)
{
    $file = ddys_open_cache_dir() . $key . '.json';
    if (!is_file($file)) {
        return FALSE;
    }
    $json = json_decode((string)@file_get_contents($file), TRUE);
    if (!is_array($json) || empty($json['expires']) || time() > (int)$json['expires']) {
        @unlink($file);
        return FALSE;
    }
    return isset($json['payload']) ? $json['payload'] : FALSE;
}

function ddys_open_cache_set($key, $payload, $ttl)
{
    $ttl = (int)$ttl;
    if ($ttl <= 0) {
        return FALSE;
    }
    $file = ddys_open_cache_dir() . $key . '.json';
    $data = array(
        'expires' => time() + $ttl,
        'payload' => $payload
    );
    return @file_put_contents($file, json_encode($data)) !== FALSE;
}

function ddys_open_cache_clear()
{
    $dir = ddys_open_cache_dir();
    $count = 0;
    foreach ((array)glob($dir . '*.json') as $file) {
        if (is_file($file) && @unlink($file)) {
            $count++;
        }
    }
    return $count;
}

function ddys_open_cache_status()
{
    $dir = ddys_open_cache_dir();
    $count = 0;
    $bytes = 0;
    $expired = 0;
    foreach ((array)glob($dir . '*.json') as $file) {
        if (!is_file($file)) {
            continue;
        }
        $count++;
        $bytes += filesize($file);
        $json = json_decode((string)@file_get_contents($file), TRUE);
        if (!is_array($json) || empty($json['expires']) || time() > (int)$json['expires']) {
            $expired++;
        }
    }
    return array(
        'dir' => $dir,
        'count' => $count,
        'bytes' => $bytes,
        'expired' => $expired,
        'writable' => is_dir($dir) && is_writable($dir)
    );
}

function ddys_open_check_rate_limit($scope, $key, $interval)
{
    $interval = max(10, (int)$interval);
    $file = ddys_open_cache_dir() . 'rate-' . sha1($scope . '|' . $key) . '.json';
    $last = 0;
    if (is_file($file)) {
        $json = json_decode((string)@file_get_contents($file), TRUE);
        if (is_array($json) && !empty($json['time'])) {
            $last = (int)$json['time'];
        }
    }
    if ($last > 0 && time() - $last < $interval) {
        return FALSE;
    }
    @file_put_contents($file, json_encode(array('time' => time())));
    return TRUE;
}
