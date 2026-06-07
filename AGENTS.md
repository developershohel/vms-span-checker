# Agent instructions — VMS Elements Form Guard

## Runtime dependencies

This plugin has **no Composer runtime packages** in `require`. Production PHP uses WordPress APIs and plugin classes only — it does **not** load `vendor/autoload.php`.

WordPress core is never installed via Composer and does not belong in `vendor/`.

## PHP_CodeSniffer (local `vendor/` + optional global)

Dev-only packages live in `require-dev` and install to `vendor/` when you run:

```bash
composer install
```

You will see `vendor/autoload.php`, `vendor/bin/phpcs`, and standard paths registered by `dealerdirect/phpcodesniffer-composer-installer`. That is **lint tooling only**, not code the live site loads.

The same packages may also be installed **globally** for reuse across projects. Prefer **local** `vendor/bin/phpcs` when working in this repo.

| Global package | Role |
|----------------|------|
| `dealerdirect/phpcodesniffer-composer-installer` | Registers PHPCS standard paths automatically |
| `wp-coding-standards/wpcs` | WordPress coding standards (`WordPress`, `WordPress-Core`, etc.) |
| `phpcompatibility/phpcompatibility-wp` | PHP version compatibility checks for WordPress (`PHPCompatibilityWP`) |

Supporting packages (installed automatically with the above): `squizlabs/php_codesniffer`, `phpcsstandards/phpcsutils`, `phpcsstandards/phpcsextra`, `phpcompatibility/php-compatibility`, `phpcompatibility/phpcompatibility-paragonie`.

### When the user mentions PHPCS / WPCS / PHPCompatibility

1. **Prefer local:** `vendor/bin/phpcs` and `vendor/bin/phpcbf` (run `composer install` if `vendor/` is missing).
2. **Fallback:** global `phpcs` / `phpcbf` if installed on the machine.
3. **Use** `phpcs.xml.dist` in the plugin root.

### Plugin Check alignment

`phpcs.xml.dist` is deliberately scoped to the rules the official
[Plugin Check](https://wordpress.org/plugins/plugin-check/) tool enforces:
`WordPress-Core` + `WordPress-Extra` + `PHPCompatibilityWP` minus a few pure
style preferences (Yoda conditions, short ternary, inline-comment end-char).
`WordPress-Docs` is **not** included because Plugin Check does not enforce
docblock formatting and pulling it in adds hundreds of noisy violations that
will never block a WP.org submission. Do not re-add `WordPress-Docs` or the
excluded sniffs without a concrete reason.

### Commands (from plugin root)

```bash
composer install
vendor/bin/phpcs -ps . --standard=phpcs.xml.dist
vendor/bin/phpcbf -ps . --standard=phpcs.xml.dist

# Or
composer phpcs
composer phpcbf
```

### Global install (one-time per machine)

```bash
composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer global config preferred-install dist
composer global require --dev dealerdirect/phpcodesniffer-composer-installer wp-coding-standards/wpcs phpcompatibility/phpcompatibility-wp
```

Ensure the global Composer `vendor/bin` directory is on `PATH`:

- **Windows:** `%APPDATA%\Composer\vendor\bin`
- **macOS/Linux:** `~/.composer/vendor/bin` or `~/.config/composer/vendor/bin`

Verify: `phpcs -i` should list `WordPress` and `PHPCompatibilityWP`.

### If `vendor/` is missing

Run `composer install` in the plugin root. Do not commit `vendor/` to git; do not ship it in WordPress.org zip builds (see `.distignore`).

## JavaScript minify (global terser)

Source files live in `assets/js/*.js`. Production loads `*.min.js` when present and `SCRIPT_DEBUG` is off.

One-time install:

```bash
npm install -g terser
```

After editing any source JS:

```powershell
cd path\to\vms-elements-form-guard
.\scripts\minify-js.ps1
```

Or manually:

```bash
terser assets/js/vms-elements-form-guard.js -o assets/js/vms-elements-form-guard.min.js -c -m --comments false
```

Enqueue uses `vms_elements_form_guard_js_asset( 'vms-elements-form-guard' )` in `includes/functions.php`.

## Licensing — Pro only (WP.org Trialware compliance)

The free plugin **must not** contain any license-activation code, license validation, license storage, or license-gated features. All license handling lives exclusively in the Pro plugin (`../vms-elements-form-guard-pro/licensing/`).

When Pro is not active, the free plugin shows one **Upgrade Now** admin page (no lock stubs, no license UI) that lists Pro features and links to `https://vmselements.com/product/vms-elements-form-guard-pro`. See `includes/class-pro-features.php` and `templates/upgrade-now.php`.

Do not re-introduce a `licensing/` folder, a "License" admin page, license-heartbeat JS, or any `vms_elements_form_guard_license_*` constants in this repo.

## Project layout notes

- `vendor/` is gitignored — developers generate it locally; production/plugin zip excludes it.
- **Pro plugin** (`../vms-elements-form-guard-pro/`) has no Composer at all; lint it via this repo’s `phpcs.xml.dist` (includes the Pro path).
- `.distignore` excludes Composer and PHPCS config from WordPress.org zip builds.
- Prefer minimal, focused diffs; match existing plugin naming and patterns.
