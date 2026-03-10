<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index()
    {
        return response()->json(Fornecedor::orderBy('nome')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:fornecedor,nome'],
        ]);

        $fornecedor = Fornecedor::create($validated);

        return response()->json($fornecedor, 201);
    }
}

