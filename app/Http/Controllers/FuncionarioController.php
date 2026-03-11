<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class FuncionarioController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('funcionario')) {
            return view('admin.funcionarios.index', [
                'funcionarios' => collect(),
                'errorMessage' => 'Tabela funcionario não existe no banco.',
            ]);
        }

        $funcionarios = Funcionario::query()->orderBy('nome')->get();

        return view('admin.funcionarios.index', compact('funcionarios'));
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('funcionario')) {
            return redirect()->route('admin.funcionarios.index')->with('error', 'Tabela funcionario não existe no banco.');
        }

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'usuario' => ['required', 'string', 'max:255', 'unique:funcionario,usuario'],
            'senha' => ['required', 'string', 'min:6'],
        ]);

        Funcionario::create([
            'nome' => $validated['nome'],
            'usuario' => $validated['usuario'],
            'senha' => Hash::make($validated['senha']),
        ]);

        return redirect()->route('admin.funcionarios.index')->with('success', 'Funcionário cadastrado com sucesso!');
    }

    public function update(Request $request, Funcionario $funcionario)
    {
        if (!Schema::hasTable('funcionario')) {
            return redirect()->route('admin.funcionarios.index')->with('error', 'Tabela funcionario não existe no banco.');
        }

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'usuario' => ['required', 'string', 'max:255', Rule::unique('funcionario', 'usuario')->ignore($funcionario->id)],
            'senha' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = [
            'nome' => $validated['nome'],
            'usuario' => $validated['usuario'],
        ];

        if (!empty($validated['senha'])) {
            $payload['senha'] = Hash::make($validated['senha']);
        }

        $funcionario->update($payload);

        return redirect()->route('admin.funcionarios.index')->with('success', 'Funcionário atualizado com sucesso!');
    }

    public function destroy(Funcionario $funcionario)
    {
        if (!Schema::hasTable('funcionario')) {
            return redirect()->route('admin.funcionarios.index')->with('error', 'Tabela funcionario não existe no banco.');
        }

        try {
            $funcionario->delete();
        } catch (\Throwable $e) {
            return redirect()->route('admin.funcionarios.index')->with('error', 'Não foi possível excluir o funcionário.');
        }

        return redirect()->route('admin.funcionarios.index')->with('success', 'Funcionário excluído com sucesso!');
    }
}
