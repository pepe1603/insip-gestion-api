<?php

namespace App\Http\Requests\Vacaciones;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVacacionesRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize()
    {
        // Aquí puedes agregar la lógica de autorización si es necesario
        return true;
    }

    /**
     * Obtener las reglas de validación que se aplicarán a la solicitud.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string|max:255',
            'estado_solicitud_id' => 'sometimes|required',
        ];
    }

    /**
     * Obtener los mensajes de error personalizados para las reglas de validación.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'observacioness.string' => 'El observaciones debe ser un texto válido.',
            'observaciones.max' => 'El observaciones no puede exceder los 255 caracteres.',
            'estado_solicitud_id.exists' => 'El estado de solicitud es requerido.',
        ];
    }
}
