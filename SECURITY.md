# Security
- Keep config.php and temporary files out of public access; store files outside document root.
- Do not commit credentials. Rotate exposed keys immediately.
- Uploaded images are MIME/type/size/dimension checked and decoded before submission.
- Requests use CSRF tokens, browser sessions, IP-based rate limits and secure TLS.
- Provider image URL retrieval is restricted to explicitly allowlisted HTTPS hosts.
- File downloads use unpredictable IDs with session-bound expiry.
- Ensure upload and post size limits are enforced by PHP and your reverse proxy.
- Run cleanup regularly and consider adding server-level rate limiting and malware controls.
- This is a starter implementation, not a security audit. Test under your actual hosting conditions.
