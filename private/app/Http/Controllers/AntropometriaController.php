<?php

namespace App\Http\Controllers;

use App\Models\Antropometrias;
use Illuminate\Http\Request;

class AntropometriaController extends Controller
{
    public function index()
    {
        return view('antropometrias.index');
    }
    public function calcular(Request $request)
    {
        // Recibe los datos del formulario
        $peso = $request->input('peso');
        $talla = $request->input('talla');
        $biacromial = $request->input('biacromial');
        $torax_transverso = $request->input('torax_transverso');
        $torax_anteriorposterior = $request->input('torax_anteriorposterior');
        $bi_iliocrestido = $request->input('bi_iliocrestido');
        $humeral = $request->input('humeral');
        $femoral = $request->input('femoral');
        $triceps = $request->input('triceps');
        $subescapular = $request->input('subescapular');
        $supraespinal = $request->input('supraespinal');
        $abdominal = $request->input('abdominal');
        $muslo_medial = $request->input('muslo_medial');
        $pantorrilla = $request->input('pantorrilla');

        $antropometria = new Antropometrias();
        $antropometria->peso = $request->input('peso');
        $antropometria->talla = $request->input('talla');
        $antropometria->talla_sentado = $request->input('talla_sentado');
        $antropometria->biacromial = $request->input('biacromial');
        $antropometria->torax_transverso = $request->input('torax_transverso');
        $antropometria->torax_anteriorposterior = $request->input('torax_anteriorposterior');
        $antropometria->bi_iliocrestido = $request->input('bi_iliocrestido');
        $antropometria->humeral = $request->input('humeral');
        $antropometria->femoral = $request->input('femoral');
        $antropometria->cabeza = $request->input('cabeza');
        $antropometria->brazo_relajado = $request->input('brazo_relajado');
        $antropometria->brazo_flexionado = $request->input('brazo_flexionado');
        $antropometria->antebrazo = $request->input('antebrazo');
        $antropometria->torax_mesoesternal = $request->input('torax_mesoesternal');
        $antropometria->cintura_minima = $request->input('cintura_minima');
        $antropometria->caderas_maxima = $request->input('caderas_maxima');
        $antropometria->muslo_superior = $request->input('muslo_superior');
        $antropometria->muslo_medial = $request->input('muslo_medial');
        $antropometria->muslo_medial_a = $request->input('muslo_medial');
        $antropometria->pantorrilla_maxima = $request->input('pantorrilla_maxima');
        $antropometria->triceps = $request->input('triceps');
        $antropometria->subescapular = $request->input('subescapular');
        $antropometria->supraespinal = $request->input('supraespinal');
        $antropometria->abdominal = $request->input('abdominal');
        $antropometria->pantorrilla = $request->input('pantorrilla');

        // Guardamos los datos
        $antropometria->save();

        // Cálculos
        $imc = $peso / pow($talla, 2);
        $clasificacion_imc = $this->clasificarIMC($imc);

        $sumatoria_pliegues = $triceps + $subescapular + $abdominal;
        $clasificacion_masa_adiposa = $this->clasificarMasaAdiposa($sumatoria_pliegues);

        $masa_muscular = $this->calcularMasaMuscular($biacromial, $torax_transverso, $torax_anteriorposterior);

        $distribucion_grasa = $this->distribucionGrasa($triceps, $supraespinal, $abdominal, $muslo_medial);

        // Cálculo de los Score-Z para Perímetros y Pliegues
        $score_z_perimetros = $this->calcularScoreZPerimetros([
            'cintura' => $request->input('cintura'),
            'muslo_superior' => $muslo_medial,
        ]);

        $score_z_pliegues = $this->calcularScoreZPliegues([
            'triceps' => $triceps,
            'subescapular' => $subescapular,
        ]);

        // Retornar los resultados a la vista
        return view('antropometrias.resultados', compact(
            'peso',
            'talla',
            'imc',
            'clasificacion_imc',
            'sumatoria_pliegues',
            'clasificacion_masa_adiposa',
            'masa_muscular',
            'distribucion_grasa',
            'score_z_perimetros',
            'score_z_pliegues'
        ));
    }

    public function clasificarIMC($imc)
    {
        if ($imc < 18.5) {
            return 'Bajo peso';
        } elseif ($imc >= 18.5 && $imc < 24.9) {
            return 'Normal';
        } elseif ($imc >= 25 && $imc < 29.9) {
            return 'Sobrepeso';
        } else {
            return 'Obesidad';
        }
    }

    public function clasificarMasaAdiposa($sumatoria_pliegues)
    {
        if ($sumatoria_pliegues < 100) {
            return 'Baja';
        } elseif ($sumatoria_pliegues >= 100 && $sumatoria_pliegues < 150) {
            return 'Normal';
        } else {
            return 'Elevada';
        }
    }

    public function calcularMasaMuscular($biacromial, $torax_transverso, $torax_anteriorposterior)
    {
        // Fórmula de ejemplo para calcular la masa muscular
        return ($biacromial + $torax_transverso + $torax_anteriorposterior) / 3;
    }

    public function distribucionGrasa($triceps, $supraespinal, $abdominal, $muslo_medial)
    {
        return [
            'superior' => $triceps,
            'media' => $supraespinal,
            'inferior' => $abdominal,
        ];
    }

    public function calcularScoreZPerimetros($perimetros)
    {
        // Cálculo de los Score-Z de perimetros
        $cintura_z = $this->scoreZ($perimetros['cintura']);
        $muslo_superior_z = $this->scoreZ($perimetros['muslo_superior']);

        return [
            'cintura' => $cintura_z,
            'muslo_superior' => $muslo_superior_z
        ];
    }

    public function calcularScoreZPliegues($pliegues)
    {
        // Cálculo de los Score-Z de pliegues
        $triceps_z = $this->scoreZ($pliegues['triceps']);
        $subescapular_z = $this->scoreZ($pliegues['subescapular']);

        return [
            'triceps' => $triceps_z,
            'subescapular' => $subescapular_z
        ];
    }

    public function scoreZ($valor)
    {
        // Cálculo de Score-Z: ejemplo basado en la desviación estándar
        $media = 30; // Valor medio de referencia
        $desviacion = 5; // Desviación estándar

        return ($valor - $media) / $desviacion;
    }
}
