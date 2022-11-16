<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


use Baraja\EntityToTypescript\Entity\DependencyBag;
use Baraja\EntityToTypescript\Entity\Property;

final class Renderer
{
	private const PhpTypeToTypescriptDefinition = [
		'int' => 'number',
		'float' => 'number',
		'bool' => 'boolean',
		'string' => 'string',
		'array' => 'unknown[]',
		'object' => 'unknown[]',
		'iterable' => 'unknown[]',
		'void' => 'never',
		'null' => 'null',
		'mixed' => 'any',
		'false' => 'false',
		'never' => 'never',
	];


	public static function renderNativeType(string $type): string
	{
		$type = (string) preg_replace('/\|null$/', '', $type);
		if (isset(self::PhpTypeToTypescriptDefinition[$type])) {
			return self::PhpTypeToTypescriptDefinition[$type];
		}
		if ($type === 'array') {
			return 'unknown[]';
		}

		return $type;
	}


	public function render(DependencyBag $bag): string
	{
		$return = '';
		foreach ($bag->classToAlias as $class => $interfaceName) {
			$properties = [];
			foreach ($bag->classToProperties[$class] ?? [] as $property) {
				$properties[] = $this->renderProperty($property, $bag);
			}
			$return .= $this->createInterface($interfaceName, implode("\n", $properties));
		}

		return trim($return);
	}


	private function createInterface(string $name, string $content = ''): string
	{
		return sprintf(
			'export interface %s {%s}' . "\n\n",
			TypescriptHelpers::firstUpper($name),
			$content !== ''
				? "\n  " . implode("\n  ", explode("\n", trim($content))) . "\n"
				: "\n  // empty definition\n",
		);
	}


	private function renderProperty(Property $property, DependencyBag $bag): string
	{
		$description = '';
		if ($property->description !== null && $property->description !== '') {
			$description .= $property->description;
		}
		if (($property->type === 'unknown' || $property->type === 'unknown[]') && $property->annotation !== null) {
			$description .= '  ' . $property->annotation;
		}

		return sprintf(
			'%s%s: %s;%s',
			$property->name,
			$property->nullable ? '?' : '',
			str_replace('?', 'unknown', $this->renderPropertyType($property, $bag)),
			trim($description) !== '' ? sprintf(' // %s', trim(str_replace("\n", ' ', $description))) : '',
		);
	}


	private function renderPropertyType(Property $property, DependencyBag $bag): string
	{
		if ($property->typescript !== null) {
			return $property->typescript;
		}
		if ($property->referencingRealType !== null) {
			return $bag->getEntityAlias($property->referencingRealType);
		}

		return self::renderNativeType($property->type ?? 'any');
	}
}
