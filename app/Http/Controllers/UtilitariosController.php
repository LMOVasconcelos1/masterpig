<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UtilitariosController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('meta')) {
            return response()->json([
                'localizacoes' => [],
                'baias' => [],
                'message' => 'Tabela meta não existe no banco.',
            ]);
        }

        return response()->json([
            'localizacoes' => $this->readList('util_localizacoes'),
            'baias' => $this->readList('util_baias'),
        ]);
    }

    public function storeLocalizacao(Request $request)
    {
        return $this->storeListItem($request, 'util_localizacoes', 'localizacoes');
    }

    public function storeBaia(Request $request)
    {
        return $this->storeListItem($request, 'util_baias', 'baias');
    }

    private function storeListItem(Request $request, string $key, string $responseKey)
    {
        if (! Schema::hasTable('meta')) {
            return response()->json([
                'message' => 'Tabela meta não existe no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
        ]);

        $name = trim((string) $validated['nome']);
        if ($name === '') {
            return response()->json([
                'message' => 'Informe um valor válido.',
            ], 422);
        }

        $items = $this->readList($key);
        $items[] = $name;
        $items = array_values(array_unique(array_map('trim', $items)));
        $items = array_values(array_filter($items, fn ($v) => $v !== ''));
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        $now = now();
        $json = json_encode($items, JSON_UNESCAPED_UNICODE);

        DB::transaction(function () use ($key, $json, $now) {
            $exists = DB::table('meta')->where('chave', $key)->exists();
            if ($exists) {
                DB::table('meta')->where('chave', $key)->update([
                    'valor' => $json,
                    'atualizado_em' => $now,
                ]);
            } else {
                DB::table('meta')->insert([
                    'chave' => $key,
                    'valor' => $json,
                    'criado_em' => $now,
                    'atualizado_em' => $now,
                ]);
            }
        });

        return response()->json([
            $responseKey => $items,
        ]);
    }

    private function readList(string $key): array
    {
        $row = DB::table('meta')->select(['valor'])->where('chave', $key)->first();
        $raw = $row?->valor;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = array_values(array_filter(array_map(function ($v) {
            if (! is_string($v)) {
                return null;
            }
            $t = trim($v);

            return $t === '' ? null : $t;
        }, $decoded)));

        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }
}
