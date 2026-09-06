<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\Loom;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use GotTheFlag\Loom\OutputType;

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
				at: new DateTimeImmutable("2026-06-09 UTC"),
			),
		);
	}

	public function test_it_generates_uuid(): void {
		$result = Loom::format("<uuid>");

		$this->assertMatchesRegularExpression(
			'/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
			$result,
		);
	}

	public function test_it_generates_ulid(): void {
		$result = Loom::format("<ulid>");

		$this->assertMatchesRegularExpression(
			'/\A[0-9A-HJKMNP-TV-Z]{26}\z/',
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

	public function test_text_output_allows_arbitrary_text(): void {
		$this->assertSame(
			"Hello, world! 👋",
			Loom::format(
				"Hello, <name>! 👋",
				["name" => "world"],
			),
		);
	}

	public function test_identifier_output_accepts_portable_identifier(): void {
		$this->assertSame(
			"DEP-2026_prod.1",
			Loom::format(
				"DEP-<version>",
				["version" => "2026_prod.1"],
				OutputType::Identifier,
			),
		);
	}

	public function test_identifier_output_rejects_path(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Generated identifier is unsafe.",
		);

		Loom::format(
			"releases/<name>",
			["name" => "stable"],
			OutputType::Identifier,
		);
	}

	public function test_path_output_accepts_nested_path(): void {
		$this->assertSame(
			"2026/09/release.zip",
			Loom::format(
				"<year>/<month>/<file>",
				["file" => "release.zip"],
				OutputType::Path,
				new DateTimeImmutable("2026-09-06 UTC"),
			),
		);
	}

	public function test_path_output_rejects_traversal(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<directory>/../secret.txt",
			["directory" => "files"],
			OutputType::Path,
		);
	}

	public function test_path_output_rejects_reserved_windows_names(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Generated path contains a reserved segment.",
		);

		Loom::format(
			"uploads/CON.txt",
			type: OutputType::Path,
		);
	}

	public function test_token_values_may_contain_angle_brackets(): void {
		$this->assertSame(
			"<strong>Hello</strong>",
			Loom::format(
				"<message>",
				["message" => "<strong>Hello</strong>"],
			),
		);
	}

	public function test_snake_and_kebab_token_names_are_supported(): void {
		$this->assertSame(
			"user-riyadh-1",
			Loom::format(
				"<user_id>-<region-1>",
				[
					"user_id" => "user",
					"region-1" => "riyadh-1",
				],
			),
		);
	}

	public function test_invalid_token_name_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<user name>",
			["user name" => "Khaled"],
		);
	}

	public function test_empty_token_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format("<>");
	}

	public function test_unclosed_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Malformed token syntax.",
		);

		Loom::format("<name");
	}

	public function test_unopened_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Malformed token syntax.",
		);

		Loom::format("name>");
	}

	public function test_nested_token_syntax_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<<name>>",
			["name" => "value"],
		);
	}
}
