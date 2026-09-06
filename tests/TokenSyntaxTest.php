<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\Loom;
use PHPUnit\Framework\TestCase;

final class TokenSyntaxTest extends TestCase {
	public function test_unknown_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Unknown token [<missing>].",
		);

		Loom::format("hello-<missing>");
	}

	public function test_empty_value_token_name_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<name>",
			["" => "world"],
		);
	}

	public function test_numeric_value_token_name_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"plain",
			[0 => "world"],
		);
	}

	public function test_invalid_token_value_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Token [name] must contain a scalar or null value.",
		);

		Loom::format(
			"<name>",
			["name" => ["world"]],
		);
	}

	public function test_snake_kebab_and_numeric_suffix_token_names_are_supported(): void {
		$this->assertSame(
			"user-riyadh-1-build2",
			Loom::format(
				"<user_id>-<region-1>-<build2>",
				[
					"user_id" => "user",
					"region-1" => "riyadh-1",
					"build2" => "build2",
				],
			),
		);
	}

	public function test_token_values_are_not_reparsed(): void {
		$this->assertSame(
			"<strong>Hello</strong>",
			Loom::format(
				"<message>",
				["message" => "<strong>Hello</strong>"],
			),
		);
	}

	public function test_invalid_pattern_token_name_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<user name>",
			["user name" => "Khaled"],
		);
	}

	public function test_token_name_cannot_start_with_number(): void {
		$this->expectException(LoomException::class);

		Loom::format("<123>");
	}

	public function test_token_name_cannot_contain_dot(): void {
		$this->expectException(LoomException::class);

		Loom::format("<user.name>");
	}

	public function test_empty_token_is_rejected(): void {
		$this->expectException(LoomException::class);

		Loom::format("<>");
	}

	public function test_unclosed_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage("Malformed token syntax.");

		Loom::format("<name");
	}

	public function test_unopened_token_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage("Malformed token syntax.");

		Loom::format("name>");
	}

	public function test_nested_token_syntax_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage("Malformed token syntax.");

		Loom::format(
			"<<name>>",
			["name" => "value"],
		);
	}
}
