<?php

namespace App\Enums;

enum UserRole: string
{
    case Medico = 'Medico';
    case Secretario = 'Secretario';
    case Tecnico = 'Tecnico';

    public function label(): string
    {
        return match ($this) {
            self::Medico => 'Médico',
            self::Secretario => 'Secretário',
            self::Tecnico => 'Técnico',
        };
    }

    public function requiresCrmCoren(): bool
    {
        return $this !== self::Secretario;
    }
}
