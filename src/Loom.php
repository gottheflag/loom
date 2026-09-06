<?php

declare(strict_types=1);

namespace GotTheFlag\Loom;

use DateTimeInterface;
use GotTheFlag\Loom\Formatting\TokenFormatter;
use GotTheFlag\Loom\Validation\OutputValidator;

final class Loom {
	/**
	 * @param array<string, scalar|null> $values
	 */
	public static function format(
		string $pattern,
		array $values = [],
		OutputType $type = OutputType::Text,
		?DateTimeInterface $at = null,
	): string {
		$output = TokenFormatter::format(
			$pattern,
			$values,
			$at,
		);

		OutputValidator::validate($output, $type);

		return $output;
	}
}
