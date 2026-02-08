<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Supervisor = 'supervisor';
    case Employee = 'employee';
    case User = 'user'; // Rol por defecto, similar a tu 'empleado'
}
