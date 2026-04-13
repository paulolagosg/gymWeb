<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class RutChileno implements ValidationRule
{
    protected $esRut = false;

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!preg_match('/^\d{1,2}\.?\d{3}\.?\d{3}-[\dkK]$/', $value) && !preg_match('/^\d{7,8}-[\dkK]$/', $value)) {
            return;
        }

        $this->esRut = true;
        $rut = preg_replace('/[\.\-]/', '', $value);
        $body = substr($rut, 0, -1);
        $dv = strtoupper(substr($rut, -1));

        if (strlen($body) < 7) {
            $fail($this->message($attribute));
            return;
        }

        $suma = 0;
        $multiplo = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $suma += $body[$i] * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }
        $resto = $suma % 11;
        $dvEsperado = 11 - $resto;
        if ($dvEsperado == 11) $dvEsperado = '0';
        elseif ($dvEsperado == 10) $dvEsperado = 'K';
        else $dvEsperado = (string)$dvEsperado;

        if ($dv != $dvEsperado) {
            $fail($this->message($attribute));
        }
    }

    public function message($attribute = 'ci')
    {
        if ($this->esRut) {
            return 'El campo ' . $attribute . ' no es un RUT chileno válido.';
        }
        return 'El campo ' . $attribute . ' no es válido.';
    }
}
