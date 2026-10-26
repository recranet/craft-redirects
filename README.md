# Craft Redirects

A redirect management plugin for Craft CMS 5. Manage URL redirects — all from the control panel.

## Features

- **Automatic redirects on slug changes** — when an entry (or any element) gets a new URI, a redirect from the old URI is created automatically (configurable in the plugin settings)
- **Exact & regex redirects** with support for captured groups (`$1`, `$2`, etc.)
- **Full-URL matching** — exact-match redirects can include the domain (`https://example.com/old-page`)
- **Expiry dates** — optionally let a redirect expire automatically, e.g. for temporary campaigns
- **Permissions** — access is controlled by the "Manage redirects" user permission
- **301, 302, 307, 308** status codes
- **Chain detection** — warns when a redirect points to another redirect's source URL
- **Search & sort** — filter and sort redirects by any column
- **Bulk actions** — enable, disable, or delete multiple redirects at once
- **CSV import/export** — import redirects from CSV or export your full list
- **Labels & notes** — organize redirects with optional metadata

## Requirements

- Craft CMS 5.0 or later
- PHP 8.2 or later

## Installation

```bash
composer require recranet/craft-redirects
php craft plugin/install redirects
```

## Usage

After installation, a **Redirects** item appears in the control panel sidebar with three sections:

### Redirects

Create and manage redirects. Each redirect has:

| Field       | Description                                                          |
| ----------- | -------------------------------------------------------------------- |
| From URL    | The path to redirect from (starts with `/`, or a full URL with domain) |
| To URL      | The destination URL                                                  |
| Type        | HTTP status code (301, 302, 307, 308)                                |
| Match Type  | `exact` or `regex`                                                   |
| Priority    | Order for overlapping regex patterns — lower numbers are checked first |
| Label       | Optional label for organization                                      |
| Notes       | Optional notes                                                       |
| Expiry date | Optional — after this date the redirect stops matching               |

**Exact match** redirects are case-insensitive and normalize trailing slashes — `/old-page` and `/old-page/` are treated the same.

**Subfolder sites** (e.g. a German site at `https://example.com/de`): a From URL can be entered with or without the site's base path — `/de/old-page` and `/old-page` both match a request to `https://example.com/de/old-page`. The path without the base path is checked first.

**Query strings are preserved**: `/old-page?utm_source=x` redirects to the destination with `?utm_source=x` appended (merged with `&` if the destination already has a query string).

**Regex match** redirects use PCRE patterns. Captured groups can be referenced in the destination URL:

| From URL             | To URL            | Example                                          |
| -------------------- | ----------------- | ------------------------------------------------ |
| `/blog/(\d{4})/(.*)` | `/articles/$1/$2` | `/blog/2024/my-post` -> `/articles/2024/my-post` |

When multiple regex patterns could match the same URL, the **Priority** field decides the order: lower numbers are checked first. Ties fall back to site-specific before global, then oldest first — so the result is always deterministic.

### Automatic redirects

When an element's URI changes — for example when you edit an entry's slug, or move a structure entry so its children get new URIs — the plugin automatically creates a redirect from the old URI to the new one, scoped to the element's site. It also:

- **Prevents loops** — removes existing redirects whose source matches the new URI
- **Prevents chains** — re-points existing redirects that targeted the old URI directly to the new one
- **Skips drafts and revisions** — only published saves create redirects

This behavior can be disabled — and the status code changed (default 301) — in **Settings → Plugins → Redirects**.

### Permissions

Users need the **Manage redirects** permission (under the "Redirects" heading in user/group permissions) to see and use the plugin in the control panel.

### Import

Import redirects from a CSV file. The import flow provides column mapping and a preview before importing. An example CSV is available for download.
