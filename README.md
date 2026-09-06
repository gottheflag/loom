# Loom

Loom is a small, framework-agnostic PHP formatter for tokenized strings, identifiers, and portable relative paths.

It deliberately does one thing: resolve a pattern such as `<year>/<month>/<uuid>.<ext>` into a final string. Loom has no Laravel dependency, performs no filesystem I/O, and is not a general-purpose template engine.

## Requirements

- PHP 8.3+

## Installation

```bash
composer require gottheflag/loom
```

## Basic usage

```php
use GotTheFlag\Loom\Loom;

$id = Loom::format(
    "DEP-<year><month><day>-<region>",
    ["region" => "riyadh"],
);
```

Custom values are scalar values or `null`. Booleans become `1` or `0`, and `null` becomes an empty string.

## Built-in tokens

Loom provides these tokens when the caller does not override them:

| Token | Value |
| --- | --- |
| `<uuid>` | RFC 4122 UUID v4 |
| `<ulid>` | ULID |
| `<date>` | UTC date as `Y-m-d` |
| `<datetime>` | UTC datetime as `YmdTHisZ` |
| `<timestamp>` | Unix timestamp |
| `<year>` | Four-digit UTC year |
| `<month>` | Two-digit UTC month |
| `<day>` | Two-digit UTC day |
| `<hour>` | Two-digit UTC hour (`00`-`23`) |
| `<minute>` | Two-digit UTC minute |
| `<second>` | Two-digit UTC second |

All date/time tokens in one `format()` call are derived from the same instant. Repeated generated tokens such as `<uuid>` also resolve to the same value within that call.

For deterministic formatting, pass any `DateTimeInterface` implementation. Loom copies it and normalizes it to UTC without mutating the caller's object:

```php
use DateTimeImmutable;
use GotTheFlag\Loom\Loom;

$value = Loom::format(
    "<year><month><day><hour><minute>",
    at: new DateTimeImmutable("2026-06-09 04:22:00+03:00"),
);

// 202606090122
```

## Custom values and overrides

Caller values override built-ins intentionally:

```php
$value = Loom::format(
    "<year>-<environment>",
    [
        "year" => "FY26",
        "environment" => "prod",
    ],
);

// FY26-prod
```

This also means domain-specific tokens stay outside Loom. A storage package can supply `<ext>` itself instead of making file extensions a Loom concern:

```php
use GotTheFlag\Loom\Loom;
use GotTheFlag\Loom\OutputType;

$key = Loom::format(
    "<year>/<month>/<uuid>.<ext>",
    ["ext" => "png"],
    type: OutputType::Path,
);
```

## Output types

By default, Loom returns text without applying output restrictions:

```php
Loom::format(
    "Hello, <name>! 👋",
    ["name" => "world"],
);
```

For outputs that need a stricter portable shape, use `OutputType`.

### Identifier

`OutputType::Identifier` requires a non-empty ASCII identifier containing only letters, digits, `.`, `_`, `+`, and `-`.

```php
Loom::format(
    "DEP-<version>",
    ["version" => "2026_prod.1+hotfix"],
    type: OutputType::Identifier,
);
```

### Path

`OutputType::Path` validates a portable, relative, forward-slash-delimited path. It rejects traversal, absolute paths, empty segments, backslashes, unsafe characters, Windows reserved device names, trailing-dot segments, and segments longer than 255 bytes.

```php
Loom::format(
    "releases/<year>/<file>",
    ["file" => "build.zip"],
    type: OutputType::Path,
);
```

Path validation is lexical only. Loom never reads, writes, resolves, or canonicalizes filesystem paths. Applications may impose additional limits for their own storage backend or filesystem.

## Token syntax

Token names are case-sensitive and must:

- start with an ASCII letter;
- contain only ASCII letters, digits, `_`, or `-` after the first character.

Valid examples:

```text
<name>
<user_id>
<region-1>
<build2>
```

Angle brackets are reserved for token syntax in the pattern. Token **values** are not reparsed, so a value may safely contain strings such as `<strong>Hello</strong>`.

Unknown tokens, malformed token syntax, invalid token names, invalid values, or unsafe typed outputs throw `GotTheFlag\Loom\Exceptions\LoomException`.

## Public API

```php
Loom::format(
    string $pattern,
    array $values = [],
    OutputType $type = OutputType::Text,
    ?DateTimeInterface $at = null,
): string;
```

The classes under `Formatting` and `Validation` are internal implementation details. The supported public surface is `Loom`, `OutputType`, and `LoomException`.

## Development

Install dependencies:

```bash
composer update
```

Run the strict test suite:

```bash
composer test
```

Audit dependencies:

```bash
composer audit --abandoned=fail
```

CI validates Composer metadata, audits dependencies, tests PHP 8.3-8.5 on Linux and Windows, exercises the lowest supported dependency set, and performs a production-only install smoke test.

## License

Apache License 2.0. See [LICENSE](LICENSE).
