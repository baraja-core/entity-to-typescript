<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Entity;


final class StructureResponse
{
	public string $sourceLine;

	/** @var array<int, StructureItem> */
	public array $structure = [];

	public ?string $comment = null;
}
