# API versioning

This document describes how the Hive Finances API is versioned and how deprecation is handled.

## Current version

- **v1** – All company-scoped endpoints use the prefix `/api/v1/`. Example: `GET /api/v1/invoices`.

## Versioning strategy

- **URL path versioning.** The major version is in the path (e.g. `/api/v1/`, `/api/v2/`).
- **Introducing v2.** When breaking changes are required, a new route group will be added under `/api/v2/`. Existing v1 routes remain unchanged. New features that are backward-compatible may be added to v1.
- **No version in URL for auth.** Login, register, and password reset live under `/api/` without a version prefix; these are kept stable.

## Deprecation policy

- When a version is **deprecated**, responses from that version will include:
  - `X-API-Deprecated: true`
  - `X-API-Sunset: YYYY-MM-DD` (date after which the version may be removed)
- **v1** is not deprecated and has no sunset date. Avoid removing or changing response shapes in v1; introduce new endpoints or a new version instead.
- Deprecation announcements will be made in release notes and in CHANGELOG.md.

## Rate limiting

- Authenticated v1 routes: **60 requests per minute** per user (`throttle:60,1`).
- Unauthenticated routes (login, register, forgot-password, reset-password) may have stricter limits.

## Changelog

Breaking changes and deprecations are documented in CHANGELOG.md per version.
