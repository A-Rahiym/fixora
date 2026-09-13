<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Staff\StaffStoreRequest;
use App\Http\Requests\Api\V1\Staff\StaffUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('role.permissions');

        $search = $request->query('search');

        if (is_string($search) && $search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->latest('id')
            ->paginate(15)
            ->through(fn (User $user) => UserResource::make($user)->resolve($request));

        return ApiResponse::ok($users);
    }

    public function store(StaffStoreRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $user->load('role.permissions');

        return ApiResponse::created([
            'user' => new UserResource($user),
        ], 'Staff created.');
    }

    public function show(int $staff): JsonResponse
    {
        $user = User::with('role.permissions')->findOrFail($staff);

        return ApiResponse::ok([
            'user' => new UserResource($user),
        ]);
    }

    public function update(StaffUpdateRequest $request, int $staff): JsonResponse
    {
        $user = User::findOrFail($staff);
        $user->update($request->validated());

        if (array_key_exists('is_active', $request->validated()) && $request->validated()['is_active'] === false) {
            $user->tokens()->delete();
        }

        $user->load('role.permissions');

        return ApiResponse::ok([
            'user' => new UserResource($user),
        ], 'Staff updated.');
    }

    /**
     * Disable rather than remove: flips is_active and revokes tokens.
     */
    public function destroy(int $staff): JsonResponse
    {
        $user = User::findOrFail($staff);
        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return ApiResponse::ok(null, 'Staff disabled.');
    }
}
