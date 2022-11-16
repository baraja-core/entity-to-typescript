<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Test;


use Baraja\EntityToTypescript\EntityToTypescript;
use Baraja\EntityToTypescript\Test\DTO\SampleEntity;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

final class EntityToTypescriptTest extends TestCase
{
	public function testSampleEntity(): void
	{
		require_once __DIR__ . '/DTO/SampleCountry.php';
		require_once __DIR__ . '/DTO/SampleProduct.php';
		require_once __DIR__ . '/DTO/SampleEntity.php';

		$service = new EntityToTypescript();

		$this->assertEquals(
			$service->render($service->process(SampleEntity::class)),
			trim(str_replace("\t", '  ', (string) file_get_contents(__DIR__ . '/SampleEntityOutput.ts'))),
		);
	}
}
