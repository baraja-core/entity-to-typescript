<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Test\DTO;


final class SampleCountry
{
	public int $id;

	public string $name;

	public string $code;

	public bool $eu = false;

	/** @var array<int, string> */
	public array $cities = [];
}
