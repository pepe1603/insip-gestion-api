<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate; // Importa la fachada Gate
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy', // Aquí se mapean las políticas a los modelos
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('admin', function ($user) {
            return $user->role === UserRole::Admin; // Comparación tipada
        });

        Gate::define('supervisor', function ($user) {
            return $user->role === UserRole::Supervisor || $user->role === UserRole::Admin;
        });

        // Opcional: Un Gate más genérico para verificar cualquier rol
        Gate::define('has-role', function ($user, UserRole $role) {
            return $user->role === $role;
        });

        // Opcional: Para verificar si el usuario es 'employee'
        Gate::define('employee', function ($user) {
            return $user->role === UserRole::Employee;
        });

        // Opcional: Para verificar si el usuario es 'user' (rol por defecto)
        Gate::define('user-role', function ($user) {
            return $user->role === UserRole::User;
        });
    }
}
