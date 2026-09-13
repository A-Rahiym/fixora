<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, module: string, label: string}>
     */
    private array $permissions = [
        ['name' => 'repairs.view', 'module' => 'repairs', 'label' => 'View repairs'],
        ['name' => 'repairs.create', 'module' => 'repairs', 'label' => 'Create repairs'],
        ['name' => 'repairs.update', 'module' => 'repairs', 'label' => 'Update repairs'],
        ['name' => 'repairs.delete', 'module' => 'repairs', 'label' => 'Delete repairs'],
        ['name' => 'repairs.assign', 'module' => 'repairs', 'label' => 'Assign technicians'],
        ['name' => 'inventory.view', 'module' => 'inventory', 'label' => 'View inventory'],
        ['name' => 'inventory.create', 'module' => 'inventory', 'label' => 'Create inventory items'],
        ['name' => 'inventory.update', 'module' => 'inventory', 'label' => 'Update inventory items'],
        ['name' => 'inventory.delete', 'module' => 'inventory', 'label' => 'Delete inventory items'],
        ['name' => 'inventory.adjust', 'module' => 'inventory', 'label' => 'Adjust stock'],
        ['name' => 'payments.view', 'module' => 'payments', 'label' => 'View payments'],
        ['name' => 'payments.create', 'module' => 'payments', 'label' => 'Record payments'],
        ['name' => 'reports.view', 'module' => 'reports', 'label' => 'View reports'],
        ['name' => 'staff.view', 'module' => 'staff', 'label' => 'View staff'],
        ['name' => 'staff.create', 'module' => 'staff', 'label' => 'Create staff'],
        ['name' => 'staff.update', 'module' => 'staff', 'label' => 'Update staff'],
        ['name' => 'staff.disable', 'module' => 'staff', 'label' => 'Disable staff'],
        ['name' => 'roles.view', 'module' => 'staff', 'label' => 'View roles'],
        ['name' => 'roles.update', 'module' => 'staff', 'label' => 'Update roles'],
    ];

    /**
     * @var array<string, array<int, string>|string>
     */
    private array $rolePermissions = [
        'owner' => '*',
        'manager' => [
            'repairs.view', 'repairs.create', 'repairs.update', 'repairs.delete', 'repairs.assign',
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.adjust',
            'payments.view', 'payments.create',
            'reports.view',
            'staff.view', 'staff.create', 'staff.update', 'staff.disable',
            'roles.view',
        ],
        'cashier' => [
            'repairs.view',
            'inventory.view',
            'payments.view', 'payments.create',
        ],
        'technician' => [
            'repairs.view', 'repairs.update', 'repairs.assign',
            'inventory.view',
        ],
        'storekeeper' => [
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.adjust',
        ],
    ];

    /**
     * @var array<string, string>
     */
    private array $roleLabels = [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'cashier' => 'Cashier',
        'technician' => 'Technician',
        'storekeeper' => 'Storekeeper',
    ];

    /**
     * Seed roles, permissions, and a default owner.
     */
    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['module' => $permission['module'], 'label' => $permission['label']],
            );
        }

        foreach ($this->rolePermissions as $name => $granted) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $this->roleLabels[$name]],
            );

            $names = $granted === '*'
                ? Permission::pluck('name')->all()
                : $granted;

            $role->permissions()->sync(Permission::whereIn('name', $names)->pluck('id')->all());
        }

        $ownerRole = Role::where('name', 'owner')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'owner@fixora.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $ownerRole->id,
                'is_active' => true,
            ],
        );
    }
}
