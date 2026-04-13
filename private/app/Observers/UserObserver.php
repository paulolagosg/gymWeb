<?php

namespace App\Observers;

use App\Models\User;
use App\Models\HistorialTarifaEntrenador;
use Carbon\Carbon;

class UserObserver
{
    public function updating(User $user)
    {
        // Si cambió individual o duo y es entrenador
        if (($user->isDirty('individual') || $user->isDirty('duo')) && $user->id_tipo_usuario == 2) {
            $now = Carbon::now();

            HistorialTarifaEntrenador::updateOrCreate(
                [
                    'entrenador_id' => $user->id,
                    'year' => $now->year,
                    'month' => $now->month,
                ],
                [
                    'individual' => $user->individual,
                    'duo' => $user->duo,
                ]
            );
        }
    }
}
