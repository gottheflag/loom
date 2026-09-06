# Loom

Loom is a small, framework-agnostic PHP formatter for tokenized strings.

Give it a pattern and values, and Loom resolves the tokens into a final string. It stays deliberately small: no framework coupling, no hidden state, and no recursive template evaluation.

## Requirements

- PHP 8.3+

## Installation

```bash
composer require gottheflag/loom
```

## Basic usage

```php
use GotTheFlag\Loom\Loom;

$value = Loom::format(
    "release-<year><month><day>-<region>",
    ["region" => "riyadh"],
);
```

Custom values may be scalars, `null`, or lazy `Closure` values. Booleans become `1` or `0`, and `null` becomes an empty string.

## Lazy values

A token value may be a `Closure`. Loom evaluates it only when that token is actually present in the pattern:

```php
use GotTheFlag\Loom\Loom;

$value = Loom::format(
    "build-<number>-<nonce>",
    [
        "number" => fn () => 42,
        "nonce" => fn () => bin2hex(random_bytes(4)),
    ],
);
```

Lazy values are resolved at most once per token in a single `format()` call, even when the token appears more than once:

```php
$calls = 0;

$value = Loom::format(
    "<counter>-<counter>",
    [
        "counter" => function () use (&$calls) {
            return ++$calls;
        },
    ],
);

// 1-1
```

Unused closures are never invoked. A closure must return a scalar value or `null`. Exceptions thrown by a closure are not swallowed or wrapped by Loom.

Loom intentionally accepts `Closure` rather than generic PHP `callable` values. This keeps ordinary strings such as `"strlen"` as strings instead of unexpectedly executing them.

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

For deterministic date/time formatting, pass any `DateTimeInterface` implementation. Loom copies it and normalizes it to UTC without mutating the caller's object:

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

Caller values override built-ins intentionally, including lazy values:

```php
$value = Loom::format(
    "<year>-<environment>",
    [
        "year" => fn () => "FY26",
        "environment" => "prod",
    ],
);

// FY26-prod
```

This keeps domain-specific tokens outside Loom. Anything that can be represented as an allowed value can be supplied by the caller:

```php
$value = Loom::format(
    "<namespace>:<resource>:<revision>",
    [
        "namespace" => "gtf",
        "resource" => "example",
        "revision" => 7,
    ],
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

For callers that want a stricter output shape, Loom also provides `OutputType`.

### Identifier

`OutputType::Identifier` requires a non-empty ASCII value containing only letters, digits, `.`, `_`, `+`, and `-`.

```php
use GotTheFlag\Loom\OutputType;

Loom::format(
    "DEP-<version>",
    ["version" => "2026_prod.1+hotfix"],
    type: OutputType::Identifier,
);
```

### Path

`OutputType::Path` validates a portable, relative, forward-slash-delimited path. It rejects traversal, absolute paths, empty segments, backslashes, unsafe characters, reserved device names, trailing-dot segments, and segments longer than 255 bytes.

```php
Loom::format(
    "releases/<year>/<file>",
    ["file" => "build.zip"],
    type: OutputType::Path,
);
```

Path validation is lexical only. Loom does not access or resolve the target, and callers may impose additional constraints for their own environment.

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

Unknown tokens, malformed token syntax, invalid token names, invalid values, invalid closure return values, or unsafe typed outputs throw `GotTheFlag\Loom\Exceptions\LoomException`.

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

Run static analysis:

```bash
composer analyse
```

Run the strict test suite:

```bash
composer test
```

Audit dependencies with Composer's native command:

```bash
composer audit --abandoned=fail
```

CI validates Composer metadata, audits dependencies, runs static analysis, tests PHP 8.3-8.5 on Linux and Windows, exercises the lowest supported dependency set, and performs a production-only install smoke test. Dependabot watches both Composer dependencies and GitHub Actions for updates.

## License

Apache License 2.0. See [LICENSE](LICENSE).
