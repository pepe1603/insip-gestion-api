<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        // En este flujo, $token es el hash que Laravel genera por defecto si usas Password::sendResetLink()
        // Pero como estamos generando el token manual, pasaremos nuestro propio token aquí.
        // Sin embargo, para ser coherentes con el contrato de Laravel, el parámetro sigue siendo $token.
        // Lo importante es que en la práctica, a la notificación le envías el código.
        // Vamos a refactorizar ligeramente cómo el AuthController llama a esto.

        // Esta línea ya no será llamada por Password::sendResetLink() directamente en este flujo.
        // Pero la mantendremos si alguna otra parte del sistema la usa.
        // En nuestro AuthController, llamaremos a $user->notify(new ResetPasswordCodeNotification($code));
        $this->notify(new ResetPasswordCodeNotification($token)); // Simplemente asegurando que usa la nueva.
    }
}
