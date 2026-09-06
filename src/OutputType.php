<?php

declare(strict_types=1);

namespace GotTheFlag\Loom;

enum OutputType: string {
	case Text = "text";
	case Identifier = "identifier";
	case Path = "path";
}
