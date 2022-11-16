<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Entity;


final class Property
{
	public \ReflectionProperty $ref;

	public string $name;

	public ?string $type = null;

	public bool $nullable = false;

	public ?string $realType = null;

	/** @var class-string|null */
	public ?string $referencingRealType = null;

	public ?string $typescript = null;

	public ?string $description = null;

	public ?string $annotation = null;
}
