<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Formatting;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use GotTheFlag\Loom\Exceptions\LoomException;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/** @internal */
final class TokenFormatter {
	private const TOKEN_NAME_PATTERN = "/\A[A-Za-z][A-Za-z0-9_-]*\z/";

	private const DATE_FORMATS = [
		"date" => "Y-m-d",
		"datetime" => "Ymd\\THis\\Z",
		"timestamp" => "U",
		"year" => "Y",
		"month" => "m",
		"day" => "d",
		"hour" => "H",
		"minute" => "i",
		"second" => "s",
	];

	/**
	 * @param array<string, scalar|null> $values
	 */
	public static function format(
		string $pattern,
		array $values = [],
		?DateTimeInterface $at = null,
	): string {
		$requiredTokens = self::extractTokenNames($pattern);
		$resolved = self::normalizeValues($values);
		$utcAt = null;

		foreach ($requiredTokens as $name) {
			if (array_key_exists($name, $resolved)) {
				continue;
			}

			if (array_key_exists($name, self::DATE_FORMATS)) {
				$utcAt ??= self::normalizeDateTime($at);
				$resolved[$name] = $utcAt->format(self::DATE_FORMATS[$name]);
				continue;
			}

			$resolved[$name] = match ($name) {
				"uuid" => Uuid::v4()->toRfc4122(),
				"ulid" => (new Ulid())->toBase32(),
				default => throw new LoomException(
					"Unknown token [<{$name}>].",
				),
			};
		}

		$tokens = [];

		foreach ($requiredTokens as $name) {
			$tokens["<{$name}>"] = $resolved[$name];
		}

		return strtr($pattern, $tokens);
	}

	/**
	 * @param array<mixed, mixed> $values
	 * @return array<string, string>
	 */
	private static function normalizeValues(array $values): array {
		$normalized = [];

		foreach ($values as $name => $value) {
			self::assertValidTokenName($name);

			if (!is_scalar($value) && $value !== null) {
				throw new LoomException(
					"Token [{$name}] must contain a scalar or null value.",
				);
			}

			$normalized[$name] = match (true) {
				$value === null => "",
				is_bool($value) => $value ? "1" : "0",
				default => (string) $value,
			};
		}

		return $normalized;
	}

	/** @return list<string> */
	private static function extractTokenNames(string $pattern): array {
		$result = preg_match_all(
			"/<([^<>]*)>/",
			$pattern,
			$matches,
		);

		if ($result === false) {
			throw new LoomException("Unable to parse token pattern.");
		}

		$names = [];

		foreach ($matches[1] as $name) {
			self::assertValidTokenName($name);
			$names[] = $name;
		}

		$remaining = preg_replace(
			"/<[^<>]*>/",
			"",
			$pattern,
		);

		if (
			$remaining === null
			|| str_contains($remaining, "<")
			|| str_contains($remaining, ">")
		) {
			throw new LoomException("Malformed token syntax.");
		}

		return array_values(array_unique($names));
	}

	private static function assertValidTokenName(mixed $name): void {
		if (
			!is_string($name)
			|| preg_match(self::TOKEN_NAME_PATTERN, $name) !== 1
		) {
			$display = is_scalar($name) || $name === null
				? (string) $name
				: get_debug_type($name);

			throw new LoomException(
				"Invalid token name [{$display}].",
			);
		}
	}

	private static function normalizeDateTime(
		?DateTimeInterface $at,
	): DateTimeImmutable {
		$utc = new DateTimeZone("UTC");

		if ($at === null) {
			return new DateTimeImmutable("now", $utc);
		}

		return DateTimeImmutable::createFromInterface($at)
			->setTimezone($utc);
	}
}
