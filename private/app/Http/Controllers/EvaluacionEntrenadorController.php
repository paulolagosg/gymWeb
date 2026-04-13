<?php
// app/Http/Controllers/EvaluacionEntrenadorController.php
namespace App\Http\Controllers;

use App\Models\EvaluacionEntrenador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class EvaluacionEntrenadorController extends Controller
{
    public function index($slug)
    {
        $entrenador = User::where('slug', $slug)->firstOrFail();
        $promedios = \App\Models\EvaluacionEntrenador::where('id_entrenador', $entrenador->id)
            ->whereRaw("updated_at = (select max(updated_at) from evaluacion_entrenadors ee where ee.id_entrenador = evaluacion_entrenadors.id_entrenador)")
            ->selectRaw('(empatia) as empatia, (escucha_activa) as escucha_activa, (comunicacion) as comunicacion, (anatomia) as anatomia,fisiologia,programacion,poblacion,psicologia,
            round(((empatia+escucha_activa+comunicacion+anatomia+fisiologia+programacion+poblacion+psicologia)/56)*100,2) total')
            ->first();

        $categorias = DB::table('clasificaciones_entrenadores')->where('id', $entrenador->id_clasificacion)->first();
        return view('evaluaciones.index', compact('entrenador', 'promedios', 'categorias'));
    }

    public function create($slug)
    {
        $entrenador = User::where('slug', $slug)->firstOrFail();
        $categorias = DB::table('clasificaciones_entrenadores')->get();

        return view('evaluaciones.create', compact('entrenador', 'categorias'));
    }

    public function store(Request $request, $slug)
    {

        try {
            DB::beginTransaction();
            $request->validate([
                'empatia' => 'required|integer|min:1|max:10',
                'escucha_activa' => 'required|integer|min:1|max:10',
                'comunicacion' => 'required|integer|min:1|max:10',
                'anatomia' => 'required|integer|min:1|max:10',
                'fisiologia' => 'required|integer|min:1|max:10',
                'programacion' => 'required|integer|min:1|max:10',
                'poblacion' => 'required|integer|min:1|max:10',
                'psicologia' => 'required|integer|min:1|max:10',
            ]);

            $entrenador = User::where('slug', $slug)->firstOrFail();

            EvaluacionEntrenador::create([
                'id_entrenador' => $entrenador->id,
                'empatia' => $request->empatia,
                'escucha_activa' => $request->escucha_activa,
                'comunicacion' => $request->comunicacion,
                'anatomia' => $request->anatomia,
                'fisiologia' => $request->fisiologia,
                'programacion' => $request->programacion,
                'poblacion' => $request->poblacion,
                'psicologia' => $request->psicologia,
            ]);


            $validatedData = $request->validate([
                'id_clasificacion' => 'nullable|integer',
            ]);



            $entrenador->update($validatedData);

            DB::commit();

            $promedios = \App\Models\EvaluacionEntrenador::where('id_entrenador', $entrenador->id)
                ->whereRaw("updated_at = (select max(updated_at) from evaluacion_entrenadors ee where ee.id_entrenador = evaluacion_entrenadors.id_entrenador)")
                ->selectRaw('(empatia) as empatia, (escucha_activa) as escucha_activa, (comunicacion) as comunicacion, (anatomia) as anatomia,fisiologia,programacion,poblacion,psicologia,
            round(((empatia+escucha_activa+comunicacion+anatomia+fisiologia+programacion+poblacion+psicologia)/56)*100,2) total')
                ->first();

            $categorias = DB::table('clasificaciones_entrenadores')->where('id', $entrenador->id_clasificacion)->first();

            return view('evaluaciones.index', compact('entrenador', 'promedios', 'categorias'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al actualizar la agenda: ' . $e->getMessage()]);
        }
    }

    public function exportarPDF($slug)
    {
        $entrenador = User::where('slug', $slug)->firstOrFail();
        $promedios = \App\Models\EvaluacionEntrenador::where('id_entrenador', $entrenador->id)
            ->whereRaw("updated_at = (select max(updated_at) from evaluacion_entrenadors ee where ee.id_entrenador = evaluacion_entrenadors.id_entrenador)")
            ->selectRaw('(empatia) as empatia, (escucha_activa) as escucha_activa, (comunicacion) as comunicacion, (anatomia) as anatomia,fisiologia,programacion,poblacion,psicologia,
            round(((empatia+escucha_activa+comunicacion+anatomia+fisiologia+programacion+poblacion+psicologia)/56)*100,2) total')
            ->first();

        $categorias = DB::table('clasificaciones_entrenadores')->where('id', $entrenador->id_clasificacion)->first();
        //return view('evaluaciones.index_pdf', compact('entrenador', 'promedios', 'categorias'));

        $pdf = Pdf::loadView('evaluaciones.index_pdf', compact('entrenador', 'categorias', 'promedios'));
        return $pdf->download('evaluacion_' . $entrenador->name . '.pdf');
    }
}
