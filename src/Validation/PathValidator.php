<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Validation;

use GotTheFlag\Loom\Exceptions\LoomException;

/** @internal */
final class PathValidator {
	private const MAX_SEGMENT_BYTES = 255;

	public static function validate(string $path): void {
		if (
			$path === ""
			|| preg_match("/\A[A-Za-z0-9._+\/-]+\z/", $path) !== 1
		) {
			throw new LoomException("Generated path is unsafe.");
		}

		foreach (explode("/", $path) as $segment) {
			self::validateSegment($segment);
		}
	}

	private static function validateSegment(string $segment): void {
		if (
			$segment === ""
			|| $segment === "."
			|| $segment === ".."
			|| strlen($segment) > self::MAX_SEGMENT_BYTES
			|| str_ends_with($segment, ".")
		) {
			throw new LoomException(
				"Generated path contains an unsafe segment.",
			);
		}

		if (
			preg_match(
				"/\A(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?\z/i",
				$segment,
			) === 1
		) {
			throw new LoomException(
				"Generated path contains a reserved segment.",
			);
		}
	}
}
