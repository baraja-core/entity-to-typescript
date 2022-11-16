<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


final class PropertyTypeResolver
{
	public static function resolvePropertyType(\ReflectionProperty $property): ?string
	{
		$classType = self::getPropertyType($property);
		if ($classType !== null) {
			return $classType;
		}
		$classType = self::parseAnnotation($property, 'var');
		if ($classType !== null) {
			if (str_starts_with($classType, 'array') || str_ends_with($classType, '[]')) {
				return 'array';
			}
			$expanded = self::expandClassName($classType, self::getPropertyDeclaringClass($property));

			return class_exists($expanded) ? $expanded : null;
		}

		return null;
	}


	/**
	 * Returns the type of given property and normalizes `self` and `parent` to the actual class names.
	 * If the property does not have a type, it returns null.
	 */
	public static function getPropertyType(\ReflectionProperty $prop): ?string
	{
		return $prop->getType() instanceof \ReflectionNamedType
			? self::normalizeType($prop->getType()->getName(), $prop)
			: null;
	}


	/**
	 * Returns an annotation value.
	 *
	 * @param \ReflectionFunctionAbstract|\ReflectionProperty|\ReflectionClass $ref
	 */
	public static function parseAnnotation(\Reflector $ref, string $name): ?string
	{
		if (!self::areCommentsAvailable()) {
			throw new \RuntimeException('You have to enable phpDoc comments in opcode cache.');
		}
		$re = '#[\s*]@' . preg_quote($name, '#') . '(?=\s|$)(?:[ \t]+([^@\s]\S*))?#';
		$docComment = (string) $ref->getDocComment();
		if ($docComment !== '' && preg_match($re, trim($docComment, '/*'), $m) === 1) {
			return $m[1] ?? '';
		}

		return null;
	}


	/**
	 * Returns a reflection of a class or trait that contains a declaration of given property. Property can also be
	 * declared in the trait.
	 */
	public static function getPropertyDeclaringClass(\ReflectionProperty $prop): \ReflectionClass
	{
		foreach ($prop->getDeclaringClass()->getTraits() as $trait) {
			if ($trait->hasProperty($prop->name)
				// doc-comment guessing as workaround for insufficient PHP reflection
				&& $trait->getProperty($prop->name)->getDocComment() === $prop->getDocComment()
			) {
				return self::getPropertyDeclaringClass($trait->getProperty($prop->name));
			}
		}

		return $prop->getDeclaringClass();
	}


	/**
	 * Expands the name of the class to full name in the given context of given class.
	 * Thus, it returns how the PHP parser would understand $name if it were written in the body of the class $context.
	 */
	public static function expandClassName(string $name, \ReflectionClass $context): string
	{
		$lower = strtolower($name);
		if ($name === '') {
			throw new \InvalidArgumentException('Class name must not be empty.');
		}
		if (isset(TypescriptHelpers::BuiltinTypes[$lower])) {
			return $lower;
		}
		if ($lower === 'self' || $lower === 'static') {
			return $context->name;
		}
		if ($name[0] === '\\') { // fully qualified name
			return ltrim($name, '\\');
		}

		$uses = self::getUseStatements($context);
		$parts = explode('\\', $name, 2);
		if (isset($uses[$parts[0]])) {
			$parts[0] = $uses[$parts[0]];

			return implode('\\', $parts);
		}
		if ($context->inNamespace()) {
			return $context->getNamespaceName() . '\\' . $name;
		}

		return $name;
	}


	/**
	 * Finds out if reflection has access to PHPdoc comments. Comments may not be available due to the opcode cache.
	 */
	private static function areCommentsAvailable(): bool
	{
		static $res;

		try {
			return $res ?? $res = (bool) (new \ReflectionMethod(__METHOD__))->getDocComment();
		} catch (\ReflectionException $e) {
			throw new \RuntimeException('Reflection is broken: ' . $e->getMessage(), 500, $e);
		}
	}


