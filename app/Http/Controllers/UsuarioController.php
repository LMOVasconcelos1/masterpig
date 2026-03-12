<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('usuario')) {
            return view('admin.usuarios.index', [
                'usuarios' => collect(),
                'errorMessage' => 'Tabela usuario não existe no banco.',
            ]);
        }

        $usuarios = User::query()->orderBy('nome')->get();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->route('admin.usuarios.index')->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'usuario' => ['required', 'string', 'max:255', 'unique:usuario,usuario'],
            'senha' => ['required', 'string', 'min:4'],
            'perfil' => ['nullable', 'string', Rule::in(['consultor', 'operador', 'administrador'])],
        ]);

        $usuario = trim((string) $validated['usuario']);
        $email = $this->uniqueEmailForUsuario($usuario);
        $cpf = $this->uniqueCpfForUsuario($usuario);

        User::create([
            'nome' => $usuario,
            'email' => $email,
            'cpf' => $cpf,
            'usuario' => $usuario,
            'perfil' => (string) ($validated['perfil'] ?? 'operador'),
            'senha' => Hash::make((string) $validated['senha']),
        ]);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário cadastrado com sucesso!');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->route('admin.usuarios.index')->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'senha' => ['nullable', 'string', 'min:4'],
            'perfil' => ['required', 'string', Rule::in(['consultor', 'operador', 'administrador'])],
        ]);

        $payload = [
            'perfil' => (string) $validated['perfil'],
        ];

        if (! empty($validated['senha'])) {
            $payload['senha'] = Hash::make((string) $validated['senha']);
        }

        $user->update($payload);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->route('admin.usuarios.index')->with('error', 'Tabela usuario não existe no banco.');
        }

        if ($request->user()?->getKey() === $user->getKey()) {
            return redirect()->route('admin.usuarios.index')->with('error', 'Você não pode excluir seu próprio usuário.');
        }

        try {
            $user->delete();
        } catch (\Throwable) {
            return redirect()->route('admin.usuarios.index')->with('error', 'Não foi possível excluir o usuário.');
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuário excluído com sucesso!');
    }

    private function uniqueEmailForUsuario(string $usuario): string
    {
        $base = strtolower(preg_replace('/\s+/', '', $usuario) ?? $usuario);
        $base = preg_replace('/[^a-z0-9._-]/', '', (string) $base) ?: 'user';
        $domain = 'masterpig.local';
        $candidate = $base.'@'.$domain;
        $i = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = $base.'+'.$i.'@'.$domain;
            $i++;
        }

        return $candidate;
    }

    private function uniqueCpfForUsuario(string $usuario): string
    {
        $seed = $usuario;
        $i = 0;

        while (true) {
            $hash = sprintf('%u', crc32($seed.($i === 0 ? '' : '|'.$i)));
            $cpf = str_pad(substr($hash, 0, 11), 11, '0', STR_PAD_LEFT);

            if (! User::query()->where('cpf', $cpf)->exists()) {
                return $cpf;
            }

            $i++;
        }
    }
}
