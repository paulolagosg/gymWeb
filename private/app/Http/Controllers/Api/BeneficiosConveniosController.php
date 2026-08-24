<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Gimnasios;
use App\Models\TiendaAliada;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BeneficiosConveniosController extends Controller
{
    private const RUBROS = ['cafeteria', 'farmacia', 'deportes', 'nutricion', 'otros'];
    private const TIPOS = ['porcentaje', 'monto_fijo', 'promocion_cantidad'];
    private const ESTADOS = ['pendiente', 'activo', 'rechazado', 'inactivo', 'aprobado'];

    private function requireAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return null;
    }

    private function requireAdminOrTrainer(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return null;
    }

    private function currentGymId(Request $request): int
    {
        return (int) ($request->user()->id_gimnasio ?? Gimnasios::gimnasioActualId() ?? 1);
    }

    private function adminStoresQuery(Request $request)
    {
        return TiendaAliada::query()->where('id_gimnasio', $this->currentGymId($request));
    }

    private function adminBenefitsQuery(Request $request)
    {
        return Beneficio::query()
            ->with('tienda')
            ->where('id_gimnasio', $this->currentGymId($request));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'si', 'sí', 'on', 'activo'], true);
    }

    private function normalizeRedes(Request $request): array
    {
        $redes = $request->input('redes', []);

        if (is_string($redes)) {
            $decoded = json_decode($redes, true);
            $redes = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($redes)) {
            $redes = [];
        }

        return [
            'instagram' => $this->normalizeNullableString($redes['instagram'] ?? $request->input('instagram')),
            'facebook' => $this->normalizeNullableString($redes['facebook'] ?? $request->input('facebook')),
            'web' => $this->normalizeNullableString($redes['web'] ?? $request->input('web')),
            'whatsapp' => $this->normalizeNullableString($redes['whatsapp'] ?? $request->input('whatsapp')),
        ];
    }

    private function normalizePromotionQuantity(Request $request): ?array
    {
        $payload = $request->input('promocion_cantidad');

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($payload)) {
            $payload = [
                'lleva' => $request->input('promocion_lleva', $request->input('lleva')),
                'paga' => $request->input('promocion_paga', $request->input('paga')),
            ];
        }

        $lleva = $payload['lleva'] ?? null;
        $paga = $payload['paga'] ?? null;

        if ($lleva === null && $paga === null) {
            return null;
        }

        return [
            'lleva' => $lleva !== null && $lleva !== '' ? (int) $lleva : null,
            'paga' => $paga !== null && $paga !== '' ? (int) $paga : null,
        ];
    }

    private function validateStorePayload(Request $request): array
    {
        return Validator::make($request->all(), [
            'nombre_comercial' => 'required_without:name|string|max:150',
            'name' => 'sometimes|nullable|string|max:150',
            'rubro' => ['required_without:category', Rule::in(self::RUBROS)],
            'category' => ['sometimes', 'nullable', Rule::in(self::RUBROS)],
            'correo_contacto' => 'required_without:contact_email|nullable|email|max:150',
            'contact_email' => 'sometimes|nullable|email|max:150',
            'telefono' => 'nullable|string|max:50',
            'contact_phone' => 'sometimes|nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'web' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:5120',
            'image' => 'nullable|image|max:5120',
            'activo' => 'nullable',
            'active' => 'nullable',
            'remove_logo' => 'nullable',
        ])->validate();
    }

    private function validateBenefitPayload(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required_without:id_tienda|integer',
            'id_tienda' => 'sometimes|nullable|integer',
            'titulo' => 'required_without:title|string|max:180',
            'title' => 'sometimes|nullable|string|max:180',
            'descripcion' => 'required_without:description|string',
            'description' => 'sometimes|nullable|string',
            'tipo' => ['required_without_all:tipo_beneficio,benefit_type', Rule::in(self::TIPOS)],
            'tipo_beneficio' => ['sometimes', 'nullable', Rule::in(self::TIPOS)],
            'benefit_type' => ['sometimes', 'nullable', Rule::in(self::TIPOS)],
            'valor' => 'nullable|numeric',
            'value' => 'sometimes|nullable|numeric',
            'condicion' => 'nullable|string',
            'condition' => 'sometimes|nullable|string',
            'promocion_cantidad' => 'nullable|array',
            'promocion_cantidad.lleva' => 'nullable|integer|min:1',
            'promocion_cantidad.paga' => 'nullable|integer|min:0',
            'promocion_lleva' => 'sometimes|nullable|integer|min:1',
            'promocion_paga' => 'sometimes|nullable|integer|min:0',
            'fecha_inicio' => 'required_without:start_date|date',
            'start_date' => 'sometimes|nullable|date',
            'fecha_fin' => 'required_without:end_date|date',
            'end_date' => 'sometimes|nullable|date',
            'codigo_promocional' => 'nullable|string|max:100',
            'codigo' => 'sometimes|nullable|string|max:100',
            'promo_code' => 'sometimes|nullable|string|max:100',
            'terminos_condiciones' => 'nullable|string',
            'terminos' => 'sometimes|nullable|string',
            'terms' => 'sometimes|nullable|string',
            'estado' => ['sometimes', 'nullable', Rule::in(self::ESTADOS)],
            'status' => ['sometimes', 'nullable', Rule::in(self::ESTADOS)],
        ]);

        $validator->after(function ($validator) use ($request) {
            $tipo = $this->normalizeNullableString(
                $request->input('tipo', $request->input('tipo_beneficio', $request->input('benefit_type')))
            );
            $valor = $request->input('valor', $request->input('value'));
            $valor = $valor !== null && $valor !== '' ? (float) $valor : null;
            $fechaInicio = $request->input('fecha_inicio', $request->input('start_date'));
            $fechaFin = $request->input('fecha_fin', $request->input('end_date'));
            $promocion = $this->normalizePromotionQuantity($request);

            if ($fechaInicio && $fechaFin && Carbon::parse($fechaFin)->lt(Carbon::parse($fechaInicio))) {
                $validator->errors()->add('fecha_fin', 'La fecha fin debe ser mayor o igual a la fecha inicio.');
            }

            if ($tipo === 'porcentaje' && ($valor === null || $valor < 1 || $valor > 100)) {
                $validator->errors()->add('valor', 'El porcentaje debe estar entre 1 y 100.');
            }

            if ($tipo === 'monto_fijo' && ($valor === null || $valor <= 0)) {
                $validator->errors()->add('valor', 'El monto fijo debe ser mayor a 0.');
            }

            if ($tipo === 'promocion_cantidad') {
                $lleva = $promocion['lleva'] ?? null;
                $paga = $promocion['paga'] ?? null;

                if ($lleva === null || $paga === null) {
                    $validator->errors()->add('promocion_cantidad', 'La promoción por cantidad requiere los campos lleva y paga.');
                } elseif ($paga >= $lleva) {
                    $validator->errors()->add('promocion_cantidad', 'El valor paga debe ser menor que lleva.');
                }
            }
        });

        return $validator->validate();
    }

    private function serializeStore(TiendaAliada $store): array
    {
        $logoUrl = $store->logo_path ? url(Storage::url($store->logo_path)) : null;

        return [
            'id' => $store->id,
            'nombre_comercial' => $store->nombre_comercial,
            'nombre' => $store->nombre_comercial,
            'commercial_name' => $store->nombre_comercial,
            'rubro' => $store->rubro,
            'correo_contacto' => $store->correo_contacto,
            'email' => $store->correo_contacto,
            'contact_email' => $store->correo_contacto,
            'telefono' => $store->telefono,
            'phone' => $store->telefono,
            'contact_phone' => $store->telefono,
            'direccion' => $store->direccion,
            'address' => $store->direccion,
            'redes' => [
                'instagram' => $store->instagram,
                'facebook' => $store->facebook,
                'web' => $store->web,
                'whatsapp' => $store->whatsapp,
            ],
            'instagram' => $store->instagram,
            'facebook' => $store->facebook,
            'web' => $store->web,
            'whatsapp' => $store->whatsapp,
            'logo_url' => $logoUrl,
            'logo' => $logoUrl,
            'image_url' => $logoUrl,
            'activo' => $store->activo,
            'active' => $store->activo,
            'estado' => $store->activo ? 'activo' : 'inactivo',
            'status' => $store->activo ? 'activo' : 'inactivo',
            'created_at' => $store->created_at?->toDateTimeString(),
            'updated_at' => $store->updated_at?->toDateTimeString(),
        ];
    }

    private function serializeBenefit(Beneficio $beneficio): array
    {
        $tienda = $beneficio->relationLoaded('tienda') ? $beneficio->tienda : $beneficio->tienda()->first();
        $promocion = is_array($beneficio->promocion_cantidad) ? $beneficio->promocion_cantidad : null;

        return [
            'id' => $beneficio->id,
            'store_id' => $beneficio->id_tienda,
            'id_tienda' => $beneficio->id_tienda,
            'tienda' => $tienda ? $this->serializeStore($tienda) : null,
            'store' => $tienda ? $this->serializeStore($tienda) : null,
            'titulo' => $beneficio->titulo,
            'title' => $beneficio->titulo,
            'descripcion' => $beneficio->descripcion,
            'description' => $beneficio->descripcion,
            'tipo' => $beneficio->tipo,
            'tipo_beneficio' => $beneficio->tipo,
            'benefit_type' => $beneficio->tipo,
            'valor' => $beneficio->valor !== null ? (float) $beneficio->valor : null,
            'valor_beneficio' => $beneficio->valor !== null ? (float) $beneficio->valor : null,
            'value' => $beneficio->valor !== null ? (float) $beneficio->valor : null,
            'condicion' => $beneficio->condicion,
            'condition' => $beneficio->condicion,
            'promocion_cantidad' => $promocion,
            'fecha_inicio' => $beneficio->fecha_inicio?->toDateString(),
            'start_date' => $beneficio->fecha_inicio?->toDateString(),
            'fecha_fin' => $beneficio->fecha_fin?->toDateString(),
            'end_date' => $beneficio->fecha_fin?->toDateString(),
            'codigo_promocional' => $beneficio->codigo_promocional,
            'codigo' => $beneficio->codigo_promocional,
            'promo_code' => $beneficio->codigo_promocional,
            'terminos_condiciones' => $beneficio->terminos_condiciones,
            'terminos' => $beneficio->terminos_condiciones,
            'terms' => $beneficio->terminos_condiciones,
            'estado' => $beneficio->estado,
            'status' => $beneficio->estado,
            'created_at' => $beneficio->created_at?->toDateTimeString(),
            'updated_at' => $beneficio->updated_at?->toDateTimeString(),
        ];
    }

    public function adminStoresIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $stores = $this->adminStoresQuery($request)
            ->orderByDesc('activo')
            ->orderBy('nombre_comercial')
            ->get();

        $payload = $stores->map(fn(TiendaAliada $store) => $this->serializeStore($store))->values();

        return response()->json([
            'tiendas' => $payload,
            'stores' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminStoreShow(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $store = $this->adminStoresQuery($request)->find($id);

        if (! $store) {
            return response()->json(['message' => 'Tienda aliada no encontrada.'], 404);
        }

        $payload = $this->serializeStore($store);

        return response()->json([
            'tienda' => $payload,
            'store' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminStoreStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $this->validateStorePayload($request);
        $redes = $this->normalizeRedes($request);
        $logo = $request->file('logo') ?? $request->file('image');

        $store = new TiendaAliada();
        $store->id_gimnasio = $this->currentGymId($request);
        $store->nombre_comercial = trim((string) $request->input('nombre_comercial', $request->input('name')));
        $store->rubro = (string) $request->input('rubro', $request->input('category'));
        $store->correo_contacto = $this->normalizeNullableString($request->input('correo_contacto', $request->input('contact_email')));
        $store->telefono = $this->normalizeNullableString($request->input('telefono', $request->input('contact_phone')));
        $store->direccion = $this->normalizeNullableString($request->input('direccion', $request->input('address')));
        $store->instagram = $redes['instagram'];
        $store->facebook = $redes['facebook'];
        $store->web = $redes['web'];
        $store->whatsapp = $redes['whatsapp'];
        $store->activo = $this->normalizeBoolean($request->input('activo', $request->input('active', true)));

        if ($logo) {
            $store->logo_path = $logo->store('beneficios/tiendas', 'public');
        }

        $store->save();

        $payload = $this->serializeStore($store);

        return response()->json([
            'message' => 'Tienda aliada creada correctamente.',
            'tienda' => $payload,
            'store' => $payload,
            'data' => $payload,
        ], 201);
    }

    public function adminStoreUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $store = $this->adminStoresQuery($request)->find($id);

        if (! $store) {
            return response()->json(['message' => 'Tienda aliada no encontrada.'], 404);
        }

        $this->validateStorePayload($request);
        $redes = $this->normalizeRedes($request);
        $logo = $request->file('logo') ?? $request->file('image');

        $store->nombre_comercial = trim((string) $request->input('nombre_comercial', $request->input('name')));
        $store->rubro = (string) $request->input('rubro', $request->input('category'));
        $store->correo_contacto = $this->normalizeNullableString($request->input('correo_contacto', $request->input('contact_email')));
        $store->telefono = $this->normalizeNullableString($request->input('telefono', $request->input('contact_phone')));
        $store->direccion = $this->normalizeNullableString($request->input('direccion', $request->input('address')));
        $store->instagram = $redes['instagram'];
        $store->facebook = $redes['facebook'];
        $store->web = $redes['web'];
        $store->whatsapp = $redes['whatsapp'];
        $store->activo = $this->normalizeBoolean($request->input('activo', $request->input('active', $store->activo)));

        if ($this->normalizeBoolean($request->input('remove_logo', false)) && $store->logo_path) {
            Storage::disk('public')->delete($store->logo_path);
            $store->logo_path = null;
        }

        if ($logo) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $store->logo_path = $logo->store('beneficios/tiendas', 'public');
        }

        $store->save();

        $payload = $this->serializeStore($store);

        return response()->json([
            'message' => 'Tienda aliada actualizada correctamente.',
            'tienda' => $payload,
            'store' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminStoreDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $store = $this->adminStoresQuery($request)->find($id);

        if (! $store) {
            return response()->json(['message' => 'Tienda aliada no encontrada.'], 404);
        }

        if ($store->logo_path) {
            Storage::disk('public')->delete($store->logo_path);
        }

        $store->delete();

        return response()->json([
            'message' => 'Tienda aliada eliminada correctamente.',
        ]);
    }

    public function adminBenefitsIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrTrainer($request)) return $err;

        $benefits = $this->adminBenefitsQuery($request)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $payload = $benefits->map(fn(Beneficio $beneficio) => $this->serializeBenefit($beneficio))->values();

        return response()->json([
            'beneficios' => $payload,
            'benefits' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminBenefitShow(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrTrainer($request)) return $err;

        $beneficio = $this->adminBenefitsQuery($request)->find($id);

        if (! $beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $payload = $this->serializeBenefit($beneficio);

        return response()->json([
            'beneficio' => $payload,
            'benefit' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminBenefitStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $this->validateBenefitPayload($request);

        $gymId = $this->currentGymId($request);
        $storeId = (int) $request->input('store_id', $request->input('id_tienda'));
        $store = TiendaAliada::query()
            ->where('id_gimnasio', $gymId)
            ->find($storeId);

        if (! $store) {
            return response()->json(['message' => 'La tienda aliada seleccionada no existe.'], 404);
        }

        $tipo = (string) $request->input('tipo', $request->input('tipo_beneficio', $request->input('benefit_type')));
        $valor = $request->input('valor', $request->input('value'));
        $promocion = $this->normalizePromotionQuantity($request);

        $beneficio = new Beneficio();
        $beneficio->id_gimnasio = $gymId;
        $beneficio->id_tienda = $store->id;
        $beneficio->id_usuario_creador = $request->user()->id;
        $beneficio->id_usuario_editor = $request->user()->id;
        $beneficio->titulo = trim((string) $request->input('titulo', $request->input('title')));
        $beneficio->descripcion = trim((string) $request->input('descripcion', $request->input('description')));
        $beneficio->tipo = $tipo;
        $beneficio->valor = $valor !== null && $valor !== '' ? (float) $valor : null;
        $beneficio->condicion = $this->normalizeNullableString($request->input('condicion', $request->input('condition')));
        $beneficio->promocion_cantidad = $tipo === 'promocion_cantidad' ? $promocion : null;
        $beneficio->fecha_inicio = (string) $request->input('fecha_inicio', $request->input('start_date'));
        $beneficio->fecha_fin = (string) $request->input('fecha_fin', $request->input('end_date'));
        $beneficio->codigo_promocional = $this->normalizeNullableString($request->input('codigo_promocional', $request->input('codigo', $request->input('promo_code'))));
        $beneficio->terminos_condiciones = $this->normalizeNullableString($request->input('terminos_condiciones', $request->input('terminos', $request->input('terms'))));
        $beneficio->estado = 'pendiente';
        $beneficio->save();
        $beneficio->load('tienda');

        $payload = $this->serializeBenefit($beneficio);

        return response()->json([
            'message' => 'Beneficio creado correctamente.',
            'beneficio' => $payload,
            'benefit' => $payload,
            'data' => $payload,
        ], 201);
    }

    public function adminBenefitUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $this->validateBenefitPayload($request);

        $beneficio = $this->adminBenefitsQuery($request)->find($id);

        if (! $beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $gymId = $this->currentGymId($request);
        $storeId = (int) $request->input('store_id', $request->input('id_tienda'));
        $store = TiendaAliada::query()
            ->where('id_gimnasio', $gymId)
            ->find($storeId);

        if (! $store) {
            return response()->json(['message' => 'La tienda aliada seleccionada no existe.'], 404);
        }

        $tipo = (string) $request->input('tipo', $request->input('tipo_beneficio', $request->input('benefit_type')));
        $valor = $request->input('valor', $request->input('value'));
        $promocion = $this->normalizePromotionQuantity($request);

        $beneficio->id_tienda = $store->id;
        $beneficio->id_usuario_editor = $request->user()->id;
        $beneficio->titulo = trim((string) $request->input('titulo', $request->input('title')));
        $beneficio->descripcion = trim((string) $request->input('descripcion', $request->input('description')));
        $beneficio->tipo = $tipo;
        $beneficio->valor = $valor !== null && $valor !== '' ? (float) $valor : null;
        $beneficio->condicion = $this->normalizeNullableString($request->input('condicion', $request->input('condition')));
        $beneficio->promocion_cantidad = $tipo === 'promocion_cantidad' ? $promocion : null;
        $beneficio->fecha_inicio = (string) $request->input('fecha_inicio', $request->input('start_date'));
        $beneficio->fecha_fin = (string) $request->input('fecha_fin', $request->input('end_date'));
        $beneficio->codigo_promocional = $this->normalizeNullableString($request->input('codigo_promocional', $request->input('codigo', $request->input('promo_code'))));
        $beneficio->terminos_condiciones = $this->normalizeNullableString($request->input('terminos_condiciones', $request->input('terminos', $request->input('terms'))));
        $beneficio->estado = 'pendiente';
        $beneficio->save();
        $beneficio->load('tienda');

        $payload = $this->serializeBenefit($beneficio);

        return response()->json([
            'message' => 'Beneficio actualizado correctamente.',
            'beneficio' => $payload,
            'benefit' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminBenefitStatusUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $beneficio = $this->adminBenefitsQuery($request)->find($id);

        if (! $beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => ['required_without:status', Rule::in(self::ESTADOS)],
            'status' => ['sometimes', 'nullable', Rule::in(self::ESTADOS)],
        ]);
        $validator->validate();

        $estado = (string) $request->input('estado', $request->input('status'));
        if ($estado === 'aprobado') {
            $estado = 'activo';
        }

        $beneficio->estado = $estado;
        $beneficio->id_usuario_editor = $request->user()->id;
        $beneficio->save();
        $beneficio->load('tienda');

        $payload = $this->serializeBenefit($beneficio);

        return response()->json([
            'message' => 'Estado del beneficio actualizado.',
            'beneficio' => $payload,
            'benefit' => $payload,
            'data' => $payload,
        ]);
    }

    public function adminBenefitDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $beneficio = $this->adminBenefitsQuery($request)->find($id);

        if (! $beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $beneficio->delete();

        return response()->json([
            'message' => 'Beneficio eliminado correctamente.',
        ]);
    }

    public function clienteBenefitsIndex(Request $request): JsonResponse
    {
        $gymId = $this->currentGymId($request);
        $today = Carbon::today();

        $benefits = Beneficio::query()
            ->with('tienda')
            ->where('id_gimnasio', $gymId)
            ->where('estado', 'activo')
            ->whereDate('fecha_inicio', '<=', $today)
            ->whereDate('fecha_fin', '>=', $today)
            ->whereHas('tienda', fn($query) => $query->where('activo', true))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $payload = $benefits->map(fn(Beneficio $beneficio) => $this->serializeBenefit($beneficio))->values();

        return response()->json([
            'beneficios' => $payload,
            'benefits' => $payload,
            'data' => $payload,
        ]);
    }

    public function clienteBenefitShow(Request $request, int $id): JsonResponse
    {
        $beneficio = Beneficio::query()
            ->with('tienda')
            ->where('id_gimnasio', $this->currentGymId($request))
            ->find($id);

        if (! $beneficio) {
            return response()->json(['message' => 'Beneficio no encontrado.'], 404);
        }

        $payload = $this->serializeBenefit($beneficio);

        return response()->json([
            'beneficio' => $payload,
            'benefit' => $payload,
            'data' => $payload,
        ]);
    }
}
