<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'owner']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        if ($role instanceof Role && in_array($role->name, Role::BUILT_IN)) {
            $nameRule = ['required', 'string', 'max:100', Rule::in([$role->name])];
        } else {
            $nameRule = ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role instanceof Role ? $role->getKey() : null)];
        }

        return [
            'name' => $nameRule,
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
