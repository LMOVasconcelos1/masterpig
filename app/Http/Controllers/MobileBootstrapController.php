<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileBootstrapController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'funcionarios' => $this->funcionarios(),
            'causas' => $this->causas(),
            'itens' => $this->itens(),
        ]);
    }

    private function funcionarios(): array
    {
        if (!Schema::hasTable('funcionario')) {
            return [];
        }

        $select = [
            'id',
            'nome',
        ];

        if (Schema::hasColumn('funcionario', 'perfil')) {
            $select[] = 'perfil';
        } else {
            $select[] = DB::raw('NULL as perfil');
        }

        if (Schema::hasColumn('funcionario', 'ativo')) {
            $select[] = 'ativo';
        } else {
            $select[] = DB::raw('1 as ativo');
        }

        if (Schema::hasColumn('funcionario', 'atualizado_em')) {
            $select[] = 'atualizado_em';
        } else {
            $select[] = DB::raw('NULL as atualizado_em');
        }

        return DB::table('funcionario')
            ->select($select)
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'nome' => (string) $row->nome,
                    'perfil' => $row->perfil === null ? null : (string) $row->perfil,
                    'ativo' => (int) ($row->ativo ?? 0),
                    'atualizado_em' => $row->atualizado_em === null ? null : (string) $row->atualizado_em,
                ];
            })
            ->values()
            ->all();
    }

    private function causas(): array
    {
        if (!Schema::hasTable('causa')) {
            return [];
        }

        $select = [
            'c.id',
            'c.codigo',
            'c.nome',
        ];

        $joinGrupo = false;

        if (Schema::hasColumn('causa', 'grupo_causa') && !Schema::hasColumn('causa', 'grupo_causa_id')) {
            $select[] = 'c.grupo_causa';
        } elseif (Schema::hasColumn('causa', 'grupo_causa_id') && Schema::hasTable('grupo_causa') && Schema::hasColumn('grupo_causa', 'nome')) {
            $joinGrupo = true;
            $select[] = 'gc.nome as grupo_causa';
        } else {
            $select[] = DB::raw('NULL as grupo_causa');
        }

        if (Schema::hasColumn('causa', 'situacao')) {
            $select[] = 'c.situacao';
        } else {
            $select[] = DB::raw('1 as situacao');
        }

        if (Schema::hasColumn('causa', 'atualizado_em')) {
            $select[] = 'c.atualizado_em';
        } else {
            $select[] = DB::raw('NULL as atualizado_em');
        }

        $query = DB::table('causa as c')->select($select);

        if ($joinGrupo) {
            $query->leftJoin('grupo_causa as gc', 'gc.id', '=', 'c.grupo_causa_id');
        }

        return $query
            ->orderBy('c.id')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'codigo' => (string) $row->codigo,
                    'nome' => (string) $row->nome,
                    'grupo_causa' => $row->grupo_causa === null ? null : (string) $row->grupo_causa,
                    'situacao' => (int) ($row->situacao ?? 0),
                    'atualizado_em' => $row->atualizado_em === null ? null : (string) $row->atualizado_em,
                ];
            })
            ->values()
            ->all();
    }

    private function itens(): array
    {
        if (!Schema::hasTable('item') || !Schema::hasColumn('item', 'tipo')) {
            return [];
        }

        $select = [
            'i.id',
            'i.tipo',
        ];

        if (Schema::hasColumn('item', 'id_primaria')) {
            $select[] = 'i.id_primaria';
        } else {
            $select[] = DB::raw('NULL as id_primaria');
        }

        if (Schema::hasColumn('item', 'id_secundaria')) {
            $select[] = 'i.id_secundaria';
        } else {
            $select[] = DB::raw('NULL as id_secundaria');
        }

        if (Schema::hasColumn('item', 'descricao')) {
            $select[] = 'i.descricao';
        } else {
            $select[] = DB::raw('NULL as descricao');
        }

        $joinRaca = false;

        if (Schema::hasColumn('item', 'raca') && !Schema::hasColumn('item', 'raca_id')) {
            $select[] = 'i.raca';
        } elseif (Schema::hasColumn('item', 'raca_id') && Schema::hasTable('raca') && Schema::hasColumn('raca', 'nome')) {
            $joinRaca = true;
            $select[] = 'r.nome as raca';
        } else {
            $select[] = DB::raw('NULL as raca');
        }

        if (Schema::hasColumn('item', 'status')) {
            $select[] = 'i.status';
        } else {
            $select[] = DB::raw('NULL as status');
        }

        if (Schema::hasColumn('item', 'atualizado_em')) {
            $select[] = 'i.atualizado_em';
        } else {
            $select[] = DB::raw('NULL as atualizado_em');
        }

        $query = DB::table('item as i')
            ->select($select)
            ->whereIn('i.tipo', ['leitoa', 'matriz', 'leitao', 'macho', 'semen'])
            ->orderBy('i.id');

        if ($joinRaca) {
            $query->leftJoin('raca as r', 'r.id', '=', 'i.raca_id');
        }

        return $query
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'tipo' => (string) $row->tipo,
                    'id_primaria' => $row->id_primaria === null ? null : (string) $row->id_primaria,
                    'id_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                    'descricao' => $row->descricao === null ? null : (string) $row->descricao,
                    'raca' => $row->raca === null ? null : (string) $row->raca,
                    'status' => $row->status === null ? null : (string) $row->status,
                    'atualizado_em' => $row->atualizado_em === null ? null : (string) $row->atualizado_em,
                ];
            })
            ->values()
            ->all();
    }
}

