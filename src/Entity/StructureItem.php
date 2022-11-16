<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Entity;


final class StructureItem
{
	/**
	 * @param class-string|null $type
	 */
	public function __construct(
		public ?string $code = null,
		public ?string $type = null,
		public ?string $key = null,
		public ?string $value = null,
	) {
	}
}
