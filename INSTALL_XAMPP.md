# Windows / XAMPP installation
1. Install XAMPP with PHP 8.3 or later.
2. Extract this folder to `C:\xampp\htdocs\RemoveBG`.
3. Copy `config\config.example.php` to `config\config.php` and set your Pixelcut key.
4. Set `storage_dir` to a writable directory outside htdocs, e.g. `C:/xampp/removebg-private`.
5. In php.ini enable `curl`, `fileinfo`, `gd`; set `upload_max_filesize=10M`, `post_max_size=12M`, `max_execution_time=120`.
6. Restart Apache. MySQL is NOT needed.
7. Open `http://localhost/RemoveBG/` and test a valid image.
8. If SSL verification fails, configure a current CA certificate bundle for PHP cURL; do not disable certificate verification.
9. Check Apache/PHP logs for errors; do not print API keys.
10. Verify private storage is inaccessible through HTTP and schedule CLI cleanup if desired.

Note: Pixelcut may reject the data URL transport. See README before treating the integration as complete.
