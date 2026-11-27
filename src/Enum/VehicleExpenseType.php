<?php

namespace App\Enum;

enum VehicleExpenseType: string
{
    case REGISTRATION = 'registration';
    case PERIODIC_INSPECTION = 'periodic_inspection';
    case LEASING_INSTALLMENT = 'leasing_installment';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::REGISTRATION => 'Registracija vozila',
            self::PERIODIC_INSPECTION => 'Periodični tehnički',
            self::LEASING_INSTALLMENT => 'Rata za lizing',
            self::OTHER => 'Ostalo',
        };
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $type) {
            $labels[$type->value] = $type->label();
        }

        return $labels;
    }
}
