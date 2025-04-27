<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Sucursale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $users = User::where(DB::raw("users.name || ' ' || users.email || ' ' || COALESCE(users.phone,'')"), 'ilike', '%' . $search . '%')->orderBy('id', 'desc')->get();

        $users_json = UserResource::collection($users);

        return response()->json([
            'users' => $users_json
        ]);
    }

    public function config()
    {
        $sucursales = Sucursale::all();
        $roles = Role::all();
        return response()->json([
            'sucursales' => $sucursales->map(function ($su) {
                return [
                    'id' => $su->id,
                    'name' => $su->name
                ];
            }),
            'roles' => $roles->map(function ($ro) {
                return [
                    'id' => $ro->id,
                    'name' => $ro->name
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $is_user_exists = User::where('email', $request->email)->first();
        if ($is_user_exists) {
            return response()->json([
                'status' => 403,
                'message' => 'user exists'
            ]);
        }

        if ($request->hasFile('image')) {
            $path = Storage::putFile('users', $request->file('image'));
            $request->request->add(['avatar' => $path]);
        }

        $request->request->add(['password' => bcrypt($request->password)]);

        $user = User::create($request->all());
        $role = Role::FindOrFail($request->role_id);
        $user->assignRole($role);

        $user_json = new UserResource($user);

        return response()->json([
            'status' => 201,
            'data' => $user_json
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
    public function update(Request $request, string $id)
    {
        $is_user_exists = User::where('email', $request->email)->where('id', '<>', $id)->first();
        if ($is_user_exists) {
            return response()->json([
                'status' => 403,
                'message' => 'user exists'
            ]);
        }

        $user = User::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile('users', $request->file('image'));
            $request->request->add(['avatar' => $path]);
        }

        if ($request->password) {
            $request->request->add(['password' => bcrypt($request->password)]);
        }

        $user->update($request->all());
        if ($request->role_id != $user->role_id) {

            $role_old =  Role::FindOrFail($user->role_id);
            $user->removeRole($role_old);

            $role_new = Role::FindOrFail($request->role_id);
            $user->assignRole($role_new);
        }


        $user_json = new UserResource($user);

        return response()->json([
            'status' => 200,
            'data' => $user_json
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'status' => 203,
            'message' => 'Deleted user'
        ]);
    }
}
