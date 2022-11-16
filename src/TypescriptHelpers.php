<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


final class TypescriptHelpers
{
	public const BuiltinTypes = [
		'string' => 1, 'int' => 1, 'float' => 1, 'bool' => 1, 'array' => 1, 'object' => 1,
		'callable' => 1, 'iterable' => 1, 'void' => 1, 'null' => 1, 'mixed' => 1, 'false' => 1,
		'never' => 1,
	];


	/**
	 * Determines if type is PHP built-in type. Otherwise, it is the class name.
	 */
	public static function isBuiltinType(string $type): bool
	{
		return isset(self::BuiltinTypes[strtolower($type)]);
	}


	public static function firstUpper(string $s): string
	{
		return strtoupper($s[0] ?? '') . mb_substr($s, 1, null, 'UTF-8');
	}


	public static function resolveCommentAsSingleLine(\ReflectionProperty $property): ?string
	{
		$docComment = str_replace(["\r\n", "\r"], "\n", (string) $property->getDocComment());
		if (str_contains($docComment, '@var') === false) {
			return null;
		}
		$docComment = (string) preg_replace('~/\*+(?:\s|\n)*((?:.|\n)*?)(?:\s|\n)*\*/~', '$1', $docComment);
		$lines = array_map(
			static fn(string $line): string => (string) preg_replace('/^\s*\/?\**\s*(.*)$/', '$1', $line),
			explode("\n", $docComment),
		);

		return (string) preg_replace('/@var\s+(.+)/', '$1', implode('', $lines));
	}
}
