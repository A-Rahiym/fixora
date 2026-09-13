<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Roles\RoleStoreRequest;
use App\Http\Requests\Api\V1\Roles\RoleUpdateRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::ok([
            'roles' => RoleResource::collection(Role::with('permissions')->get()),
        ]);
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        $role = Role::create($request->only(['name', 'label']));

        if ($request->has('permissions')) {
            $role->permissions()->sync(
                Permission::whereIn('name', $request->validated()['permissions'])->pluck('id')->all()
            );
        }

        $role->load('permissions');

        return ApiResponse::created([
            'role' => new RoleResource($role),
        ], 'Role created.');
    }

    public function update(RoleUpdateRequest $request, int $role): JsonResponse
    {
        $model = Role::findOrFail($role);
        $model->update($request->only(['name', 'label']));

        if ($request->has('permissions')) {
            $model->permissions()->sync(
                Permission::whereIn('name', $request->validated()['permissions'])->pluck('id')->all()
            );
        }

        $model->load('permissions');

        return ApiResponse::ok([
            'role' => new RoleResource($model),
        ], 'Role updated.');
    }

    public function permissions(): JsonResponse
    {
        return ApiResponse::ok([
            'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'),
        ]);
    }
}
