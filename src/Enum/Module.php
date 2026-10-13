<?php

namespace App\Enum;

enum Module: string
{
    case VEHICLES = 'vehicles';
    case DRIVERS = 'drivers';
    case DOCUMENTS = 'documents';
    case REMINDERS = 'reminders';
    case MAINTENANCE = 'maintenance';
    case FUEL = 'fuel';
    case EXPENSES = 'expenses';
    case WORK_ORDERS = 'work_orders';
    case REPORTS = 'reports';
    case ACCOUNTING = 'accounting';
    case VEHICLE_REGISTRATION_SCAN = 'vehicle_registration_scan';
    case DRIVING_LICENCE_SCAN = 'driving_licence_scan';

    public function label(): string
    {
        return match ($this) {
            self::VEHICLES => 'Vozila',
            self::DRIVERS => 'Vozači',
            self::DOCUMENTS => 'Dokumenti',
            self::REMINDERS => 'Podsjetnici',
            self::MAINTENANCE => 'Servisi i održavanje',
            self::FUEL => 'Gorivo',
            self::EXPENSES => 'Troškovi',
            self::WORK_ORDERS => 'Radni nalozi',
            self::REPORTS => 'Izvještaji',
            self::ACCOUNTING => 'Računovodstvo',
            self::VEHICLE_REGISTRATION_SCAN => 'AI skeniranje saobraćajne',
            self::DRIVING_LICENCE_SCAN => 'AI skeniranje vozačke',
        };
    }
}
