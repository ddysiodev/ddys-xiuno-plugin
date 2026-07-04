# 低端影视 API Xiuno BBS 插件

[English](README.md) | 简体中文

低端影视 API 的官方 Xiuno BBS 4.x 插件。安装后，论坛可以通过本地页面、短代码和本地 JSON 代理展示 [低端影视](https://ddys.io/) API 内容，并支持服务端求片提交。

- GitHub 仓库：[ddysiodev/ddys-xiuno-plugin](https://github.com/ddysiodev/ddys-xiuno-plugin)
- GitHub Release：[v0.1.0](https://github.com/ddysiodev/ddys-xiuno-plugin/releases/tag/v0.1.0)
- 下载压缩包：[ddys-xiuno-plugin-v0.1.0.zip](https://github.com/ddysiodev/ddys-xiuno-plugin/releases/download/v0.1.0/ddys-xiuno-plugin-v0.1.0.zip)
- 插件目录：`ddys_open`
- 兼容目标：Xiuno BBS 4.0.x 传统插件体系
- 分发方式：GitHub Release ZIP

## 功能

- 后台配置：API Base URL、来源站点 URL、API Key、请求超时、默认数量、样式、导航入口和求片开关。
- 后台诊断：连接测试、缓存目录状态、缓存文件统计、缓存清理和入口 URL 检查。
- 生成器：在后台选择组件、数量和详情标识，生成短代码、前台页面链接或本地代理 URL。
- 前台页面：最新、热门、搜索、日历、影片详情、片单、片单详情和求片列表。
- 短代码嵌入：帖子、页面或模板输出中出现 `[ddys_*]` 时自动渲染。
- 本地 JSON 代理：浏览器请求论坛本地 `/ddys-api` 路由，插件服务端再请求低端影视 API。
- 服务端求片：带 nonce、限流、字段校验、错误提示，API Key 不暴露到前端。
- 缓存：按接口和参数生成缓存，区分字典、列表、详情和社区数据 TTL。
- 安全：路由白名单、参数白名单、输出转义、媒体 URL 校验、请求超时和敏感信息保护。

## 安装

1. 下载 Release 中的 `ddys-xiuno-plugin-v0.1.0.zip`。
2. 解压后将 `ddys_open` 上传到 Xiuno 根目录的 `plugin/`。
3. 进入后台 `插件 -> 本地插件`，安装并启用“低端影视 API”。
4. 打开插件设置页，填写 API Base URL、缓存时间、显示样式等配置。
5. 如果要启用求片表单，填写 API Key，并在后台执行连接测试。

## 前台入口

Xiuno 默认 URL 模式下可以访问：

```text
?ddys.htm
?ddys-hot.htm
?ddys-search.htm
?ddys-calendar.htm
?ddys-movie-this-tempting-madness.htm
?ddys-collections.htm
?ddys-collection-editor-choice.htm
?ddys-requests.htm
```

后台“前台基础路径”默认是 `ddys`，只支持英文字母、数字和下划线，且不能使用 `index`、`thread`、`forum`、`user` 等 Xiuno 核心路由。如果改成其他路径，例如 `movies`，前台入口会对应变成 `?movies.htm`、`?movies-hot.htm` 等，伪静态规则也需要同步替换路径。

本地代理示例：

```text
?ddys-api.htm&route=latest&limit=6
?ddys-api.htm&route=movie&slug=this-tempting-madness
?ddys-api.htm&route=collections&page=1
```

求片提交入口：

```text
?ddys-request-submit.htm
```

## 短代码

```text
[ddys_latest limit="12"]
[ddys_hot limit="10"]
[ddys_search]
[ddys_calendar year="2026" month="7"]
[ddys_movie slug="this-tempting-madness"]
[ddys_sources slug="this-tempting-madness"]
[ddys_related slug="this-tempting-madness"]
[ddys_comments slug="this-tempting-madness"]
[ddys_collections page="1"]
[ddys_collection slug="editor-choice"]
[ddys_shares page="1"]
[ddys_share id="1"]
[ddys_requests page="1"]
[ddys_activities page="1"]
[ddys_user username="demo"]
[ddys_types]
[ddys_genres]
[ddys_regions]
[ddys_request_form]
```

通用参数：

| 参数 | 说明 |
| --- | --- |
| `layout` | `grid`、`list`、`compact` |
| `theme` | `auto`、`light`、`dark` |
| `columns` | 网格列数，1 到 6 |
| `cache_ttl` | 单个短代码覆盖缓存时间，单位秒 |
| `limit` / `per_page` | 列表数量 |

## 伪静态示例

如果论坛已启用 Xiuno URL Rewrite，可以参考下面规则。不同站点目录和服务器配置可能不同，发布前请先在测试环境验证。

Apache：

```apache
RewriteRule ^ddys/?$ index.php?ddys.htm [L,QSA]
RewriteRule ^ddys/(hot|search|calendar|collections|requests)/?$ index.php?ddys-$1.htm [L,QSA]
RewriteRule ^ddys/movie/([^/]+)/?$ index.php?ddys-movie-$1.htm [L,QSA]
RewriteRule ^ddys/collection/([^/]+)/?$ index.php?ddys-collection-$1.htm [L,QSA]
RewriteRule ^ddys/api/?$ index.php?ddys-api.htm [L,QSA]
RewriteRule ^ddys/request-submit/?$ index.php?ddys-request-submit.htm [L,QSA]
```

Nginx：

```nginx
rewrite ^/ddys/?$ /index.php?ddys.htm last;
rewrite ^/ddys/(hot|search|calendar|collections|requests)/?$ /index.php?ddys-$1.htm last;
rewrite ^/ddys/movie/([^/]+)/?$ /index.php?ddys-movie-$1.htm last;
rewrite ^/ddys/collection/([^/]+)/?$ /index.php?ddys-collection-$1.htm last;
rewrite ^/ddys/api/?$ /index.php?ddys-api.htm last;
rewrite ^/ddys/request-submit/?$ /index.php?ddys-request-submit.htm last;
```

IIS：

```xml
<rule name="DDYS Xiuno">
  <match url="^ddys/?$" />
  <action type="Rewrite" url="index.php?ddys.htm" appendQueryString="true" />
</rule>
```

## 发布检查

```powershell
node tools/check.mjs
```

检查覆盖插件结构、hook 入口、后台设置、短代码、本地代理、求片表单、缓存、安全文案、图标尺寸和禁止提交的运行时文件。
