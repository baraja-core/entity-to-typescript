<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\ClassAlias;


interface Resolver
{
	/**
	 * @param array<class-string, string> $context used aliases (className => alias)
	 */
	public function resolve(string $className, array $context): string;
}
