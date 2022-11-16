export interface SampleEntity {
	countryEntity: SampleCountry;
	unknown: any;
	myNumber: number;
	thisInstance: SampleEntity;
	annoted: SampleProduct;
	annotedArray: SampleProduct[];
	annotedArraySecond: SampleProduct[];
	annotedArrayThird: Record<string, SampleProduct>;
	annotedArrayForth: Record<number, number>;
	annotedArraySixth: Record<string, string>;
	annotedArraySeven: Record<number, Record<number, Record<string, number>>>;
}

export interface SampleCountry {
	id: number;
	name: string;
	code: string;
	eu: boolean;
	cities: Record<number, string>;
}

export interface SampleProduct {
	id: number;
	name: string;
	description?: string;
	availableCountry?: SampleCountry;
}
