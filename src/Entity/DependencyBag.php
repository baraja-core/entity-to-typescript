<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Entity;


use Baraja\EntityToTypescript\ClassAlias\ClassAliasResolver;
use Baraja\EntityToTypescript\ClassAlias\Resolver;

final class DependencyBag
{
	/**
	 * Rewrite full-class name to Typescript alias.
	 *
	 * @var array<class-string, string>
	 */
	public array $classToAlias = [];

	/**
	 * @var array<class-string, array<int, Property>>
	 */
	public array $classToProperties = [];

	private Resolver $classAliasResolver;


	public function __construct(?Resolver $classAliasResolver = null)
	{
		$this->classAliasResolver = $classAliasResolver ?? new ClassAliasResolver;
	}


	public function addClass(string $class, ?string $alias = null): void
	{
		$this->classToAlias[$class] = $this->classAliasResolver->resolve($alias ?? $class, $this->classToAlias);
	}


	public function addClassProperty(string $class, Property $property): void
	{
		if (isset($this->classToProperties[$class]) === false) {
			$this->classToProperties[$class] = [];
		}
		$this->classToProperties[$class][] = $property;
	}


	public function isTypeRegistered(string $class): bool
	{
		return isset($this->classToAlias[$class]);
	}


	public function getEntityAlias(string $class): string
	{
		return $this->classToAlias[$class] ?? $class;
	}
}
