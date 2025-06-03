<?php

namespace App\Http\Requests\Vacaciones;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacacionesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            // 'estado_solicitud_id' NO DEBE ser 'required' aquí para la creación.
            // El backend lo asignará a 'PENDIENTE'. Si lo recibes para edición,
            // entonces 'sometimes' es adecuado, pero no para 'store'.
            // Lo eliminamos por completo de aquí para la creación.

            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],

            // Cambia 'motivo' a 'observaciones' para que coincida con el frontend
            'observaciones' => ['nullable', 'string', 'max:255'],

            // Aunque el frontend envía 'dias_vacaciones_solicitados', tu servicio lo recalcula.
            // Si quieres validarlo como un número entero mínimo 1 (aunque el servicio lo sobreescribe),
            // lo puedes dejar como 'nullable'. Si tu servicio realmente es la única fuente de verdad
            // para este campo, podrías incluso no validarlo aquí.
            'dias_vacaciones_solicitados' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empleado_id.required' => 'El campo empleado es obligatorio.',
            'empleado_id.exists' => 'El empleado seleccionado no existe.',
            // Elimina el mensaje de estado_solicitud_id si ya no lo validas como requerido
            // 'estado_solicitud_id.required' => 'El campo estado de solicitud es obligatorio.',
            // 'estado_solicitud_id.exists' => 'El estado de solicitud seleccionado no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            // Actualiza los mensajes para 'observaciones'
            'observaciones.string' => 'Las observaciones deben ser un texto válido.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 255 caracteres.',
        ];
    }
}
