<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function apiIndex()
    {
        if (! Schema::hasTable('usuario')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela usuario não existe no banco.',
            ]);
        }

        $items = User::query()
            ->select(['id', 'nome', 'perfil'])
            ->orderBy('nome')
            ->limit(5000)
            ->get()
            ->map(fn (User $u) => [
                'id' => (int) $u->id,
                'nome' => (string) $u->nome,
                'perfil' => $u->perfil === null ? null : (string) $u->perfil,
            ])->values();

        return response()->json([
            'items' => $items,
        ]);
    }

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
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'usuario' => ['required', 'string', 'max:255', 'unique:usuario,usuario'],
            'senha' => ['required', 'string'],
        ]);

        $usuario = trim((string) $validated['usuario']);
        $email = $this->uniqueEmailForUsuario($usuario);
        $cpf = $this->uniqueCpfForUsuario($usuario);

        User::create([
            'nome' => $usuario,
            'email' => $email,
            'cpf' => $cpf,
            'usuario' => $usuario,
            'perfil' => 'operador',
            'senha' => Hash::make((string) $validated['senha']),
        ]);

        return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Usuário cadastrado com sucesso!');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'senha' => ['nullable', 'string'],
        ]);

        $payload = [];

        if (! empty($validated['senha'])) {
            $payload['senha'] = Hash::make((string) $validated['senha']);
        }

        $user->update($payload);

        return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Tabela usuario não existe no banco.');
        }

        if ($request->user()?->getKey() === $user->getKey()) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Você não pode excluir seu próprio usuário.');
        }

        try {
            $user->delete();
        } catch (\Throwable) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Não foi possível excluir o usuário.');
        }

        return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Usuário excluído com sucesso!');
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