	/**
	 * @param \ReflectionMethod|\ReflectionParameter|\ReflectionProperty $reflection
	 */
	private static function normalizeType(string $type, $reflection): string
	{
		$lower = strtolower($type);
		$declaringClass = $reflection->getDeclaringClass();
		if ($declaringClass === null) {
			return $type;
		}
		if ($lower === 'self' || $lower === 'static') {
			return $declaringClass->name;
		}
		if ($lower === 'parent') {
			$parentClass = $declaringClass->getParentClass();
			if ($parentClass instanceof \ReflectionClass) {
				return $parentClass->name;
			}
		}

		return $type;
	}


	/** @return string[] of [alias => class] */
	private static function getUseStatements(\ReflectionClass $class): array
	{
		if ($class->isAnonymous()) {
			throw new \LogicException('Anonymous classes are not supported.');
		}
		static $cache = [];
		if (!isset($cache[$name = $class->name])) {
			if ($class->isInternal()) {
				$cache[$name] = [];
			} else {
				$code = (string) file_get_contents((string) $class->getFileName());
				$cache = self::parseUseStatements($code, $name) + $cache;
			}
		}

		return $cache[$name];
	}


	/**
	 * Parses PHP code to [class => [alias => class, ...]]
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function parseUseStatements(string $code, string $forClass = null): array
	{
		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			trigger_error($e->getMessage(), E_USER_NOTICE);
			$tokens = [];
		}
		$level = 0;
		$classLevel = 0;
		$namespace = $class = null;
		$res = $uses = [];

		$nameTokens = PHP_VERSION_ID < 80_000
			? [T_STRING, T_NS_SEPARATOR]
			: [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

		while ($token = current($tokens)) {
			next($tokens);
			switch (is_array($token) ? $token[0] : $token) {
				case T_NAMESPACE:
					$namespace = ltrim(self::fetch($tokens, $nameTokens) . '\\', '\\');
					$uses = [];
					break;

				case T_CLASS:
				case T_INTERFACE:
				case T_TRAIT:
					/** @phpstan-ignore-next-line */
					if ($name = self::fetch($tokens, T_STRING)) {
						$class = $namespace . $name;
						$classLevel = $level + 1;
						$res[$class] = $uses;
						if ($class === $forClass) {
							return $res;
						}
					}
					break;

				case T_USE:
					/** @phpstan-ignore-next-line */
					while (!$class && ($name = self::fetch($tokens, $nameTokens))) {
						$name = ltrim($name, '\\');
						/** @phpstan-ignore-next-line */
						if (self::fetch($tokens, '{')) {
							while ($suffix = self::fetch($tokens, $nameTokens)) {
								/** @phpstan-ignore-next-line */
								if (self::fetch($tokens, T_AS)) {
									$uses[self::fetch($tokens, T_STRING)] = $name . $suffix;
								} else {
									$tmp = explode('\\', $suffix);
									$uses[end($tmp)] = $name . $suffix;
								}
								/** @phpstan-ignore-next-line */
								if (!self::fetch($tokens, ',')) {
									break;
								}
							}
							/** @phpstan-ignore-next-line */
						} elseif (self::fetch($tokens, T_AS)) {
							$uses[self::fetch($tokens, T_STRING)] = $name;
						} else {
							$tmp = explode('\\', $name);
							$uses[end($tmp)] = $name;
						}
						/** @phpstan-ignore-next-line */
						if (!self::fetch($tokens, ',')) {
							break;
						}
					}
					break;

				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case '{':
					$level++;
					break;

				case '}':
					if ($level === $classLevel) {
						$classLevel = 0;
						$class = null;
					}
					$level--;
			}
		}

		return $res;
	}


	/**
	 * @param mixed[]|string|int $take
	 * @phpstan-ignore-next-line
	 */
	private static function fetch(array &$tokens, array|string|int $take): ?string
	{
		$res = null;
		while ($token = current($tokens)) {
			[$token, $s] = is_array($token) ? $token : [$token, $token];
			if (in_array($token, (array) $take, true)) {
				$res .= $s;
			} elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], true)) {
				break;
			}
			next($tokens);
		}

		return $res;
	}
}
