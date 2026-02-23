# Tabi - Time Tracking with Screenshot Monitoring

Tabi is a time-tracking application with built-in screenshot monitoring for remote teams. Track time, manage projects, and monitor work activity with automatic screenshots.

Forked from [solidtime](https://github.com/solidtime-io/solidtime).

## Features

- Time tracking with a modern, easy-to-use interface
- **Screenshot monitoring**: Automatic screenshot capture and management for remote team oversight
- **Organization-level screenshot settings**: Configure screenshot frequency and policies per organization
- Projects, tasks, and clients management
- Billable rates for projects, members, and organizations
- Multiple organizations with role-based permissions
- Reporting and data export
- Import from Toggl, Clockify, and CSV

## Self Hosting

Refer to the original [solidtime self-hosting guide](https://docs.solidtime.io/self-hosting/intro) for setup instructions.

## Development

```bash
composer install
npm install
php artisan serve        # API server
npm run dev              # Vite dev server
```

### Testing

```bash
composer test            # PHPUnit
php artisan test --filter=SomeTest
```

### Code Quality

```bash
composer fix             # Laravel Pint formatter
composer analyse         # PHPStan level 7
npm run lint:fix         # ESLint
npm run format           # Prettier
```

## License

This project is open-source and available under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE.md).

## Acknowledgments

Built on top of [solidtime](https://github.com/solidtime-io/solidtime) by the solidtime team.
