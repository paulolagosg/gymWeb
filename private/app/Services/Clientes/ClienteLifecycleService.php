<?php

namespace App\Services\Clientes;

use App\Mail\BienvenidaClienteMail;
use App\Mail\RecordatorioInactividad;
use App\Models\Clientes;
use App\Models\TiposUsuarios;
use App\Models\User;
use App\Notifications\ClienteActivadoNotification;
use App\Notifications\RecordatorioInactividadClienteNotification;
use App\Notifications\RecordatorioMorosidadClienteNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClienteLifecycleService
{
    public const APP_DOWNLOAD_URL = 'https://play.google.com/store/apps/details?id=gym.ampaya.cl';

    public function resolveClientePresencialTipoUsuarioId(): int
    {
        return (int) (TiposUsuarios::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(nombre) = ?', ['cliente presencial'])
                    ->orWhere('slug', 'cliente-presencial')
                    ->orWhere('id', 3);
            })
            ->orderByRaw("CASE WHEN slug = 'cliente-presencial' THEN 0 WHEN LOWER(nombre) = 'cliente presencial' THEN 1 ELSE 2 END")
            ->value('id') ?? 3);
    }

    public function resolveOpenGymTipoUsuarioId(): int
    {
        return (int) (TiposUsuarios::query()
            ->where(function ($query) {
                $query->where('id', 5)
                    ->orWhere('slug', 'open-gym')
                    ->orWhereRaw('LOWER(nombre) = ?', ['open gym']);
            })
            ->orderByRaw("CASE WHEN id = 5 THEN 0 WHEN slug = 'open-gym' THEN 1 ELSE 2 END")
            ->value('id') ?? 5);
    }

    public function buildAccessPassword(?string $ci, ?string $email, int $clienteId = 0): string
    {
        return trim((string) ($ci ?: $email ?: ('cliente-' . $clienteId)));
    }

    public function createAccessUserForCliente(Clientes $cliente, ?int $tipoUsuarioId = null): User
    {
        $password = $this->buildAccessPassword($cliente->ci, $cliente->email, (int) $cliente->id);
        $resolvedTipoUsuarioId = $this->resolveClientTipoUsuarioId($tipoUsuarioId);

        $user = User::query()->firstOrNew(['id_cliente' => $cliente->id]);
        $user->name = trim(implode(' ', array_filter([$cliente->nombres, $cliente->paterno, $cliente->materno])));
        $user->email = $cliente->email;
        $user->password = bcrypt($password);
        $user->id_tipo_usuario = $resolvedTipoUsuarioId;
        $user->porcentaje = $user->porcentaje ?? 0;
        $user->id_gimnasio = $cliente->id_gimnasio;
        $user->slug = $user->slug ?: ($cliente->slug ?: Str::slug($user->name . '-' . $cliente->id));
        $user->save();

        return $user;
    }

    public function syncExistingClienteUser(Clientes $cliente, ?int $tipoUsuarioId = null): ?User
    {
        $user = User::query()->where('id_cliente', $cliente->id)->first();

        if (! $user) {
            return null;
        }

        $user->name = trim(implode(' ', array_filter([$cliente->nombres, $cliente->paterno, $cliente->materno])));

        // Solo actualizar email si no lo usa otro usuario distinto (evita romper login de otro cliente)
        $emailConflict = User::query()
            ->where('email', $cliente->email)
            ->where('id', '!=', $user->id)
            ->exists();

        if (! $emailConflict) {
            $user->email = $cliente->email;
        }

        $user->id_gimnasio = $cliente->id_gimnasio;

        if ($tipoUsuarioId !== null) {
            $user->id_tipo_usuario = $tipoUsuarioId;
        }

        if (! $user->slug) {
            $user->slug = $cliente->slug ?: Str::slug($user->name . '-' . $cliente->id);
        }

        $user->save();

        return $user;
    }

    public function sendActivationWelcomeEmail(Clientes $cliente): void
    {
        if (! $cliente->email) {
            return;
        }

        $password = $this->buildAccessPassword($cliente->ci, $cliente->email, (int) $cliente->id);
        Mail::to($cliente->email)->send(new BienvenidaClienteMail($cliente, $password, true, self::APP_DOWNLOAD_URL));
    }

    /**
     * Reenvía las credenciales de acceso a un cliente puntual, bajo demanda,
     * generando una clave nueva aleatoria (a diferencia de la clave de
     * activación automática, que es determinística por CI/email).
     */
    public function resendAccessCredentials(Clientes $cliente): User
    {
        $password = Str::random(10);
        $user = User::query()->firstOrNew(['id_cliente' => $cliente->id]);

        $user->name = trim(implode(' ', array_filter([$cliente->nombres, $cliente->paterno, $cliente->materno])));
        $user->email = $cliente->email;
        $user->password = bcrypt($password);
        if (! $user->id_tipo_usuario) {
            $user->id_tipo_usuario = $this->resolveClientePresencialTipoUsuarioId();
        }
        $user->porcentaje = $user->porcentaje ?? 0;
        $user->id_gimnasio = $cliente->id_gimnasio;
        $user->slug = $user->slug ?: ($cliente->slug ?: Str::slug($user->name . '-' . $cliente->id));
        $user->save();

        Mail::to($cliente->email)->send(new BienvenidaClienteMail($cliente, $password, true, self::APP_DOWNLOAD_URL));

        return $user;
    }

    public function notifyClienteActivated(Clientes $cliente, User $actor): void
    {
        $clienteUser = User::query()->where('id_cliente', $cliente->id)->first();
        $trainer = $cliente->id_usuario ? User::query()->find($cliente->id_usuario) : null;

        if ($clienteUser) {
            $clienteUser->notify(new ClienteActivadoNotification([
                'type' => 'cliente_activado',
                'title' => 'Tu cuenta ya esta activa',
                'message' => 'Bienvenido a Ampaya Gym. Tu cuenta fue activada y ya puedes ingresar a la app con tus datos.',
                'cliente_id' => (int) $cliente->id,
                'cliente_slug' => $cliente->slug,
                'actor_id' => (int) $actor->id,
                'actor_name' => $actor->name,
                'action_url_web' => $cliente->slug ? route('clientes.agenda', $cliente->slug) : route('dashboard'),
                'action_url_app' => '/cliente/portada',
            ]));
        }

        if ($trainer) {
            $trainer->notify(new ClienteActivadoNotification([
                'type' => 'cliente_activado',
                'title' => 'Cliente activado',
                'message' => sprintf('%s %s ya fue activado por %s.', $cliente->nombres, $cliente->paterno, $actor->name),
                'cliente_id' => (int) $cliente->id,
                'cliente_slug' => $cliente->slug,
                'actor_id' => (int) $actor->id,
                'actor_name' => $actor->name,
                'action_url_web' => $cliente->slug ? route('clientes.opciones.portada', $cliente->slug) : route('clientes.index'),
                'action_url_app' => '/admin/cliente/' . ($cliente->slug ?: $cliente->id),
            ]));
        }
    }

    public function notifyMorosidadReminderSent(Clientes $cliente, User $actor, int $overdueInstallmentsCount): void
    {
        $clienteUser = User::query()->where('id_cliente', $cliente->id)->first();

        if (! $clienteUser) {
            return;
        }

        $message = $overdueInstallmentsCount === 1
            ? 'Tienes 1 cuota vencida. Revisa tu cuenta corriente para regularizar tu situacion.'
            : sprintf('Tienes %d cuotas vencidas. Revisa tu cuenta corriente para regularizar tu situacion.', $overdueInstallmentsCount);

        $clienteUser->notify(new RecordatorioMorosidadClienteNotification([
            'type' => 'recordatorio_morosidad',
            'title' => 'Recordatorio de cuotas vencidas',
            'message' => $message,
            'cliente_id' => (int) $cliente->id,
            'cliente_slug' => $cliente->slug,
            'actor_id' => (int) $actor->id,
            'actor_name' => $actor->name,
            'action_url_web' => $cliente->slug ? route('clientes.cuenta_corriente', $cliente->slug) : route('dashboard'),
            'action_url_app' => '/cliente/cuotas',
        ]));
    }

    public function notifyInactividadReminderSent(Clientes $cliente, User $actor, int $diasSinEntrenar): void
    {
        $clienteUser = User::query()->where('id_cliente', $cliente->id)->first();

        if (! $clienteUser) {
            return;
        }

        $message = $diasSinEntrenar === 1
            ? 'Llevas 1 día sin entrenar. ¡Te esperamos de vuelta!'
            : sprintf('Llevas %d días sin entrenar. ¡Te esperamos de vuelta!', $diasSinEntrenar);

        $clienteUser->notify(new RecordatorioInactividadClienteNotification([
            'type' => 'recordatorio_inactividad',
            'title' => '¡Tu entrenador te extraña!',
            'message' => $message,
            'cliente_id' => (int) $cliente->id,
            'cliente_slug' => $cliente->slug,
            'actor_id' => (int) $actor->id,
            'actor_name' => $actor->name,
            'action_url_app' => '/cliente/dashboard',
        ]));
    }

    private function resolveClientTipoUsuarioId(?int $tipoUsuarioId): int
    {
        if ($tipoUsuarioId === 5) {
            return $this->resolveOpenGymTipoUsuarioId();
        }

        if ($tipoUsuarioId !== null && in_array($tipoUsuarioId, [3, 4], true)) {
            return $tipoUsuarioId;
        }

        return $this->resolveClientePresencialTipoUsuarioId();
    }
}
