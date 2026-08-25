<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'usuarios'     => collect(),
                'arvore'       => PermissaoService::arvorePermissoes(),
                'perfis'       => PermissaoService::perfisDisponiveis(),
                'errorMessage' => 'Tabela usuario não existe no banco.',
            ]);
        }

        $usuarios = User::query()->orderBy('nome')->get();
        $arvore   = PermissaoService::arvorePermissoes();
        $perfis   = PermissaoService::perfisDisponiveis();

        return view('admin.usuarios.index', compact('usuarios', 'arvore', 'perfis'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'usuario'     => ['required', 'string', 'max:255', 'unique:usuario,usuario'],
            'senha'       => ['required', 'string'],
            'perfil'      => ['nullable', 'string', 'in:administrador,operador,leitor'],
            'permissoes'  => ['nullable', 'array'],
        ]);

        $usuario = trim((string) $validated['usuario']);
        $email = $this->uniqueEmailForUsuario($usuario);
        $cpf = $this->uniqueCpfForUsuario($usuario);

        $perfil = (string) ($validated['perfil'] ?? PermissaoService::PERFIL_OPERADOR);
        if ($perfil === '') $perfil = PermissaoService::PERFIL_OPERADOR;

        $permissoesRaw = $validated['permissoes'] ?? [];
        $permissoes = is_array($permissoesRaw) ? array_values(array_filter(array_map('strval', $permissoesRaw))) : [];

        User::create([
            'nome'       => $usuario,
            'email'      => $email,
            'cpf'        => $cpf,
            'usuario'    => $usuario,
            'perfil'     => $perfil,
            'permissoes' => $permissoes,
            'senha'      => Hash::make((string) $validated['senha']),
        ]);

        return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Usuário cadastrado com sucesso!');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasTable('usuario')) {
            return redirect()->to(route('admin.usuarios.index', [], false))->with('error', 'Tabela usuario não existe no banco.');
        }

        $validated = $request->validate([
            'senha'       => ['nullable', 'string'],
            'perfil'      => ['nullable', 'string', 'in:administrador,operador,leitor'],
            'permissoes'  => ['nullable', 'array'],
        ]);

        $payload = [];

        if (! empty($validated['senha'])) {
            $payload['senha'] = Hash::make((string) $validated['senha']);
        }
        if (array_key_exists('perfil', $validated) && $validated['perfil'] !== null) {
            $perfil = (string) $validated['perfil'];
            $payload['perfil'] = ($perfil === '') ? PermissaoService::PERFIL_OPERADOR : $perfil;
        }
        if (array_key_exists('permissoes', $validated)) {
            $raw = $validated['permissoes'];
            $payload['permissoes'] = is_array($raw) ? array_values(array_filter(array_map('strval', $raw))) : [];
        }

        if ($payload !== []) {
            $user->update($payload);
        }

        return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Usuário atualizado com sucesso!');
    }

    public function savePermissoes(Request $request, User $user): RedirectResponse
    {
        try {
            if (! Schema::hasTable('usuario')) {
                return redirect()->back()->withInput()->with('error', 'Tabela "usuario" não existe no banco do CNPJ. Execute migração.');
            }

            $validated = $request->validate([
                'perfil'              => ['nullable', 'string', 'in:administrador,operador,leitor'],
                'permissoes'          => ['nullable', 'array'],
                '_permissoes_enviadas' => ['nullable', 'string', 'in:1'],
            ]);

            $payload = [];
            if (array_key_exists('perfil', $validated) && $validated['perfil'] !== null) {
                $perfil = (string) $validated['perfil'];
                $payload['perfil'] = ($perfil === '') ? PermissaoService::PERFIL_OPERADOR : $perfil;
            }

            // Bandeira _permissoes_enviadas=1: front SEMPRE envia (até quando 0 permissões marcadas).
            // Neste caso: SEMPRE atualizar coluna permissoes (mesmo que array vazio → limpar).
            $enviaPermissoes = (string) ($validated['_permissoes_enviadas'] ?? '') === '1';
            if ($enviaPermissoes) {
                $raw = $validated['permissoes'] ?? [];
                if (! is_array($raw)) {
                    $raw = [];
                }
                // Filtrar valores vazios "" que o front envia quando permissoes = [] (para garantir array vazio)
                $payload['permissoes'] = array_values(array_filter(array_map('strval', $raw), static fn ($v) => $v !== ''));
            } else {
                // Fallback: se veio permissoes array (sem bandeira), também aceita (compatibilidade)
                if (array_key_exists('permissoes', $validated) && is_array($validated['permissoes'])) {
                    $raw = $validated['permissoes'];
                    $payload['permissoes'] = array_values(array_filter(array_map('strval', $raw)));
                }
            }

            if ($payload !== []) {
                // Garantir que colunas perfil e permissoes existam (ApplyUserSchema deveria ter rodado, mas se não rodou, dá erro amigável)
                foreach (array_keys($payload) as $col) {
                    if (! Schema::hasColumn('usuario', $col)) {
                        return redirect()->back()->withInput()->with('error', 'Coluna "'.$col.'" não existe na tabela usuario (banco do CNPJ). Tente recarregar a tela 1x para ApplyUserSchema rodar.');
                    }
                }
                $user->update($payload);
                // Re-ler os dados para confirmar persistência
                $user->refresh();
            }

            return redirect()->to(route('admin.usuarios.index', [], false))->with('success', 'Controle de acesso atualizado com sucesso! (ID usuário: '.$user->id.')');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve; // Laravel redirect back com $errors automaticamente
        } catch (\Throwable $e) {
            report($e);
            $erro = (string) $e->getMessage();
            $cod  = (string) $e->getCode();
            if (mb_strlen($erro) > 260) $erro = mb_substr($erro, 0, 260).'...';
            return redirect()->back()->withInput()->with('error', 'Falha ao salvar controle de acesso ('.$cod.'): '.$erro);
        }
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
