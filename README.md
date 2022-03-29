# Proxy module

![CI](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/workflows/CI/badge.svg)
[![codecov](https://codecov.io/gh/City-of-Helsinki/drupal-module-helfi-proxy/branch/main/graph/badge.svg?token=K3S899VYYB)](https://codecov.io/gh/City-of-Helsinki/drupal-module-helfi-proxy)

Provides various fixes to serve multiple Drupal instances from one domain.

## Requirements

- PHP 8.0 or higher

## Features

All these features are made to ensure that the instance can be served from `<proxy url>/<project prefix>`.

### Cookie name suffix

Cookie name will be suffixed with the site's hostname. For example `SSID{sha256}helfi-docker-so`.

### Site prefix

A "prefix" will be added to all URLs. This prefix should be same as the `<project prefix>` in `<proxy url>/<project prefix>`.

These can be configured by creating a `conf/cmi/helfi_proxy.settings.yml` file containing:

```yaml
prefixes:
  en: maps-and-transport
  fi: kartat-ja-liikenne
  sv: kartor-och-trafik
  ru: maps
```
or by adding them to your settings.php:

```php
$config['helfi_proxy.settings']['prefixes'] = [
  'en' => 'test-maps-and-transport',
  'fi' => 'test-kartat-ja-liikenne',
  'sv' => 'test-kartor-och-trafik',
  'ru' => 'test-maps',
];
```

### Serve assets from asset path

All assets (`script[src]`, `source[srcset]`, `img[src]` etc.) are served from the path configured in (`helfi_proxy.settings`) `asset-path` setting. For example `liikenne-assets`.

This ensures that all local assets are served directly from the asset path (`<proxy url>/<asset path>/<path to asset>`) instead of `<proxy url>/<path to asset>`.

### Disallow search engines/robots from indexing the site

Set `helfi_proxy.settings.robots_header_enabled` configuration to `TRUE`.

```php
if ($robots_header_enabled = getenv('DRUPAL_X_ROBOTS_TAG_HEADER')) {
  $config['helfi_proxy.settings']['robots_header_enabled'] = (bool) $robots_header_enabled;
}
```

This will insert a `X-Robots-Tag: noindex, nofollow` header to every response, effectively blocking search engines from indexing the site.

You can use `helfi_proxy.settings` configuration to only ignore certain paths:

```yaml
robots_paths:
  - '/user/login'
  - '/residential-*'
```

### Varnish support

Enable modules with `drush en varnish_purger varnish_purge_tags purge_drush purge_processor_cron purge_queuer_coretags purge_tokens`.

Copy configuration from `helfi_proxy/config/optional` to your `conf/cmi` folder if you enabled this module before Varnish/Purge modules.

Add these to your settings.php to use varnish cache:

```php
if ($varnish_host = getenv('DRUPAL_VARNISH_HOST')) {
  $config['varnish_purger.settings.default']['hostname'] = $varnish_host;
  $config['varnish_purger.settings.varnish_purge_all']['hostname'] = $varnish_host;
}

if ($varnish_port = getenv('DRUPAL_VARNISH_PORT')) {
  $config['varnish_purger.settings.default']['port'] = $varnish_port;
  $config['varnish_purger.settings.varnish_purge_all']['port'] = $varnish_port;
}

// Configuration doesn't know about existing config yet so we can't
// just append new headers to an already existing headers array here.
// If you have configured any extra headers in your purge settings
// you must add them here as well.
$config['varnish_purger.settings.default']['headers'] = [
  [
    'field' => 'Cache-Tags',
    'value' => '[invalidation:expression]',
  ],
];
$config['varnish_purger.settings.varnish_purge_all']['headers'] = [
  [
    'field' => 'X-VC-Purge-Method',
    'value' => 'regex',
  ],
];

if ($varnish_purge_key = getenv('VARNISH_PURGE_KEY')) {
  $config['varnish_purger.settings.default']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
  $config['varnish_purger.settings.varnish_purge_all']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
}
```

## Contact

Slack: #helfi-drupal (http://helsinkicity.slack.com/)

Mail: helfi-drupal-aaaactuootjhcono73gc34rj2u@druid.slack.com
