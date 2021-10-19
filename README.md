# Proxy module

![CI](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/workflows/CI/badge.svg)

Provides various fixes to serve multiple Drupal instances from one domain.

## Requirements

- PHP 8.0 or higher

## Features

All these features are made to ensure that the instance can be served from `<proxy url>/<project prefix>`.

### Cookie name prefix

Cookie name will be prefixed with the site's hostname. For example `helfi-docker-so-SSID{sha256}`.

### Site prefix

A "prefix" will be added to all URLs. This prefix should be same as the `<project prefix>` in `<proxy url>/<project prefix>`.

These can be configured by creating a `conf/cmi/helfi_proxy.settings.yml` file containing:

```
prefixes:
  en: maps-and-transport
  fi: kartat-ja-liikenne
  sv: kartor-och-trafik
  ru: maps
```
or by adding them to your settings.php:

```
$config['helfi_proxy.settings']['prefixes'] = [
  'en' => 'test-maps-and-transport',
  'fi' => 'test-kartat-ja-liikenne',
  'sv' => 'test-kartor-och-trafik',
  'ru' => 'test-maps',
];
```

### Absolute URL for assets

Converts all assets (`source[srcset]`, `img[src]`, `link[href]`, `script[src]`) to use an absolute URL. The URL is read from `DRUPAL_REVERSE_PROXY_URL` and `DRUPAL_ROUTES` environment variables. The first one to have a value will be used.

This ensures that all local assets are served directly from the instance's real URL instead of `<proxy url>/<project prefix>/<path to asset>`.

### Inline SVGs

SVGs using `<use>` cannot be served from an external domain so this module attempts to 'inline' all SVG `<use>` definitions.

For example, the following SVG:
```
<svg>
  <use href="/core/themes/claro/sprite.svg#helsinki"></use>
</svg>
```

will be converted to:
```
<svg>
   <use href="#helsinki"></use>
</svg>
```

The module attempts to read the file and insert it directly into DOM, then change the `href` attribute to `#helsinki`.

### Disallow search engines/robots from indexing the site

Add an environment variable `DRUPAL_X_ROBOTS_TAG_HEADER` with any value to insert a `X-Robots-Tag: noindex, nofollow` header to every response.


### Varnish support

Enable modules with `drush en varnish_purger varnish_purge_tags purge_drush purge_processor_cron purge_queuer_coretags purge_tokens`.

Copy configuration from `helfi_proxy/config/optional` to your `conf/cmi` folder if you enabled this module before Varnish/Purge modules and import configuration changes.

Add these to your settings.php to use varnish cache:

```
if ($varnish_host = getenv('DRUPAL_VARNISH_HOST')) {
  $config['varnish_purger.settings.default']['hostname'] = $varnish_host;
  $config['varnish_purger.settings.varnish_purge_all']['hostname'] = $varnish_host;
}

if ($varnish_port = getenv('DRUPAL_VARNISH_PORT')) {
  $config['varnish_purger.settings.default']['port'] = $varnish_port;
  $config['varnish_purger.settings.varnish_purge_all']['port'] = $varnish_port;
}

// Configuration doesn't know about existing config here so we can't
// append to existing headers array here and have to include all headers.
// If you have any extra headers you must add them here as well.
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
