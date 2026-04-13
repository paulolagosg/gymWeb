<?php

namespace App\Http\Controllers;

use App\Models\Cuestionarios;
use Illuminate\Http\Request;

class CuestionariosController extends Controller
{
    public function index()
    {
        $cuestionarios = Cuestionarios::all();
        return response()->json($cuestionarios);
    }

    public function show($id)
    {
        $cuestionario = Cuestionarios::find($id);
        if (!$cuestionario) {
            return response()->json(['message' => 'Cuestionario no encontrado'], 404);
        }
        return response()->json($cuestionario);
    }

    public function store(Request $request)
    {
        $cuestionario = Cuestionarios::create($request->all());
        return response()->json($cuestionario, 201);
    }

    public function update(Request $request, $id)
    {
        $cuestionario = Cuestionarios::find($id);
        if (!$cuestionario) {
            return response()->json(['message' => 'Cuestionario no encontrado'], 404);
        }
        $cuestionario->update($request->all());
        return response()->json($cuestionario);
    }

    public function destroy($id)
    {
        $cuestionario = Cuestionarios::find($id);
        if (!$cuestionario) {
            return response()->json(['message' => 'Cuestionario no encontrado'], 404);
        }
        $cuestionario->delete();
        return response()->json(['message' => 'Cuestionario eliminado']);
    }
}
