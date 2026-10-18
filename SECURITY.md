# Security Policy

Please **do not** report security vulnerabilities in public GitHub issues.

Instead, report it privately through GitHub: go to the repository's **Security** tab and choose **Report a vulnerability**. Include a description of the issue, the steps to reproduce it and the affected version. We will acknowledge your report and work with you on a fix before anything is disclosed publicly.

## Hardening checklist for self-hosters

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Serve the app over HTTPS only.
- Set `SETUP_TOKEN` before the first visit so nobody else can claim the admin account.
- Keep your R2/S3 bucket private. The app hands out short-lived signed download links.
- Run `composer install` regularly to pick up security fixes.
