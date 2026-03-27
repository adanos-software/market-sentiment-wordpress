# WordPress.org Submission Notes

This repository contains the public source for the `Market Sentiment` WordPress plugin by Adanos.

## Intended plugin slug

- `market-sentiment`

## Included assets

- `readme.txt` for WordPress.org parsing
- `wordpress-org-assets/banner-1544x500.png`
- `wordpress-org-assets/banner-772x250.png`
- `wordpress-org-assets/icon-256x256.png`
- `wordpress-org-assets/icon-128x128.png`
- `wordpress-org-assets/screenshot-1.png`
- `wordpress-org-assets/screenshot-2.png`

## External service disclosure

The plugin uses the Adanos Finance API as an external service.

- Key signup: <https://adanos.org/reddit-stock-sentiment#api-form>
- API docs: <https://api.adanos.org/docs>
- Privacy policy: <https://adanos.org/privacy-policy>
- Terms: <https://adanos.org/terms>

The plugin:

- keeps the API key server-side
- proxies requests through WordPress
- caches responses in WordPress transients
- ships frontend widget code locally instead of hotlinking runtime JavaScript

## Installable package

For GitHub releases, the installable ZIP should contain this structure:

- `market-sentiment/`
  - `adanos-retail-sentiment-insights.php`
  - `assets/`
  - `readme.txt`
  - `uninstall.php`

`wordpress-org-assets/`, `README.md`, `SECURITY.md`, and this file are repository metadata and should not be required inside the install ZIP.

## Validation

Validated before release with:

- `python3 -m pytest tests/test_wordpress_retail_sentiment_plugin.py -q`
- PHP lint inside `wordpress:cli-php8.2`

The parent monorepo currently has unrelated failing tests during full collection, so plugin-specific validation is the authoritative check for this package.
