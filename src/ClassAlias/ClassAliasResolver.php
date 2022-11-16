<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\ClassAlias;


use Baraja\EntityToTypescript\TypescriptHelpers;

final class ClassAliasResolver implements Resolver
{
	/**
	 * @param array<class-string, string> $context used aliases (className => alias)
	 */
	public function resolve(string $className, array $context): string
	{
		$used = array_flip($context);
		$candidates = [
			(string) preg_replace('~^.*?\\\\([^\\\\]+)$~', '$1', $className),
			$this->fullPathStrategy($className),
		];

		foreach ($candidates as $candidate) {
			if (isset($used[$candidate]) === false) {
				return $candidate;
			}
		}

		return $this->fullPathStrategy($className);
	}


	private function fullPathStrategy(string $className): string
	{
		return TypescriptHelpers::firstUpper(str_replace('\\', '', $className));
	}
}
