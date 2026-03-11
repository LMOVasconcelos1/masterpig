<?php

namespace App\Http\Controllers;

use App\Models\TipoRacao;
use Illuminate\Http\Request;

class TipoRacaoController extends Controller
{
    public function index()
    {
        return response()->json(TipoRacao::orderBy('nome')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:tipo_racao,nome'],
        ]);

        $tipo = TipoRacao::create($validated);

        return response()->json($tipo, 201);
    }
}

