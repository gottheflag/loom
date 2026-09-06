<?php

declare(strict_types=1);

namespace GotTheFlag\Loom;

use GotTheFlag\Loom\Exceptions\LoomException;

final class Loom {
	public static function format(
		string $pattern,
		array $values = [],
	): string {
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
