<?php

declare(strict_types=1);

namespace Baraja\EntityToTypescript\Test\DTO;


final class SampleEntity
{
	private SampleCountry $countryEntity;

	private $unknown;

	/** Value from 5 to 23 */
	private int $myNumber;

	/** @var self */
	private $thisInstance;

	/** @var SampleProduct Description */
	private $annoted;

	/** @var SampleProduct[] Another description */
	private $annotedArray;

	/** @var array<SampleProduct> */
	private $annotedArraySecond;

	/** @var array<string, SampleProduct> */
	private $annotedArrayThird;

	/** @var array<int> */
	private $annotedArrayForth;

	/** @var array<string, string> Silence is golden. */
	private $annotedArraySixth;

	/** @var array<int,
	 *    array<bool,    array<string, float>>
	 * > My note. :)
	 */
	private $annotedArraySeven;
}
