# Proxy module

![CI](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/workflows/CI/badge.svg)

Provides various fixes to serve multiple Drupal instances from one domain.

## Requirements

- PHP 7.4 or higher

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

## Contact

Slack: #helfi-drupal (http://helsinkicity.slack.com/)

Mail: helfi-drupal-aaaactuootjhcono73gc34rj2u@druid.slack.com
