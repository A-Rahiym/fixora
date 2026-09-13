<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->map(
                fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'label' => $permission->label,
                ]
            )->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
