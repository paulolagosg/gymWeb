<?php

namespace App\Support;

use App\Models\Gimnasios;
use Illuminate\Mail\Mailable;

class GymBranding
{
    private const DEFAULT_PRIMARY = '#489ddf';
    private const DEFAULT_SECONDARY = '#3f8ac4';
    private const DEFAULT_TEXT = '#111827';
    private const DEFAULT_MUTED = '#6B7280';

    public static function resolve(?object $source = null): array
    {
        $gimnasio = self::resolveGym($source);
        $displayName = trim((string) ($gimnasio?->nombre ?: config('app.name', 'Ampaya Gym')));
        $primaryColor = self::normalizeHex($gimnasio?->color_primario, self::DEFAULT_PRIMARY);
        $secondaryColor = self::normalizeHex($gimnasio?->color_secundario, self::DEFAULT_SECONDARY);
        $emailHeading = trim((string) ($gimnasio?->email_encabezado ?? ''));
        $emailSignature = trim((string) ($gimnasio?->email_firma ?? ''));
        $emailFooter = trim((string) ($gimnasio?->email_pie ?? ''));
        $fromAddress = (string) config('mail.from.address', 'no-reply@example.com');

        return [
            'display_name' => $displayName,
            'email_heading' => $emailHeading !== '' ? $emailHeading : $displayName,
            'email_signature' => $emailSignature !== '' ? $emailSignature : 'Equipo ' . $displayName,
            'email_footer' => $emailFooter !== '' ? $emailFooter : self::buildFooter($gimnasio, $displayName),
            'primary_color' => $primaryColor,
            'primary_dark' => self::darkenHex($primaryColor, 0.16),
            'secondary_color' => $secondaryColor,
            'soft_color' => self::lightenHex($secondaryColor, 0.88),
            'text_color' => self::DEFAULT_TEXT,
            'muted_color' => self::DEFAULT_MUTED,
            'from_address' => $fromAddress,
            'from_name' => $displayName,
            'reply_to_address' => $gimnasio?->correo_electronico ?: $fromAddress,
            'reply_to_name' => $displayName,
        ];
    }

    public static function applyToMailable(Mailable $mailable, array $brand): Mailable
    {
        $mailable->from($brand['from_address'], $brand['from_name']);

        if (! empty($brand['reply_to_address'])) {
            $mailable->replyTo($brand['reply_to_address'], $brand['reply_to_name']);
        }

        return $mailable;
    }

    private static function resolveGym(?object $source = null): ?Gimnasios
    {
        if ($source instanceof Gimnasios) {
            return $source;
        }

        if (! is_object($source)) {
            return Gimnasios::gimnasioActual();
        }

        if (isset($source->gimnasio) && $source->gimnasio instanceof Gimnasios) {
            return $source->gimnasio;
        }

        $gymId = self::extractGymId($source);

        if ($gymId !== null) {
            return Gimnasios::find($gymId);
        }

        return Gimnasios::gimnasioActual();
    }

    private static function extractGymId(object $source): ?int
    {
        $candidates = [
            $source->id_gimnasio ?? null,
            $source->gimnasio_id ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }

    private static function normalizeHex(?string $value, string $fallback): string
    {
        $trimmed = strtoupper(trim((string) $value));

        if (preg_match('/^#[0-9A-F]{6}$/', $trimmed) === 1) {
            return $trimmed;
        }

        return strtoupper($fallback);
    }

    private static function buildFooter(?Gimnasios $gimnasio, string $displayName): string
    {
        $parts = [];

        if ($gimnasio?->telefono) {
            $parts[] = 'Tel: ' . $gimnasio->telefono;
        }

        if ($gimnasio?->correo_electronico) {
            $parts[] = $gimnasio->correo_electronico;
        }

        if ($gimnasio?->direccion) {
            $parts[] = $gimnasio->direccion;
        }

        if ($gimnasio?->sitio_web) {
            $parts[] = $gimnasio->sitio_web;
        }

        if (empty($parts)) {
            return 'Gracias por confiar en ' . $displayName . '.';
        }

        return implode("\n", $parts);
    }

    private static function darkenHex(string $hex, float $ratio): string
    {
        [$red, $green, $blue] = self::hexToRgb($hex);

        return sprintf(
            '#%02X%02X%02X',
            max(0, (int) round($red * (1 - $ratio))),
            max(0, (int) round($green * (1 - $ratio))),
            max(0, (int) round($blue * (1 - $ratio))),
        );
    }

    private static function lightenHex(string $hex, float $ratio): string
    {
        [$red, $green, $blue] = self::hexToRgb($hex);

        return sprintf(
            '#%02X%02X%02X',
            min(255, (int) round($red + ((255 - $red) * $ratio))),
            min(255, (int) round($green + ((255 - $green) * $ratio))),
            min(255, (int) round($blue + ((255 - $blue) * $ratio))),
        );
    }

    private static function hexToRgb(string $hex): array
    {
        $normalized = ltrim($hex, '#');

        return [
            hexdec(substr($normalized, 0, 2)),
            hexdec(substr($normalized, 2, 2)),
            hexdec(substr($normalized, 4, 2)),
        ];
    }
}
