<?php

!defined('DEBUG') AND exit('Access Denied.');

include_once APP_PATH . 'plugin/ddys_open/source/bootstrap.php';
ddys_open_bootstrap();

$message = '';
$message_type = 'success';
$settings = ddys_open_settings();
$op = ddys_open_get('op', '');

if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    if (!ddys_open_verify_nonce(ddys_open_post('ddys_nonce'))) {
        $message = '表单令牌无效，请刷新页面后重试。';
        $message_type = 'danger';
    } else {
        $data = array();
        foreach (array_keys(ddys_open_defaults()) as $key) {
            $data[$key] = ddys_open_post($key, isset($settings[$key]) ? $settings[$key] : '');
        }
        foreach (array('show_source_link', 'enable_styles', 'enable_request_form', 'show_nav', 'enable_pretty_urls', 'debug') as $key) {
            $data[$key] = ddys_open_post($key, '0') === '1' ? 1 : 0;
        }
        ddys_open_save_settings($data);
        $settings = ddys_open_settings();
        $message = '低端影视 API 配置已保存。';
    }
}

if ($op === 'clear' && ddys_open_verify_nonce(ddys_open_get('ddys_nonce'))) {
    $count = ddys_open_cache_clear();
    $message = '已清理缓存文件 ' . $count . ' 个。';
}

if ($op === 'test' && ddys_open_verify_nonce(ddys_open_get('ddys_nonce'))) {
    $result = ddys_open_api_get('/latest', array('limit' => 1), array('no_cache' => TRUE));
    if (ddys_open_is_error($result)) {
        $message = '连接测试失败：' . $result['message'];
        $message_type = 'danger';
    } else {
        $message = '连接测试成功，站点服务器可以访问低端影视 API。';
    }
}

$cache = ddys_open_cache_status();
$config_checks = ddys_open_admin_config_checks($settings, $cache);
$nonce = ddys_open_nonce();
$base_url = function_exists('url') ? url('plugin-setting-ddys_open') : '?plugin-setting-ddys_open.htm';
$proxy_example_url = ddys_open_append_query(ddys_open_endpoint_url('api'), array('route' => 'latest', 'limit' => 3));

