# Contributing

Contributions are welcome and appreciated. Please take a moment to review this document before submitting a pull request.

## Development Setup

1. Fork and clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

## Workflow

1. Create a feature branch from `main`
2. Make your changes
3. Ensure all checks pass (see below)
4. Submit a pull request

## Code Quality

Before submitting, please ensure:

### Tests

```bash
composer test
```

All tests must pass. New features should include tests.

### Static Analysis

```bash
composer analyse
```

PHPStan level 9 must pass without errors.

### Code Style

```bash
composer format
```

Code must follow Laravel Pint standards.

## Pull Request Guidelines

- Keep changes focused and atomic
- Write clear commit messages
- Update documentation if needed
- Add tests for new functionality
- Reference related issues in the PR description

## Reporting Issues

When reporting bugs, please include:

- PHP and Laravel versions
- Steps to reproduce
- Expected vs actual behavior
- Relevant code snippets

## Security Vulnerabilities

Please report security vulnerabilities via [our contact page](https://pxl.no/en/contact) rather than the issue tracker.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
