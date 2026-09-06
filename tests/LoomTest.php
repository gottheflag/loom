<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
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
}