include _include(ADMIN_PATH . 'view/htm/header.inc.htm');
?>
<link rel="stylesheet" href="../plugin/ddys_open/static/css/admin.css?v=<?php echo ddys_open_attr(DDYS_OPEN_XIUNO_VERSION); ?>">
<div class="row">
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-body">
        <div class="media">
          <img class="mr-3" src="../plugin/ddys_open/static/images/icon-32.png" width="32" height="32" alt="DDYS">
          <div class="media-body">
            <h4 class="mt-0 mb-1">低端影视 API</h4>
            <p class="text-muted mb-0">Xiuno BBS 官方扩展，提供前台页面、短代码、本地代理、缓存、后台诊断和求片表单。</p>
          </div>
        </div>
      </div>
    </div>

    <?php if ($message !== '') { ?>
      <div class="alert alert-<?php echo ddys_open_attr($message_type); ?>"><?php echo ddys_open_h($message); ?></div>
    <?php } ?>

    <div class="row">
      <div class="col-lg-8">
        <form method="post" action="<?php echo ddys_open_attr($base_url); ?>" class="card mb-3">
          <div class="card-header">基础配置</div>
          <div class="card-body">
            <input type="hidden" name="ddys_nonce" value="<?php echo ddys_open_attr($nonce); ?>">
            <?php echo ddys_open_admin_input('api_base_url', 'API Base URL', $settings['api_base_url'], 'https://ddys.io/api/v1'); ?>
            <?php echo ddys_open_admin_input('site_base_url', '来源站点 URL', $settings['site_base_url'], 'https://ddys.io'); ?>
            <?php echo ddys_open_admin_input('api_key', 'API Key', $settings['api_key'], '求片等写接口需要填写', 'password'); ?>
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_input('timeout', '请求超时秒数', $settings['timeout']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('default_limit', '默认数量', $settings['default_limit']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('request_interval', '求片间隔秒数', $settings['request_interval']); ?></div>
            </div>
          </div>
          <div class="card-header">缓存策略</div>
          <div class="card-body">
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_input('default_cache_ttl', '默认缓存 TTL', $settings['default_cache_ttl']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('dictionary_cache_ttl', '字典缓存 TTL', $settings['dictionary_cache_ttl']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('fresh_cache_ttl', '新鲜内容 TTL', $settings['fresh_cache_ttl']); ?></div>
            </div>
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_input('list_cache_ttl', '列表缓存 TTL', $settings['list_cache_ttl']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('detail_cache_ttl', '详情缓存 TTL', $settings['detail_cache_ttl']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_input('community_cache_ttl', '社区数据 TTL', $settings['community_cache_ttl']); ?></div>
            </div>
          </div>
          <div class="card-header">展示和入口</div>
          <div class="card-body">
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_select('theme', '主题', $settings['theme'], array('auto' => '自动', 'light' => '浅色', 'dark' => '深色')); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_select('layout', '布局', $settings['layout'], array('grid' => '网格', 'list' => '列表', 'compact' => '紧凑')); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_select('columns', '网格列数', $settings['columns'], array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6')); ?></div>
            </div>
            <?php echo ddys_open_admin_select('target', '来源链接打开方式', $settings['target'], array('_blank' => '新窗口', '_self' => '当前窗口')); ?>
            <?php echo ddys_open_admin_input('pretty_base_path', '前台基础路径', $settings['pretty_base_path'], '只支持英文字母、数字、下划线，且不能使用 Xiuno 核心路由'); ?>
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('show_source_link', '显示来源链接', $settings['show_source_link']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('enable_styles', '加载内置样式', $settings['enable_styles']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('show_nav', '显示导航入口', $settings['show_nav']); ?></div>
            </div>
            <div class="form-row">
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('enable_request_form', '启用求片表单', $settings['enable_request_form']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('enable_pretty_urls', '启用伪静态文档提示', $settings['enable_pretty_urls']); ?></div>
              <div class="col-md-4"><?php echo ddys_open_admin_checkbox('debug', '调试模式', $settings['debug']); ?></div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">保存配置</button>
            <a class="btn btn-secondary" href="<?php echo ddys_open_attr($base_url . (strpos($base_url, '?') === FALSE ? '?' : '&') . 'op=test&ddys_nonce=' . $nonce); ?>">测试连接</a>
            <a class="btn btn-secondary" href="<?php echo ddys_open_attr($base_url . (strpos($base_url, '?') === FALSE ? '?' : '&') . 'op=clear&ddys_nonce=' . $nonce); ?>">清理缓存</a>
            <a class="btn btn-outline-secondary" target="_blank" href="<?php echo ddys_open_attr(ddys_open_admin_front_url(ddys_open_page_url('latest'))); ?>">打开前台</a>
          </div>
        </form>
      </div>

      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-header">诊断</div>
          <div class="card-body">
            <p>缓存目录：<code><?php echo ddys_open_h($cache['dir']); ?></code></p>
            <p>缓存文件：<?php echo ddys_open_h($cache['count']); ?> 个，过期 <?php echo ddys_open_h($cache['expired']); ?> 个。</p>
            <p>可写状态：<?php echo $cache['writable'] ? '<span class="text-success">可写</span>' : '<span class="text-danger">不可写</span>'; ?></p>
            <p>前台入口：<code><?php echo ddys_open_h(ddys_open_page_url('latest')); ?></code></p>
            <p>代理入口：<code><?php echo ddys_open_h($proxy_example_url); ?></code></p>
            <p>求片提交：<code><?php echo ddys_open_h(ddys_open_endpoint_url('request')); ?></code></p>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">当前配置检查</div>
          <div class="card-body">
            <ul class="ddys-admin-checks">
              <?php foreach ($config_checks as $check) { ?>
                <li class="ddys-admin-check ddys-admin-check-<?php echo ddys_open_attr($check['status']); ?>">
                  <strong><?php echo ddys_open_h($check['label']); ?></strong>
                  <span><?php echo ddys_open_h($check['message']); ?></span>
                </li>
              <?php } ?>
            </ul>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">短代码生成器</div>
          <div class="card-body" data-ddys-xiuno-generator
            data-ddys-proxy-url="<?php echo ddys_open_attr(ddys_open_endpoint_url('api')); ?>"
            data-ddys-page-latest="<?php echo ddys_open_attr(ddys_open_page_url('latest')); ?>"
            data-ddys-page-hot="<?php echo ddys_open_attr(ddys_open_page_url('hot')); ?>"
            data-ddys-page-search="<?php echo ddys_open_attr(ddys_open_page_url('search')); ?>"
            data-ddys-page-calendar="<?php echo ddys_open_attr(ddys_open_page_url('calendar')); ?>"
            data-ddys-page-movie="<?php echo ddys_open_attr(ddys_open_page_url('movie', array('slug' => '__identity__'))); ?>"
            data-ddys-page-collections="<?php echo ddys_open_attr(ddys_open_page_url('collections')); ?>"
            data-ddys-page-collection="<?php echo ddys_open_attr(ddys_open_page_url('collection', array('slug' => '__identity__'))); ?>"
            data-ddys-page-requests="<?php echo ddys_open_attr(ddys_open_page_url('requests')); ?>">
            <div class="form-group">
              <label>输出类型</label>
              <select class="form-control" data-ddys-generator-kind>
                <option value="shortcode">短代码</option>
                <option value="page">前台页面链接</option>
                <option value="proxy">本地代理 URL</option>
              </select>
            </div>
            <div class="form-group">
              <label>组件</label>
              <select class="form-control" data-ddys-generator-type>
                <?php foreach (ddys_open_shortcodes() as $shortcode) { ?>
                  <option value="<?php echo ddys_open_attr($shortcode); ?>"><?php echo ddys_open_h($shortcode); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group">
              <label>数量</label>
              <input class="form-control" type="number" min="1" max="50" value="<?php echo ddys_open_attr($settings['default_limit']); ?>" data-ddys-generator-limit>
            </div>
            <div class="form-group">
              <label>Slug / ID / 用户名</label>
              <input class="form-control" type="text" value="" placeholder="详情类组件填写" data-ddys-generator-identity>
            </div>
            <textarea class="form-control" rows="4" readonly data-ddys-generator-output>[ddys_latest]</textarea>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script defer src="../plugin/ddys_open/static/js/admin.js?v=<?php echo ddys_open_attr(DDYS_OPEN_XIUNO_VERSION); ?>"></script>
<?php include _include(ADMIN_PATH . 'view/htm/footer.inc.htm'); ?>
<?php

function ddys_open_admin_input($name, $label, $value, $placeholder = '', $type = 'text')
{
    return '<div class="form-group"><label for="' . ddys_open_attr($name) . '">' . ddys_open_h($label) . '</label><input class="form-control" type="' . ddys_open_attr($type) . '" id="' . ddys_open_attr($name) . '" name="' . ddys_open_attr($name) . '" value="' . ddys_open_attr($value) . '" placeholder="' . ddys_open_attr($placeholder) . '"></div>';
}

function ddys_open_admin_select($name, $label, $value, $options)
{
    $html = '<div class="form-group"><label for="' . ddys_open_attr($name) . '">' . ddys_open_h($label) . '</label><select class="form-control" id="' . ddys_open_attr($name) . '" name="' . ddys_open_attr($name) . '">';
    foreach ($options as $key => $text) {
        $html .= '<option value="' . ddys_open_attr($key) . '"' . ((string)$key === (string)$value ? ' selected' : '') . '>' . ddys_open_h($text) . '</option>';
    }
    return $html . '</select></div>';
}

function ddys_open_admin_checkbox($name, $label, $value)
{
    return '<div class="form-group"><label class="custom-input custom-checkbox"><input type="checkbox" name="' . ddys_open_attr($name) . '" value="1"' . (!empty($value) ? ' checked' : '') . '> ' . ddys_open_h($label) . '</label></div>';
}

function ddys_open_admin_config_checks($settings, $cache)
{
    $checks = array();
    $checks[] = array(
        'label' => 'API Base URL',
        'status' => preg_match('#^https?://#i', $settings['api_base_url']) ? 'ok' : 'warn',
        'message' => preg_match('#^https?://#i', $settings['api_base_url']) ? '格式正常。' : '请填写 http 或 https 开头的接口地址。'
    );
    $checks[] = array(
        'label' => '服务器请求能力',
        'status' => ddys_open_admin_can_remote_request() ? 'ok' : 'warn',
        'message' => ddys_open_admin_can_remote_request() ? '当前 PHP 环境具备服务端请求能力。' : '当前 PHP 环境可能无法请求远程接口。'
    );
    $checks[] = array(
        'label' => '缓存目录',
        'status' => !empty($cache['writable']) ? 'ok' : 'warn',
        'message' => !empty($cache['writable']) ? '缓存目录可写。' : '缓存目录不可写，请检查 Xiuno tmp 目录权限。'
    );
    $checks[] = array(
        'label' => '求片表单',
        'status' => empty($settings['enable_request_form']) || $settings['api_key'] !== '' ? 'ok' : 'warn',
        'message' => empty($settings['enable_request_form']) ? '当前未启用求片表单。' : ($settings['api_key'] !== '' ? '已启用，并且已配置 API Key。' : '已启用，但 API Key 为空，提交会失败。')
    );
    $checks[] = array(
        'label' => '前台基础路径',
        'status' => $settings['pretty_base_path'] === 'ddys' ? 'ok' : 'info',
        'message' => $settings['pretty_base_path'] === 'ddys' ? '使用默认路径 ddys。' : '已使用自定义路径 ' . $settings['pretty_base_path'] . '，伪静态规则需要同步调整。'
    );
    return $checks;
}

function ddys_open_admin_can_remote_request()
{
    if (function_exists('curl_init')) {
        return TRUE;
    }
    $allow = strtolower((string)ini_get('allow_url_fopen'));
    return $allow === '1' || $allow === 'on' || $allow === 'true';
}
