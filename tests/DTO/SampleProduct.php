<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Test\DTO;


final class SampleProduct
{
	public int $id;

	public string $name;

	public ?string $description = null;

	public ?SampleCountry $availableCountry = null;
}
