<?php

namespace App\Http\Controllers;

use App\Models\Raca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RacaController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('raca')) {
            return response()->json([]);
        }

        return response()->json(Raca::orderBy('nome')->get());
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('raca')) {
            return response()->json([
                'message' => 'A tabela de raça ainda não foi criada no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:raca,nome'],
        ]);

        $raca = Raca::create($validated);

        return response()->json($raca, 201);
    }
}
