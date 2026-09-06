<?php

declare(strict_types=1);

namespace GotTheFlag\Loom\Tests;

use GotTheFlag\Loom\Exceptions\LoomException;
use GotTheFlag\Loom\Loom;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LazyValueTest extends TestCase {
	public function test_closure_value_is_resolved(): void {
		$this->assertSame(
			"build-42",
			Loom::format(
				"build-<number>",
				["number" => fn (): int => 42],
			),
		);
	}

	public function test_closure_value_is_resolved_only_once_for_repeated_token(): void {
		$calls = 0;

		$this->assertSame(
			"1-1-1",
			Loom::format(
				"<counter>-<counter>-<counter>",
				[
					"counter" => function () use (&$calls): int {
						return ++$calls;
					},
				],
			),
		);

		$this->assertSame(1, $calls);
	}

	public function test_unused_closure_is_not_invoked(): void {
		$called = false;

		$this->assertSame(
			"plain",
			Loom::format(
				"plain",
				[
					"unused" => function () use (&$called): string {
						$called = true;

						return "unused";
					},
				],
			),
		);

		$this->assertFalse($called);
	}

	public function test_closure_can_override_builtin_token(): void {
		$this->assertSame(
			"fixed-uuid",
			Loom::format(
				"<uuid>",
				["uuid" => fn (): string => "fixed-uuid"],
			),
		);
	}

	public function test_closure_return_values_use_the_same_scalar_normalization(): void {
		$this->assertSame(
			"1|0|",
			Loom::format(
				"<yes>|<no>|<empty>",
				[
					"yes" => fn (): bool => true,
					"no" => fn (): bool => false,
					"empty" => fn (): null => null,
				],
			),
		);
	}

	public function test_closure_must_return_scalar_or_null(): void {
		$this->expectException(LoomException::class);
		$this->expectExceptionMessage(
			"Closure for token [value] must return a scalar or null value.",
		);

		Loom::format(
			"<value>",
			["value" => fn (): array => ["invalid"]],
		);
	}

	public function test_callable_looking_string_remains_a_string(): void {
		$this->assertSame(
			"strlen",
			Loom::format(
				"<value>",
				["value" => "strlen"],
			),
		);
	}

	public function test_closure_exception_is_not_wrapped(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("boom");

		Loom::format(
			"<value>",
			[
				"value" => static function (): never {
					throw new RuntimeException("boom");
				},
			],
		);
	}
}
