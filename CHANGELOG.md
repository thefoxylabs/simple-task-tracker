# Changelog

All notable changes to Task Tracker are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Upgraded to **Laravel 13** (from Laravel 12). Task Tracker now requires **PHP 8.3 or newer**.
- Laravel Tinker upgraded to version 3, and Guzzle to version 8 (pulled in by Laravel 13).

### Security
- Fixed a cross-site scripting issue where a crafted `?period=` link on the Plans page could run JavaScript for a manager or admin who clicked "New plan". All values read from the URL are now checked against allowed values.
- Staff can no longer open project pages or download project files for projects they aren't part of. Every file download now checks access to its task or project.
- Staff now only see company-wide plans, their group's plans and their own plans.
- Task, project and plan pages re-check access on every request, not only when first opened.
- Staff can only file tasks under projects and plans they can see.
- The setup wizard requires a one-time token in production (`php artisan app:setup-token`), limits wrong guesses, and can't be completed twice by simultaneous requests.
- Stronger login throttling (per account and per IP address) and throttling for password reset emails.
- Security headers (no framing, nosniff, referrer policy, HSTS over HTTPS) and trusted hosts based on `APP_URL`.
- Passwords need at least 10 characters with letters and numbers. Changing your email needs your current password, and changing your password signs out other devices.
- Task titles and names can no longer add links or formatting to emails.
- Password reset and invitation jobs are encrypted in the queue, and failed jobs are pruned after a week.
- Safer download filenames, SVG logos disabled, and session cookies are encrypted and HTTPS-only by default.
- Docker Compose no longer starts with default database passwords. "Keep me signed in" is now off by default.

### Added
- **Queued emails.** Assignment emails, reminders, invitations and password resets are sent in the background through the database queue, with three automatic retries. The scheduler processes the queue, so no extra worker is needed (`QUEUE_WORKER_IN_SCHEDULER`).
- Project homepage on GitHub Pages, with a screenshot lightbox and Foxy Labs installation and hosting packages: Basic install (from $250), Full setup ($500), Managed hosting (from $99/month) and maintenance plans (from $49/month).

## [1.0.0] - 2026-10-19

The first public release.

### Added
- **Kanban board** with To do, In progress, In review and Done columns, drag and drop, colour-coded priorities, assignees, deadlines and overdue highlighting, plus a list view.
- **Filters** by period (today, this week, month, quarter, year), person, staff group, project and priority, and a free-text search.
- **Projects** with progress, a project lead, start and due dates, file attachments, archiving and restoring.
- **Plans**: daily, weekly, monthly, quarterly and yearly goals for a person, a staff group or the whole company, with progress from linked tasks.
- **Recurring tasks** that are created and assigned automatically every day, week, month, quarter or year.
- **Deadlines and email reminders** chosen by whoever sets the task, plus assignment emails and staff invitations.
- **Staff tracking** for any day, week, month, quarter or year, with done, open and overdue counts per person.
- **Roles**: admin, manager and staff, with permissions enforced on the server.
- **Staff management** and **staff groups**, including invitations and deactivation.
- **Attachments** on tasks and projects, stored in Cloudflare R2 by default (or S3, or local disk) and downloaded with short-lived signed links.
- **White-label branding** in Settings: app name, company name, logo, brand colour, sign-in tagline and footer text. The brand colour is applied to every accent, native control and email button.
- **SMTP settings** in the app, stored encrypted, with a test email button.
- **Searchable dropdowns** (Select2) throughout the app.
- **Custom date and time picker** with month and year views, shortcuts, a scrollable time picker, keyboard support and a bottom sheet on phones.
- **Mobile-friendly layouts** for every page and form.
- First-run **setup wizard** with an optional `SETUP_TOKEN`.
- **Demo data** seeder, **Docker** setup, GitHub Actions **CI**, and a test suite.

[Unreleased]: https://github.com/thefoxylabs/simple-task-tracker/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/thefoxylabs/simple-task-tracker/releases/tag/v1.0.0
