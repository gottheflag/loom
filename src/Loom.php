<?php

declare(strict_types=1);

namespace GotTheFlag\Loom;

use GotTheFlag\Loom\Exceptions\LoomException;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class Loom {
	public static function format(
		string $pattern,
		array $values = [],
		?DateTimeImmutable $at = null,
	): string {
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
			str_contains($pattern, "<uuid>")
			&& !array_key_exists("uuid", $values)
		) {
			$generated["uuid"] = Uuid::v4()->toRfc4122();
		}

		if (
			str_contains($pattern, "<ulid>")
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
			if (!is_string($name) || $name === "") {
				throw new LoomException(
					"Token names must be non-empty strings.",
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

		$result = strtr($pattern, $tokens);

		if (preg_match('/<[^<>]+>/', $result, $match) === 1) {
			throw new LoomException(
				"Unknown token [{$match[0]}].",
			);
		}

		return $result;
	}
}
