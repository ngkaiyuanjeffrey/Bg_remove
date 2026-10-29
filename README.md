# RemoveBG — PHP / Pixelcut

No SQL database, no account system, no Composer or npm. Requires PHP 8.3+, cURL, Fileinfo, GD, sessions, Apache.

## Setup
1. Copy `config/config.example.php` to `config/config.php`.
2. Enter your own Pixelcut API key. Keep this file private; never commit it.
3. Ensure `storage_dir` is writable and located **outside** the web root.
4. Set PHP `upload_max_filesize=10M`, `post_max_size=12M` and suitable timeouts.
5. Serve with Apache and HTTPS on production.

## API transport
The Pixelcut remove-background endpoint accepts a multipart upload using the `image` field. Uploaded files are sent directly to the provider; they are not exposed through a public URL. The result URL JSON property is parsed from `result_url`, `image_url`, or `url`, and only explicitly allowlisted HTTPS Pixelcut hosts are retrieved.

## Operational notes
Files are temporarily stored outside the web root. They expire after one hour by default. Cleanup runs during processing requests and via `cron/cleanup.php` (schedule hourly). Downloads require the original browser session. API keys are server-only. Rate limits are IP-based file counters (20/hour by default); for public high-volume deployments also add reverse-proxy rate limiting. No persistent processing history.

## Testing checklist
- [ ] XAMPP Apache loads homepage
- [ ] Upload valid JPEG, PNG, WebP
- [ ] Reject invalid, oversized and corrupt images
- [ ] Verify successful Pixelcut API response using your key
- [ ] Confirm transparent PNG result and download
- [ ] Confirm solid color export pixels
- [ ] Check rate limit, token expiry and storage cleanup
- [ ] Verify private configuration and storage cannot be accessed by URL

No live provider request was made. PHP execution tests depend on your server environment.
# bgremove
