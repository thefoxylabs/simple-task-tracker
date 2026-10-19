# Security Policy

Please **do not** report security vulnerabilities in public GitHub issues.

Instead, report it privately through GitHub: go to the repository's **Security** tab and choose **Report a vulnerability**. Include a description of the issue, the steps to reproduce it and the affected version. We will acknowledge your report and work with you on a fix before anything is disclosed publicly.

## Hardening checklist for self-hosters

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `APP_URL` to your real address (e.g. `https://tasks.yourcompany.com`). The app only answers to that domain.
- Serve the app over HTTPS only, and keep `SESSION_SECURE_COOKIE=true`.
- Complete the setup wizard straight after installing. In production it needs the setup token from `php artisan app:setup-token` (or your own `SETUP_TOKEN`).
- Use strong, unique database passwords (Docker refuses to start without `DB_PASSWORD` and `DB_ROOT_PASSWORD`).
- Keep your R2/S3 bucket private. The app hands out short-lived signed download links.
- Run the scheduler cron so queued emails are sent. Failed email jobs are cleared after a week.
- Run `composer install` regularly to pick up security fixes, and keep PHP up to date.
