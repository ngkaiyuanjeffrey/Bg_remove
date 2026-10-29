# cPanel deployment
1. Use PHP 8.3+ with curl, fileinfo, gd and HTTPS.
2. Upload application files to the desired public_html subdirectory.
3. Create a private writable folder in your home directory outside public_html.
4. Copy config.example.php to config.php; set your API key and private storage path. Protect configuration from public access.
5. Configure PHP upload limits and timeouts using hosting PHP settings.
6. Ensure Apache .htaccess rules work. Test direct requests to config/, includes/, cron/ return 403.
7. Configure hourly cron: `/usr/local/bin/php /home/ACCOUNT/public_html/RemoveBG/cron/cleanup.php` (adjust paths).
8. Test upload, provider errors, download, background export and cleanup.
9. Confirm the provider's actual accepted image input and response schema before launch; see README.
