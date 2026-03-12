<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FornecedorController extends Controller
{
    public function page()
    {
        if (! Schema::hasTable('fornecedor')) {
            return view('admin.fornecedores.index', [
                'fornecedores' => collect(),
                'errorMessage' => 'Tabela fornecedor não existe no banco.',
            ]);
        }

        $fornecedores = Fornecedor::query()->orderBy('nome')->get();

        return view('admin.fornecedores.index', compact('fornecedores'));
    }

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

    public function update(Request $request, Fornecedor $fornecedor)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:fornecedor,nome,'.$fornecedor->id],
        ]);

        $fornecedor->update($validated);

        return redirect()->to(route('admin.fornecedores.index', [], false))->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        try {
            $fornecedor->delete();
        } catch (\Throwable) {
            return redirect()->to(route('admin.fornecedores.index', [], false))->with('error', 'Não foi possível excluir o fornecedor.');
        }

        return redirect()->to(route('admin.fornecedores.index', [], false))->with('success', 'Fornecedor excluído com sucesso!');
    }
}
