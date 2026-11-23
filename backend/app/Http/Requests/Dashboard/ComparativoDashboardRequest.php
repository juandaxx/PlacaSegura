<?php

namespace App\Http\Requests\Dashboard;

use App\Application\Services\PeriodoDashboard;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ComparativoDashboardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $hoy = app(PeriodoDashboard::class)->hoy();
        $this->merge([
            'desde' => $this->input('desde', $hoy),
            'hasta' => $this->input('hasta', $hoy),
        ]);
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $desde = $this->fechaValida('desde');
            $hasta = $this->fechaValida('hasta');
            if ($desde !== null && $hasta !== null && $desde->diff($hasta)->days + 1 > 31) {
                $validator->errors()->add('hasta', 'El periodo no puede superar 31 días operativos.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'desde.required' => 'Debe indicar la fecha inicial.',
            'desde.date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            'hasta.required' => 'Debe indicar la fecha final.',
            'hasta.date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a desde.',
        ];
    }

    private function fechaValida(string $campo): ?DateTimeImmutable
    {
        if ($this->validator->errors()->has($campo)) {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $this->input($campo));

        return $fecha === false ? null : $fecha;
    }
}
