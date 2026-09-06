<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Validation;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\OutputType;

/** @internal */
final class OutputValidator {
	public static function validate(
		string $output,
		OutputType $type,
	): void {
		match ($type) {
			OutputType::Text => null,
			OutputType::Identifier => self::validateIdentifier($output),
			OutputType::Path => PathValidator::validate($output),
		};
	}

	private static function validateIdentifier(string $output): void {
		if (
			$output === ""
			|| preg_match("/\A[A-Za-z0-9._+-]+\z/", $output) !== 1
		) {
			throw new LoomException("Generated identifier is unsafe.");
		}
	}
}
