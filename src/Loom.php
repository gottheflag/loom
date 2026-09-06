<?php

declare(strict_types=1);

namespace GotTheFlag\Loom;

use GotTheFlag\Loom\Exceptions\LoomException;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class Loom {
	private const TOKEN_NAME_PATTERN =
	"/\A[A-Za-z][A-Za-z0-9_-]*\z/";

	public static function format(
		string $pattern,
		array $values = [],
		OutputType $type = OutputType::Text,
		?DateTimeImmutable $at = null,
	): string {
		$requiredTokens = self::extractTokenNames($pattern);

		$at = ($at ?? new DateTimeImmutable(
			"now",
			new DateTimeZone("UTC"),
		))->setTimezone(new DateTimeZone("UTC"));

		$values = array_replace(
			[
				"date" => $at->format("Y-m-d"),
				"datetime" => $at->format("Ymd\\THis\\Z"),
				"timestamp" => $at->format("U"),

				"year" => $at->format("Y"),
				"month" => $at->format("m"),
				"day" => $at->format("d"),
				"hour" => $at->format("H"),
				"minute" => $at->format("i"),
				"second" => $at->format("s"),
			],
			$values,
		);

		$generated = [];

		if (
			in_array("uuid", $requiredTokens, true)
			&& !array_key_exists("uuid", $values)
		) {
			$generated["uuid"] = Uuid::v4()->toRfc4122();
		}

		if (
			in_array("ulid", $requiredTokens, true)
			&& !array_key_exists("ulid", $values)
		) {
			$generated["ulid"] = (new Ulid())->toBase32();
		}

		$values = array_replace(
			$generated,
			$values,
		);

		$tokens = [];

		foreach ($values as $name => $value) {
			if (
				!is_string($name)
				|| preg_match(self::TOKEN_NAME_PATTERN, $name) !== 1
			) {
				throw new LoomException(
					"Invalid token name [{$name}].",
				);
			}

			if (!is_scalar($value) && $value !== null) {
				throw new LoomException(
					"Token [{$name}] must contain a scalar or null value.",
				);
			}

			$tokens["<{$name}>"] = match (true) {
				$value === null => "",
				is_bool($value) => $value ? "1" : "0",
				default => (string) $value,
			};
		}

		foreach ($requiredTokens as $name) {
			if (!array_key_exists($name, $values)) {
				throw new LoomException(
					"Unknown token [<{$name}>].",
				);
			}
		}

		$result = strtr($pattern, $tokens);

		self::validateOutput($result, $type);

		return $result;
	}

	private static function validateOutput(
		string $result,
		OutputType $type,
	): void {
		match ($type) {
			OutputType::Text => null,
			OutputType::Identifier => self::validateIdentifier($result),
			OutputType::Path => self::validatePath($result),
		};
	}

	private static function validateIdentifier(string $result): void {
		if (
			$result === ""
			|| preg_match('/\A[A-Za-z0-9._+-]+\z/', $result) !== 1
		) {
			throw new LoomException(
				"Generated identifier is unsafe.",
			);
		}
	}

	private static function validatePath(string $result): void {
		if (
			$result === ""
			|| preg_match('/\A[A-Za-z0-9._+\/-]+\z/', $result) !== 1
		) {
			throw new LoomException(
				"Generated path is unsafe.",
			);
		}

		foreach (explode("/", $result) as $segment) {
			self::validatePathSegment($segment);
		}
	}

	private static function validatePathSegment(string $segment): void {
		if (
			$segment === ""
			|| $segment === "."
			|| $segment === ".."
			|| strlen($segment) > 255
			|| str_ends_with($segment, ".")
		) {
			throw new LoomException(
				"Generated path contains an unsafe segment.",
			);
		}

		if (
			preg_match(
				'/\A(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?\z/i',
				$segment,
			) === 1
		) {
			throw new LoomException(
				"Generated path contains a reserved segment.",
			);
		}
	}

	private static function extractTokenNames(string $pattern): array {
		preg_match_all(
			'/<([^<>]*)>/',
			$pattern,
			$matches,
		);

		$names = [];

		foreach ($matches[1] as $name) {
			if (
				preg_match(
					self::TOKEN_NAME_PATTERN,
					$name,
				) !== 1
			) {
				throw new LoomException(
					"Invalid token name [{$name}].",
				);
			}

			$names[] = $name;
		}

		$remaining = preg_replace(
			'/<[^<>]*>/',
			"",
			$pattern,
		);

		if (
			$remaining === null
			|| str_contains($remaining, "<")
			|| str_contains($remaining, ">")
		) {
			throw new LoomException(
				"Malformed token syntax.",
			);
		}

		return array_values(array_unique($names));
	}
}
