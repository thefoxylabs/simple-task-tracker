# Contributing

Thanks for helping improve Task Tracker!

1. Fork the repository and create a branch from `main`.
2. Set up a development copy (see "Development" in the README).
3. Make your change. Keep it focused, and add or update tests in `tests/`.
4. Run `php artisan test` and `vendor/bin/pint` and make sure both pass.
5. Open a pull request that explains what you changed and why.

## Guidelines

- Follow the existing structure: Livewire components in `app/Livewire`, views in `resources/views/livewire`.
- Use the shared CSS classes (`btn`, `input`, `card`, `badge`…) and the `brand-*` colours so custom branding keeps working.
- Check permissions on the server (Gates and policies), not only in the UI.
- Write new settings through `App\Support\Settings` so they appear in the admin area.

## Reporting bugs and requesting features

Open a GitHub issue with steps to reproduce, what you expected and what happened. Include your PHP version and database.
