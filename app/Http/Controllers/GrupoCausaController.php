<?php

namespace App\Http\Controllers;

use App\Models\GrupoCausa;
use Illuminate\Http\Request;

class GrupoCausaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:grupo_causa,nome'],
        ]);

        $grupoCausa = GrupoCausa::create($validated);

        return response()->json($grupoCausa, 201);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(GrupoCausa::all());
    }
}
