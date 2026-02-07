# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-02-07

### Added

- Core `bindQuery()` method for full query builder control
- `bindWith()` for eager loading relationships
- `bindWithCount()` for relationship counts
- `bindSelect()` for column selection
- `bindWithTrashed()` and `bindOnlyTrashed()` for soft delete handling
- `bindScoped()` for applying model scopes
- `bindWhere()` for simple where conditions
- `bindWithoutGlobalScope()` and `bindWithoutGlobalScopes()` for scope removal
- `QueryBindable` interface for default model binding behavior
- Parent model access in query callbacks
- Support for custom route keys
- Method chaining for multiple bindings
- Global and route-specific middleware options
- Laravel 11.x and 12.x support
- PHP 8.2+ support

[Unreleased]: https://github.com/pxl-no/laravel-query-binding/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pxl-no/laravel-query-binding/releases/tag/v1.0.0
