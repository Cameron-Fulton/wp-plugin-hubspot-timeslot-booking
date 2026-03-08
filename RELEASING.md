# Releasing a New Plugin Version

This documents the end-to-end process for releasing a new version of the Event Landing Pages WordPress plugin and delivering it to WordPress sites via the GitHub update checker (PUC).

## How Updates Work

The plugin bundles [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) (PUC), which periodically checks this GitHub repo for new releases. Because the plugin lives in the `event-landing-pages/` subdirectory of a monorepo, `enableReleaseAssets()` is configured so PUC downloads the **attached zip asset** from the release — not the source archive (which would include the entire repo).

WordPress checks for updates every 12 hours by default. Admins can force an immediate check from **Dashboard → Updates**.

## Release Steps

### 1. Bump the version

Update **both** places in `event-landing-pages/event-landing-pages.php`:

```php
// Line 6 — plugin header
* Version:     1.2.0

// Line 19 — PHP constant
define( 'ELP_VERSION', '1.2.0' );
```

These must match each other and must be higher than the version currently deployed.

### 2. Commit and push

```bash
git add event-landing-pages/event-landing-pages.php
git commit -m "Bump version to 1.2.0"
git push
```

### 3. Build the plugin zip

The zip must have `event-landing-pages/` as the top-level directory (matches the plugin slug so WordPress extracts it correctly). Use Python to avoid backslash path issues on Windows:

```bash
python3 -c "
import zipfile, os
os.chdir('D:/projects/castlerock/lp-event')
ver = '1.2.0'
with zipfile.ZipFile(f'event-landing-pages-{ver}.zip', 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk('event-landing-pages'):
        for f in files:
            fp = os.path.join(root, f)
            zf.write(fp, fp.replace(os.sep, '/'))
"
```

> **Warning:** Do not use PowerShell `Compress-Archive` — it creates backslash paths that break on Linux.

### 4. Create the GitHub release

```bash
gh release create v1.2.0 \
  --title "v1.2.0" \
  --notes "Description of changes" \
  event-landing-pages-1.2.0.zip
```

### 5. Verify

- Visit **Dashboard → Updates** in wp-admin to force an update check
- The new version should appear as an available update
- Click "Update Now" to install

## Rules

| Rule | Why |
|------|-----|
| Tag must start with `v` (e.g. `v1.2.0`) | PUC strips the `v` prefix when comparing versions |
| Zip top-level dir must be `event-landing-pages/` | WordPress expects the directory name to match the plugin slug |
| Tag version > deployed version | WordPress only shows updates when remote version is higher |
| Header version = `ELP_VERSION` constant | Mismatches cause confusing behavior in admin UI |
| Attach the zip as a release asset | `enableReleaseAssets()` tells PUC to download assets, not source |

## Deploying Without a Release (Direct Deploy)

For deploying code to Cloudways without going through the update checker (e.g., hotfixes):

1. Push code to `main` on GitHub
2. Use the WP REST API + Code Snippets method:
   - Install Code Snippets from wordpress.org via `POST /wp/v2/plugins` with `{"slug":"code-snippets","status":"active"}`
   - Create a snippet via `POST /wp-json/code-snippets/v1/snippets` that downloads the GitHub main zip, extracts `event-landing-pages/` to `wp-content/plugins/`, and activates it
   - Delete the snippet and uninstall Code Snippets when done
3. All REST API calls require Application Password auth and a browser-like `User-Agent` header
