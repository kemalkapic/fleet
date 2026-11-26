<?php

namespace App\Service\Vehicle;

final class VehicleServiceCatalog
{
    public const TYPE_SMALL = 'small';
    public const TYPE_LARGE = 'large';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public function types(): array
    {
        return [
            self::TYPE_SMALL => 'Mali servis',
            self::TYPE_LARGE => 'Veliki servis',
            self::TYPE_EMERGENCY => 'Izvanredni servis',
            self::TYPE_OTHER => 'Ostali servis',
        ];
    }

    public function typeLabel(string $type): string
    {
        return $this->types()[$type] ?? 'Ostali servis';
    }

    /**
     * @return array<string, string>
     */
    public function itemsFor(string $type): array
    {
        return match ($type) {
            self::TYPE_SMALL => [
                'engine_oil' => 'Motorno ulje',
                'oil_filter' => 'Filter ulja',
                'air_filter' => 'Filter zraka',
                'fuel_filter' => 'Filter goriva',
                'air_dryer_filter' => 'Filter isušivač zraka',
                'cabin_filter' => 'Filter kabine',
                'fluid_check' => 'Kontrola i dopuna tečnosti',
                'brake_check' => 'Kontrola kočnica',
                'tire_check' => 'Kontrola guma i pritiska',
            ],
            self::TYPE_LARGE => [
                'engine_oil' => 'Motorno ulje',
                'oil_filter' => 'Filter ulja',
                'air_filter' => 'Filter zraka',
                'fuel_filter' => 'Filter goriva',
                'cabin_filter' => 'Filter kabine',
                'timing_set' => 'Set zupčastog remena ili lanca',
                'water_pump' => 'Vodena pumpa',
                'auxiliary_belt' => 'PK remen i natezači',
                'coolant' => 'Rashladna tečnost',
                'gearbox_oil' => 'Ulje mjenjača',
                'brake_fluid' => 'Kočiona tečnost',
                'spark_plugs' => 'Svjećice ili grijači',
                'brakes_suspension' => 'Pregled kočnica i ovjesa',
            ],
            self::TYPE_EMERGENCY => [
                'diagnostics' => 'Dijagnostika kvara',
                'engine_repair' => 'Popravak motora',
                'brake_repair' => 'Popravak kočnica',
                'suspension_repair' => 'Popravak ovjesa ili upravljača',
                'electrical_repair' => 'Elektrika i elektronika',
                'tire_repair' => 'Gume ili felge',
                'body_repair' => 'Limarija ili stakla',
                'towing' => 'Vuča vozila',
            ],
            default => [
                'air_conditioning' => 'Servis klima uređaja',
                'wheel_alignment' => 'Geometrija trapa',
                'battery' => 'Akumulator',
                'vehicle_wash' => 'Pranje ili detailing',
                'accessories' => 'Dodatna oprema',
                'inspection' => 'Pregled vozila',
            ],
        };
    }

    /**
     * @param list<string> $submittedItems
     *
     * @return list<string>
     */
    public function validSelectedItems(string $type, array $submittedItems): array
    {
        $allowedItems = $this->itemsFor($type);

        return array_values(array_unique(array_filter(
            $submittedItems,
            static fn (mixed $item): bool => is_string($item) && array_key_exists($item, $allowedItems),
        )));
    }

    public function itemLabel(string $type, string $item): string
    {
        return $this->itemsFor($type)[$item] ?? $item;
    }
}
