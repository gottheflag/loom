<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\Loom;
use GotTheFlag\Loom\OutputType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OutputTypeTest extends TestCase {
	public function test_text_output_allows_arbitrary_text(): void {
		$this->assertSame(
			"Hello, world! 👋 <strong>safe</strong>",
			Loom::format(
				"Hello, <name>! 👋 <markup>",
				[
					"name" => "world",
					"markup" => "<strong>safe</strong>",
				],
			),
		);
	}

	public function test_text_output_may_be_empty(): void {
		$this->assertSame("", Loom::format(""));
	}

	public function test_identifier_output_accepts_portable_identifier(): void {
		$this->assertSame(
			"DEP-2026_prod.1+hotfix",
			Loom::format(
				"DEP-<version>",
				["version" => "2026_prod.1+hotfix"],
				OutputType::Identifier,
			),
		);
	}

	#[DataProvider("unsafeIdentifierProvider")]
	public function test_identifier_output_rejects_unsafe_values(
		string $identifier,
	): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage("Generated identifier is unsafe.");

		Loom::format(
			"<value>",
			["value" => $identifier],
			OutputType::Identifier,
		);
	}

	/** @return iterable<string, array{string}> */
	public static function unsafeIdentifierProvider(): iterable {
		yield "empty" => [""];
		yield "path" => ["releases/stable"];
		yield "space" => ["release 1"];
		yield "backslash" => ["release\\1"];
		yield "unicode" => ["إصدار"];
	}

	public function test_path_output_accepts_nested_portable_path(): void {
		$this->assertSame(
			"2026/09/release.zip",
			Loom::format(
				"<year>/<month>/<file>",
				[
					"year" => "2026",
					"month" => "09",
					"file" => "release.zip",
				],
				OutputType::Path,
			),
		);
	}

	#[DataProvider("unsafePathProvider")]
	public function test_path_output_rejects_unsafe_paths(string $path): void {
		$this->expectException(LoomException::class);

		Loom::format(
			"<path>",
			["path" => $path],
			OutputType::Path,
		);
	}

	/** @return iterable<string, array{string}> */
	public static function unsafePathProvider(): iterable {
		yield "empty" => [""];
		yield "absolute" => ["/etc/passwd"];
		yield "trailing slash" => ["files/"];
		yield "double slash" => ["files//a.txt"];
		yield "dot segment" => ["files/./a.txt"];
		yield "traversal" => ["files/../secret.txt"];
		yield "backslash" => ["files\\secret.txt"];
		yield "drive prefix" => ["C:/secret.txt"];
		yield "space" => ["files/my file.txt"];
		yield "unicode" => ["files/ملف.txt"];
		yield "trailing dot" => ["files/name."];
	}

	#[DataProvider("reservedWindowsNameProvider")]
	public function test_path_output_rejects_reserved_windows_names(
		string $segment,
	): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Generated path contains a reserved segment.",
		);

		Loom::format(
			"uploads/<segment>",
			["segment" => $segment],
			OutputType::Path,
		);
	}

	/** @return iterable<string, array{string}> */
	public static function reservedWindowsNameProvider(): iterable {
		yield "CON" => ["CON"];
		yield "CON extension" => ["CON.txt"];
		yield "PRN" => ["prn"];
		yield "AUX" => ["AUX.json"];
		yield "NUL" => ["nul"];
		yield "COM1" => ["COM1"];
		yield "COM9 extension" => ["com9.log"];
		yield "LPT1" => ["LPT1"];
		yield "LPT9 extension" => ["lpt9.txt"];
	}

	public function test_path_output_allows_non_reserved_prefixes(): void {
		$this->assertSame(
			"uploads/CONNECTION.txt",
			Loom::format(
				"uploads/<file>",
				["file" => "CONNECTION.txt"],
				OutputType::Path,
			),
		);
	}

	public function test_path_segment_may_be_255_bytes(): void {
		$segment = str_repeat("a", 255);

		$this->assertSame(
			$segment,
			Loom::format(
				"<segment>",
				["segment" => $segment],
				OutputType::Path,
			),
		);
	}

	public function test_path_segment_over_255_bytes_is_rejected(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Generated path contains an unsafe segment.",
		);

		Loom::format(
			"<segment>",
			["segment" => str_repeat("a", 256)],
			OutputType::Path,
		);
	}

	public function test_output_type_has_stable_backed_values(): void {
		$this->assertSame("text", OutputType::Text->value);
		$this->assertSame("identifier", OutputType::Identifier->value);
		$this->assertSame("path", OutputType::Path->value);
	}
}
