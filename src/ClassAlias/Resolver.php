<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\ClassAlias;


interface Resolver
{
	/**
	 * Converts the fully qualified class name to its TypeScript equivalent,
	 * which must never contain illegal characters and must be unique.
	 * The method verifies the uniqueness of each alias so that a duplicate name is never returned.
	 *
	 * @param class-string $className
	 * @param array<class-string, string> $context used aliases (className => alias)
	 */
	public function resolve(string $className, array $context): string;
}
