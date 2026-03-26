<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsDemoSeeder extends Seeder
{
  /**
   * Create the initial roles and permissions.
   */
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissions = [
      'dashboard',

      'register_role',
      'list_role',
      'edit_role',
      'delete_role',

      'register_user',
      'list_user',
      'edit_user',
      'delete_user',

      'settings',

      'register_product',
      'list_product',
      'edit_product',
      'delete_product',
      'show_inventory_product',
      'show_wallet_price_product',

      'register_client',
      'list_client',
      'edit_client',
      'delete_client',

      'register_sale',
      'list_sale',
      'edit_sale',
      'delete_sale',

      'return',

      'register_purchase',
      'list_purchase',
      'edit_purchase',
      'delete_purchase',

      'register_transport',
      'list_transport',
      'edit_transport',
      'delete_transport',

      'conversions',
      'kardex',

      'view_audit_logs',
      'export_audit_logs',

      'register_lead',
      'list_lead',
      'edit_lead',
      'delete_lead',
      'convert_lead',

      'register_opportunity',
      'list_opportunity',
      'edit_opportunity',
      'delete_opportunity',

      'register_crm_activity',
      'list_crm_activity',
      'edit_crm_activity',
      'delete_crm_activity',

      'register_refurbish',
      'list_refurbish',
      'edit_refurbish',
      'delete_refurbish',
    ];

    foreach ($permissions as $permissionName) {
      Permission::findOrCreate($permissionName, 'api');
    }

    // create roles and assign existing permissions
    $role = Role::findOrCreate('Super-Admin', 'api');

    $backfillMap = [
      'list_client' => ['list_lead'],
      'register_client' => ['register_lead', 'convert_lead'],
      'edit_client' => ['edit_lead'],
      'delete_client' => ['delete_lead'],

      'list_sale' => ['list_opportunity', 'list_crm_activity'],
      'register_sale' => ['register_opportunity', 'register_crm_activity'],
      'edit_sale' => ['edit_opportunity', 'edit_crm_activity'],
      'delete_sale' => ['delete_opportunity', 'delete_crm_activity'],

      'list_product' => ['list_refurbish'],
      'register_product' => ['register_refurbish'],
      'edit_product' => ['edit_refurbish'],
      'delete_product' => ['delete_refurbish'],
    ];

    Role::with('permissions')->get()->each(function (Role $existingRole) use ($backfillMap) {
      $currentPermissions = $existingRole->permissions->pluck('name')->all();

      foreach ($backfillMap as $sourcePermission => $targetPermissions) {
        if (!in_array($sourcePermission, $currentPermissions, true)) {
          continue;
        }

        foreach ($targetPermissions as $targetPermission) {
          if (in_array($targetPermission, $currentPermissions, true)) {
            continue;
          }

          $existingRole->givePermissionTo($targetPermission);
          $currentPermissions[] = $targetPermission;
        }
      }
    });

    // gets all permissions via Gate::before rule; see AuthServiceProvider

    // create demo users

    $user = User::firstOrCreate(
      ['email' => 'superadmin@sitecsas.com'],
      [
        'name' => 'Example Super-Admin User',
        'role_id' => 1,
        'sucuarsal_id' => 1,
        'password' => bcrypt('+[z8U]N<<[qyVTDbbp'),
      ]
    );

    if (!$user->hasRole($role->name)) {
      $user->assignRole($role);
    }

    app()[PermissionRegistrar::class]->forgetCachedPermissions();
  }
}

// php artisan migrate:fresh --seed --seeder=PermissionsDemoSeeder
