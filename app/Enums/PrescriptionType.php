<?php

namespace App\Enums;

enum PrescriptionType: string
{
    case Simples = 'Simples';
    case ControleEspecial = 'ControleEspecial';

    public function label(): string
    {
        return match ($this) {
            self::Simples => 'Simples',
            self::ControleEspecial => 'Controle Especial',
        };
    }
}
