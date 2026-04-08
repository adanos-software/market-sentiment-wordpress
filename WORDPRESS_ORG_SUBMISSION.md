# WordPress.org Submission Notes

This repository contains the public source for the `Adanos Market Sentiment Widgets` WordPress plugin by Adanos.

## Intended plugin slug

- `adanos-market-sentiment-widgets`

## What stays in this GitHub repo

- `readme.txt` for WordPress.org parsing
- `wordpress-org-assets/` for source-controlled listing art
- `README.md`, `SECURITY.md`, and this file as repo documentation

## What goes to WordPress.org SVN

- `trunk/`
  - `adanos-market-sentiment-widgets.php`
  - `assets/`
  - `readme.txt`
  - `uninstall.php`
- top-level SVN `assets/`
  - `banner-1544x500.png`
  - `banner-772x250.png`
  - `icon-256x256.png`
  - `icon-128x128.png`
  - `screenshot-1.png`
  - `screenshot-2.png`

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

- `adanos-market-sentiment-widgets/`
  - `adanos-market-sentiment-widgets.php`
  - `assets/`
  - `readme.txt`
  - `uninstall.php`

`wordpress-org-assets/`, `README.md`, `SECURITY.md`, and this file are repository metadata and should not be required inside the install ZIP.

## Submission flow

1. Submit the plugin through the WordPress.org plugin uploader and request the slug `adanos-market-sentiment-widgets`.
2. After approval, use the provided SVN repository URL.
3. Commit the plugin code to `trunk/`.
4. Commit the listing assets to the SVN root `assets/`.
5. Create the first SVN tag, for example `tags/0.6.1/`.

## Local preflight note

Until the plugin is actually installed from a `adanos-market-sentiment-widgets` directory or checked from the WordPress.org SVN repo, local `plugin-check` runs should pass the intended slug explicitly:

- `wp plugin check adanos-market-sentiment-widgets --slug=adanos-market-sentiment-widgets`

## Validation

Validated before release with:

- `python3 -m pytest tests/ -q`
- PHP lint inside `wordpress:cli-php8.2`

The parent monorepo currently has unrelated failing tests during full collection, so plugin-specific validation is the authoritative check for this package.
