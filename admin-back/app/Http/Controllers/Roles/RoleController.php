<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $roles = Role::where('id', '<>', 1)
            ->where('name', 'ilike', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->get();

        $role_resource = RoleResource::collection($roles);

        return response()->json([
            'roles' => $role_resource
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {

        $exists = Role::where('name', $request->name)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'role exists',
            ]);
        }

        $role = Role::create([
            'name'          => $request->name,
            'guard_name'    => 'api'
        ]);

        // Enlasarlo con los permisos que tenga
        $permissions = $request->permissions;
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        $new_role = new RoleResource($role);

        return response()->json([
            'status' => 201,
            'role' => $new_role
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {
        $exists = Role::where('name', $request->name)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'role exists',
            ]);
        }

        $role = Role::findOrFail($id);

        $role->update([
            "name" => $request->name
        ]);
        $permissions = $request->permissions;
        // Enlazar con los permisos que tenga
        $role->syncPermissions($permissions);

        $new_role = new RoleResource($role);

        return response()->json([
            'status' => 201,
            'role' => $new_role
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return response()->json([
            'message' => 'deleted role'
        ]);
    }
}
