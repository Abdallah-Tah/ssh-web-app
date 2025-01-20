<?php

namespace App\Http\Requests\SSH;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'command' => ['required', 'string'],
            'is_long_running' => ['boolean'],
            'hostname' => ['required_if:is_long_running,true'],
            'username' => ['required_if:is_long_running,true'],
            'password' => ['required_if:is_long_running,true'],
        ];
    }
}
