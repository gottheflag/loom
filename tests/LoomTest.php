<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use DateTime;
use DateTimeImmutable;
use GotTheFlag\Loom\Loom;
use PHPUnit\Framework\TestCase;

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

	public function test_it_formats_multiple_scalar_values(): void {
		$this->assertSame(
			"DEP-2026-prod-42-1-0-",
			Loom::format(
				"DEP-<year>-<environment>-<number>-<yes>-<no>-<empty>",
				[
					"year" => 2026,
					"environment" => "prod",
					"number" => 42,
					"yes" => true,
					"no" => false,
					"empty" => null,
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

	public function test_it_formats_date_and_time_tokens_from_one_instant(): void {
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

	public function test_mutable_datetime_is_supported_without_mutating_it(): void {
		$at = new DateTime("2026-06-09 04:22:33+03:00");

		$this->assertSame(
			"20260609T012233Z",
			Loom::format("<datetime>", at: $at),
		);
		$this->assertSame("+03:00", $at->format("P"));
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
				at: new DateTimeImmutable("2026-06-09 UTC"),
			),
		);
	}

	public function test_it_generates_uuid_v4(): void {
		$result = Loom::format("<uuid>");

		$this->assertMatchesRegularExpression(
			"/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i",
			$result,
		);
	}

	public function test_it_generates_ulid(): void {
		$result = Loom::format("<ulid>");

		$this->assertMatchesRegularExpression(
			"/\A[0-9A-HJKMNP-TV-Z]{26}\z/",
			$result,
		);
	}

	public function test_generated_tokens_can_be_overridden(): void {
		$this->assertSame(
			"fixed-uuid/fixed-ulid",
			Loom::format(
				"<uuid>/<ulid>",
				[
					"uuid" => "fixed-uuid",
					"ulid" => "fixed-ulid",
				],
			),
		);
	}

	public function test_repeated_generated_token_is_stable_within_one_format_call(): void {
		$result = Loom::format("<uuid>|<uuid>");
		[$first, $second] = explode("|", $result);

		$this->assertSame($first, $second);
	}
}
