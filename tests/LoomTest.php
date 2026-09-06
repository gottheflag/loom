<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\Loom;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

final class LoomTest extends TestCase {
	public function test_it_formats_values(): void {
		$this->assertSame(
			"hello-world",
			Loom::format(
				"hello-<name>",
				["name" => "world"],
			),
		);
	}

	public function test_it_formats_multiple_values(): void {
		$this->assertSame(
			"DEP-2026-prod-42",
			Loom::format(
				"DEP-<year>-<environment>-<number>",
				[
					"year" => 2026,
					"environment" => "prod",
					"number" => 42,
				],
			),
		);
	}

	public function test_plain_text_is_unchanged(): void {
		$this->assertSame(
			"hello-world",
			Loom::format("hello-world"),
		);
	}

	public function test_unknown_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Unknown token [<missing>].",
		);

		Loom::format("hello-<missing>");
	}

	public function test_invalid_token_name_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<name>",
			["" => "world"],
		);
	}

	public function test_invalid_token_value_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<name>",
			["name" => ["world"]],
		);
	}

	public function test_it_formats_date_and_time_tokens(): void {
		$at = new DateTimeImmutable(
			"2026-06-09 01:22:33 UTC",
		);

		$this->assertSame(
			"2026|06|09|01|22|33|2026-06-09|20260609T012233Z|1780968153",
			Loom::format(
				"<year>|<month>|<day>|<hour>|<minute>|<second>|<date>|<datetime>|<timestamp>",
				at: $at,
			),
		);
	}

	public function test_datetime_is_normalized_to_utc(): void {
		$at = new DateTimeImmutable(
			"2026-06-09 04:22:33+03:00",
		);

		$this->assertSame(
			"20260609T012233Z",
			Loom::format("<datetime>", at: $at),
		);
	}

	public function test_custom_values_override_builtin_tokens(): void {
		$this->assertSame(
			"FY26-release",
			Loom::format(
				"<year>-<name>",
				[
					"year" => "FY26",
					"name" => "release",
				],
				new DateTimeImmutable("2026-06-09 UTC"),
			),
		);
	}
}
