<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript;


use Baraja\EntityToTypescript\Entity\DependencyBag;
use Baraja\EntityToTypescript\Entity\Property;
use Baraja\EntityToTypescript\Entity\StructureItem;
use Baraja\EntityToTypescript\Entity\StructureResponse;

final class EntityToTypescript
{
	private StructureResolver $structureResolver;

	private Renderer $renderer;


	public function __construct()
	{
		$this->structureResolver = new StructureResolver;
		$this->renderer = new Renderer;
	}


	public function process(object|string $entity, ?DependencyBag $bag = null): DependencyBag
	{
		$bag ??= new DependencyBag;
		$this->processEntity(is_string($entity) ? $entity : $entity::class, $bag);

		return $bag;
	}


	public function render(DependencyBag $bag): string
	{
		return $this->renderer->render($bag);
	}


	/**
	 * @param class-string $class
	 */
	private function processEntity(string $class, DependencyBag $bag): void
	{
		$ref = new \ReflectionClass($class);
		$bag->addClass($ref->getName());

		foreach ($ref->getProperties() as $property) {
			$property->setAccessible(true);
			$entity = new Property;
			$bag->addClassProperty($ref->getName(), $entity);
			$entity->ref = $property;
			$entity->name = $property->getName();
			$entity->type = $property->getType()?->getName();
			$entity->nullable = (bool) $property->getType()?->allowsNull();

			$realType = $this->resolveRealType($property);
			if (is_string($realType)) {
				$entity->realType = $realType;
				$this->resolveRealTypeAsString($realType, $entity, $bag);
			} else {
				$this->resolveStructuredResponse($realType, $entity, $bag);
			}
		}
	}


	private function resolveRealType(\ReflectionProperty $property): string|StructureResponse
	{
		$type = PropertyTypeResolver::resolvePropertyType($property);
		if ($type === null) {
			return 'unknown';
		}
		if ($type === 'array') {
			return $this->structureResolver->process($property);
		}

		return $type;
	}


	private function resolveRealTypeAsString(string $realType, Property $property, DependencyBag $bag): void
	{
		if ($realType !== 'unknown'
			&& class_exists($realType)
			&& TypescriptHelpers::isBuiltinType($realType) === false
		) {
			$property->referencingRealType = $realType;
			if ($bag->isTypeRegistered($realType) === false) {
				$this->processEntity($realType, $bag);
			}
		}
	}


	private function resolveStructuredResponse(
		StructureResponse $structure,
		Property $property,
		DependencyBag $bag
	): void {
		$return = '';
		foreach ($structure->structure as $item) {
			$code = $item->code;
			if ($code === 'Record') {
				$code = $this->resolveRecordType($item, $bag);
			} elseif ($item->type !== null && class_exists($item->type)) {
				$code = $bag->getEntityAlias($item->type);
			}
			if ($item->type !== null) {
				$this->resolveRealTypeAsString($item->type, $property, $bag);
			}
			$return = str_contains($return, '?')
				? str_replace('?', $code, $return)
				: $return . $code;
		}

		$property->typescript = $return;
	}


	private function resolveRecordType(StructureItem $item, DependencyBag $bag): string
	{
		if ($item->key !== null
			&& $item->key === $item->value
			&& TypescriptHelpers::isBuiltinType($item->key) === false
		) {
			return sprintf('%s[]', $bag->getEntityAlias($item->type));
		}

		$type = ($item->value !== null && $item->type !== null)
			? $bag->getEntityAlias($item->type)
			: ($item->value ?? '?');

		return sprintf(
			'Record<%s, %s>',
			Renderer::renderNativeType($item->key ?? 'number'),
			Renderer::renderNativeType($type),
		);
	}
}
