# DDYS API Xiuno BBS Plugin

English | [简体中文](README.zh-CN.md)

Official Xiuno BBS 4.x plugin for the [DDYS](https://ddys.io/) API. It adds local pages, shortcodes, a server-side JSON proxy, caching, diagnostics, and a request form without exposing the API Key in the browser.

- Repository: [ddysiodev/ddys-xiuno-plugin](https://github.com/ddysiodev/ddys-xiuno-plugin)
- GitHub Release: [v0.1.0](https://github.com/ddysiodev/ddys-xiuno-plugin/releases/tag/v0.1.0)
- Download ZIP: [ddys-xiuno-plugin-v0.1.0.zip](https://github.com/ddysiodev/ddys-xiuno-plugin/releases/download/v0.1.0/ddys-xiuno-plugin-v0.1.0.zip)
- Plugin directory: `ddys_open`
- Target: Xiuno BBS 4.0.x classic plugin system
- Distribution: GitHub Release ZIP

## Features

- Admin settings for API Base URL, source site URL, API Key, timeout, cache TTLs, default count, theme, layout, navigation, and request form.
- Admin diagnostics for connection tests, cache status, cache clearing, and endpoint inspection.
- Generator for shortcodes, frontend page links, and local proxy URLs.
- Frontend pages for latest, hot, search, calendar, movie detail, collections, collection detail, and requests.
- Shortcode rendering for posts, pages, and templates.
- Local JSON proxy under the forum domain, keeping the API Key server-side.
- Server-side request submission with nonce validation, rate limiting, field validation, and clear errors.
- Per-endpoint caching with separate TTLs for dictionaries, fresh lists, details, and community data.
- Safety checks for route allowlists, parameter allowlists, escaped output, media URL validation, timeouts, and sensitive settings.

## Installation

1. Download `ddys-xiuno-plugin-v0.1.0.zip` from Releases.
2. Upload the `ddys_open` directory to the Xiuno `plugin/` directory.
3. In the Xiuno admin panel, install and enable “低端影视 API”.
4. Open the plugin settings page and configure API Base URL, cache TTLs, display options, and navigation.
5. To enable the request form, set an API Key and run the connection test.

## Frontend Routes

Default Xiuno URL examples:

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

The admin frontend base path defaults to `ddys` and only supports letters, digits, and underscores. It must not use Xiuno core routes such as `index`, `thread`, `forum`, or `user`. If you change it to another safe path, such as `movies`, frontend entries become `?movies.htm`, `?movies-hot.htm`, and related rewrite rules should be updated accordingly.

Local proxy examples:

```text
?ddys-api.htm&route=latest&limit=6
?ddys-api.htm&route=movie&slug=this-tempting-madness
?ddys-api.htm&route=collections&page=1
```

Request form endpoint:

```text
?ddys-request-submit.htm
```

## Shortcodes

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

Common attributes:

| Attribute | Description |
| --- | --- |
| `layout` | `grid`, `list`, or `compact` |
| `theme` | `auto`, `light`, or `dark` |
| `columns` | Grid columns, from 1 to 6 |
| `cache_ttl` | Override cache TTL in seconds |
| `limit` / `per_page` | List size |

## Rewrite Examples

Apache:

```apache
RewriteRule ^ddys/?$ index.php?ddys.htm [L,QSA]
RewriteRule ^ddys/(hot|search|calendar|collections|requests)/?$ index.php?ddys-$1.htm [L,QSA]
RewriteRule ^ddys/movie/([^/]+)/?$ index.php?ddys-movie-$1.htm [L,QSA]
RewriteRule ^ddys/collection/([^/]+)/?$ index.php?ddys-collection-$1.htm [L,QSA]
RewriteRule ^ddys/api/?$ index.php?ddys-api.htm [L,QSA]
RewriteRule ^ddys/request-submit/?$ index.php?ddys-request-submit.htm [L,QSA]
```

Nginx:

```nginx
rewrite ^/ddys/?$ /index.php?ddys.htm last;
rewrite ^/ddys/(hot|search|calendar|collections|requests)/?$ /index.php?ddys-$1.htm last;
rewrite ^/ddys/movie/([^/]+)/?$ /index.php?ddys-movie-$1.htm last;
rewrite ^/ddys/collection/([^/]+)/?$ /index.php?ddys-collection-$1.htm last;
rewrite ^/ddys/api/?$ /index.php?ddys-api.htm last;
rewrite ^/ddys/request-submit/?$ /index.php?ddys-request-submit.htm last;
```

IIS:

```xml
<rule name="DDYS Xiuno">
  <match url="^ddys/?$" />
  <action type="Rewrite" url="index.php?ddys.htm" appendQueryString="true" />
</rule>
```

## Local Check

```powershell
node tools/check.mjs
```
