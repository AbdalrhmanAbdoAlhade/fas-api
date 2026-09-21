<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Hotels
            ['hotels.view',     'hotels', 'view',     'عرض الفنادق', 'View hotels'],
            ['hotels.create',   'hotels', 'create',   'إنشاء فندق',  'Create hotel'],
            ['hotels.update',   'hotels', 'update',   'تعديل فندق',  'Update hotel'],
            ['hotels.delete',   'hotels', 'delete',   'حذف فندق',    'Delete hotel'],
            ['hotels.block',    'hotels', 'block',    'حظر فندق',    'Block hotel'],

            // Properties
            ['properties.view',   'properties', 'view',   'عرض العقارات', 'View properties'],
            ['properties.create', 'properties', 'create', 'إنشاء عقار',   'Create property'],
            ['properties.update', 'properties', 'update', 'تعديل عقار',   'Update property'],
            ['properties.delete', 'properties', 'delete', 'حذف عقار',     'Delete property'],
            ['properties.block',  'properties', 'block',  'حظر عقار',     'Block property'],

            // Companies
            ['companies.view',   'companies', 'view',   'عرض الشركات', 'View companies'],
            ['companies.create', 'companies', 'create', 'إنشاء شركة',  'Create company'],
            ['companies.update', 'companies', 'update', 'تعديل شركة',  'Update company'],
            ['companies.delete', 'companies', 'delete', 'حذف شركة',    'Delete company'],
            ['companies.block',  'companies', 'block',  'حظر شركة',    'Block company'],

            // Offers
            ['offers.view',   'offers', 'view',   'عرض العروض', 'View offers'],
            ['offers.create', 'offers', 'create', 'إنشاء عرض',  'Create offer'],
            ['offers.update', 'offers', 'update', 'تعديل عرض',  'Update offer'],
            ['offers.delete', 'offers', 'delete', 'حذف عرض',    'Delete offer'],
            ['offers.block',  'offers', 'block',  'حظر عرض',    'Block offer'],

            // Bookings
            ['bookings.view',   'bookings', 'view',   'عرض الحجوزات', 'View bookings'],
            ['bookings.create', 'bookings', 'create', 'إنشاء حجز',    'Create booking'],
            ['bookings.update', 'bookings', 'update', 'تعديل حجز',    'Update booking'],
            ['bookings.delete', 'bookings', 'delete', 'حذف حجز',      'Delete booking'],
            ['bookings.cancel', 'bookings', 'cancel', 'إلغاء حجز',    'Cancel booking'],

            // Payments
            ['payments.view',   'payments', 'view',   'عرض المدفوعات', 'View payments'],
            ['payments.refund', 'payments', 'refund', 'استرجاع مبلغ',  'Refund payment'],

            // Employees
            ['employees.view',   'employees', 'view',   'عرض الموظفين', 'View employees'],
            ['employees.create', 'employees', 'create', 'إنشاء موظف',   'Create employee'],
            ['employees.update', 'employees', 'update', 'تعديل موظف',   'Update employee'],
            ['employees.delete', 'employees', 'delete', 'حذف موظف',     'Delete employee'],

            // Blockings
            ['blockings.view',   'blockings', 'view',   'عرض سجل الحظر', 'View blockings'],
            ['blockings.manage', 'blockings', 'manage', 'إدارة الحظر',   'Manage blockings'],
        ];

        foreach ($permissions as [$name, $module, $action, $ar, $en]) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'module'         => $module,
                    'action'         => $action,
                    'description_ar' => $ar,
                    'description_en' => $en,
                ]
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions seeded.');
    }
}