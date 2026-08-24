<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
use App\Models\Planes;
use App\Services\Clientes\ClienteCsvImportService;
use App\Services\Clientes\ClienteLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientesImportController extends Controller
{
    private const CARPETA_TEMPORAL = 'imports/clientes';

    public function create()
    {
        $this->autorizar();

        $this->limpiarArchivosViejos();

        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin
            ? (request()->filled('id_gimnasio') ? (int) request()->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();

        $gimnasios = $esSuperAdmin ? Gimnasios::where('estado', 1)->orderBy('nombre')->get() : null;
        $planes = Planes::where('estado', 1)
            ->when($idGimnasio, fn ($q) => $q->where('id_gimnasio', $idGimnasio))
            ->orderBy('nombre')
            ->get();

        return view('clientes.importar', compact('gimnasios', 'idGimnasio', 'planes', 'esSuperAdmin'));
    }

    public function plantilla()
    {
        $this->autorizar();

        $encabezados = array_keys(ClienteCsvImportService::COLUMNAS);
        $ejemplo = [
            'Juana', 'Pérez', 'Soto', 'juana.perez@correo.com', '+56912345678',
            '12.345.678-9', 'Femenino', '1990-05-14', '2026-08-24',
            'Los Aromos 123', 'Santiago', '1.65', 'Recomendación', 'Mensual', '',
        ];

        $lineas = [
            implode(',', $encabezados),
            implode(',', array_map(fn ($v) => str_contains($v, ',') ? '"' . $v . '"' : $v, $ejemplo)),
        ];

        return response(implode("\n", $lineas))
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="plantilla_clientes.csv"');
    }

    public function previsualizar(Request $request, ClienteCsvImportService $importService)
    {
        $this->autorizar();

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:5120',
            'id_gimnasio' => 'nullable|integer|exists:gimnasios,id',
            'id_plan_defecto' => 'nullable|integer|exists:planes,id',
        ]);

        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin
            ? (int) $request->input('id_gimnasio', 0) ?: null
            : Gimnasios::gimnasioActualId();

        if ($esSuperAdmin && ! $idGimnasio) {
            return back()->withErrors(['id_gimnasio' => 'Selecciona el gimnasio destino.']);
        }

        $uuid = (string) Str::uuid();
        $rutaRelativa = self::CARPETA_TEMPORAL . '/' . $uuid . '.csv';
        Storage::disk('local')->putFileAs(
            self::CARPETA_TEMPORAL,
            $request->file('archivo'),
            $uuid . '.csv',
        );

        $parseado = $importService->parseFile(Storage::disk('local')->path($rutaRelativa));

        $faltantes = array_diff(
            array_keys(array_filter(ClienteCsvImportService::COLUMNAS)),
            $parseado['encabezados'],
        );
        if (! empty($faltantes)) {
            Storage::disk('local')->delete($rutaRelativa);
            return back()->withErrors([
                'archivo' => 'Faltan columnas obligatorias en el archivo: ' . implode(', ', $faltantes) . '.',
            ]);
        }

        $idPlanDefecto = $request->filled('id_plan_defecto') ? (int) $request->input('id_plan_defecto') : null;
        $resultado = $importService->validarFilas($parseado['filas'], (int) $idGimnasio, $idPlanDefecto);

        session([
            'import_clientes' => [
                'uuid' => $uuid,
                'id_gimnasio' => $idGimnasio,
                'id_plan_defecto' => $idPlanDefecto,
                'total_filas' => count($parseado['filas']),
            ],
        ]);

        return view('clientes.importar-preview', [
            'validas' => $resultado['validas'],
            'invalidas' => $resultado['invalidas'],
            'totalFilas' => count($parseado['filas']),
        ]);
    }

    public function store(Request $request, ClienteCsvImportService $importService, ClienteLifecycleService $lifecycle)
    {
        $this->autorizar();

        $datosImport = session('import_clientes');
        if (! $datosImport || ($datosImport['uuid'] ?? null) !== $request->input('uuid')) {
            return redirect()->route('clientes.importar')
                ->withErrors(['archivo' => 'La sesión de importación expiró o no coincide. Sube el archivo de nuevo.']);
        }

        $ruta = Storage::disk('local')->path(self::CARPETA_TEMPORAL . '/' . $datosImport['uuid'] . '.csv');
        if (! file_exists($ruta)) {
            return redirect()->route('clientes.importar')
                ->withErrors(['archivo' => 'El archivo temporal ya no existe. Sube el archivo de nuevo.']);
        }

        // Se revalida contra el estado actual de la base (no la del momento de
        // la previsualización, que pudo haber sido minutos antes) — si algo
        // cambió mientras tanto (otro admin creó un cliente con el mismo
        // email, por ejemplo), esa fila se re-clasifica como inválida en vez
        // de fallar la inserción a medio camino.
        $parseado = $importService->parseFile($ruta);
        $resultado = $importService->validarFilas(
            $parseado['filas'],
            (int) $datosImport['id_gimnasio'],
            $datosImport['id_plan_defecto'],
        );

        $importado = $importService->importarFilasValidas($resultado['validas'], $lifecycle);

        Storage::disk('local')->delete(self::CARPETA_TEMPORAL . '/' . $datosImport['uuid'] . '.csv');
        session()->forget('import_clientes');

        $mensaje = "{$importado['creadas']} cliente(s) importado(s) correctamente.";
        if (! empty($resultado['invalidas']) || ! empty($importado['fallidas'])) {
            $totalOmitidas = count($resultado['invalidas']) + count($importado['fallidas']);
            $mensaje .= " {$totalOmitidas} fila(s) se omitieron por errores de datos.";
        }

        return redirect()->route('clientes.index')->with('success', $mensaje);
    }

    private function autorizar(): void
    {
        $usuario = Auth::user();
        $puede = in_array((int) $usuario->id_tipo_usuario, [1, 10], true)
            || (int) $usuario->id_clasificacion === 3;

        if (! $puede) {
            abort(403, 'No tienes permiso para importar clientes.');
        }
    }

    private function limpiarArchivosViejos(): void
    {
        $disco = Storage::disk('local');
        if (! $disco->exists(self::CARPETA_TEMPORAL)) {
            return;
        }

        foreach ($disco->files(self::CARPETA_TEMPORAL) as $archivo) {
            if ($disco->lastModified($archivo) < now()->subHours(6)->timestamp) {
                $disco->delete($archivo);
            }
        }
    }
}
