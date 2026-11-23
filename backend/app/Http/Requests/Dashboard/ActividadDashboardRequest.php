<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ActividadDashboardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['limite' => $this->input('limite', 10)]);
    }

    public function rules(): array
    {
        return [
            'sede_id' => ['required', 'integer', 'exists:sedes,id'],
            'limite' => ['required', 'integer', 'between:1,50'],
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'Debe seleccionar una sede.',
            'sede_id.integer' => 'La sede debe ser un identificador válido.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'limite.required' => 'Debe indicar el límite de eventos.',
            'limite.integer' => 'El límite debe ser un número entero.',
            'limite.between' => 'El límite debe estar entre 1 y 50 eventos.',
        ];
    }
}
