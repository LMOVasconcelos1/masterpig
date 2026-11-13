<?php

namespace App\Http\Controllers;

use App\Models\Racao;
use App\Models\RacaoAcerto;
use App\Models\RacaoCompra;
use App\Models\RacaoConsumo;
use App\Models\RacaoVenda;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NutricaoController extends Controller
{
    public function index()
    {
        $stats = [
            'estoque_kg' => 0.0,
            'valor_estoque' => 0.0,
        ];

        $racoesEstoque = [];
        $compras = [];
        $consumos = [];
        $vendas = [];
        $acertos = [];
        $racoes = [];
        $fornecedores = [];
        $ultimoAcerto = null;

        // Resumo de estoque e valor por ração
        if (Schema::hasTable('racao')) {
            $rows = DB::table('racao as r')
                ->leftJoin('tipo_racao as t', 't.id', '=', 'r.tipo_racao_id')
                ->orderBy('r.codigo')
                ->get([
                    'r.id',
                    'r.codigo',
                    'r.nome',
                    'r.estoque',
                    'r.custo_por_kg',
                    't.nome as tipo_nome',
                ]);

            foreach ($rows as $row) {
                $estoque = (float) ($row->estoque ?? 0);
                $custo = (float) ($row->custo_por_kg ?? 0);
                $valorTotal = $estoque * $custo;

                $stats['estoque_kg'] += $estoque;
                $stats['valor_estoque'] += $valorTotal;

                $racoesEstoque[] = [
                    'id' => (int) $row->id,
                    'codigo' => (string) $row->codigo,
                    'nome' => (string) $row->nome,
                    'grupo' => (string) ($row->tipo_nome ?? ''),
                    'estoque' => $estoque,
                    'custo' => $custo,
                    'valor_total' => $valorTotal,
                ];
            }

            // Lista de rações para o select do modal de compra
            $racoes = $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'codigo' => (string) $r->codigo,
                'nome' => (string) $r->nome,
            ])->all();
        }

        // Lista de fornecedores para o select
        if (Schema::hasTable('fornecedor')) {
            $fornecedores = DB::table('fornecedor')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
                ->all();
        }

        // Listagem de compras (últimas 200)
        if (Schema::hasTable('racao_compras') && Schema::hasTable('racao')) {
            $rows = DB::table('racao_compras as c')
                ->join('racao as r', 'r.id', '=', 'c.racao_id')
                ->leftJoin('fornecedor as f', 'f.id', '=', 'c.fornecedor_id')
                ->orderByDesc('c.data_compra')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.data_compra',
                    'c.racao_id',
                    'r.codigo as racao_codigo',
                    'r.nome as racao_nome',
                    'c.quantidade',
                    'c.valor_unitario',
                    'c.valor_total',
                    'c.nota_fiscal',
                    'c.fornecedor_id',
                    'f.nome as fornecedor_nome',
                    'c.lote_fabricacao',
                ]);

            $compras = $rows->map(function ($r) {
                $data = empty($r->data_compra) ? null : Carbon::parse($r->data_compra)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_compra' => PigCycleService::formatDisplayDate($data),
                    'racao' => trim(($r->racao_codigo ? $r->racao_codigo.' - ' : '').($r->racao_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'valor_unitario' => (float) $r->valor_unitario,
                    'valor_total' => (float) $r->valor_total,
                    'nota_fiscal' => (string) ($r->nota_fiscal ?? ''),
                    'fornecedor' => (string) ($r->fornecedor_nome ?? ''),
                    'lote_fabricacao' => (string) ($r->lote_fabricacao ?? ''),
                ];
            })->all();
        }

        // Listagem de consumos (últimas 200)
        if (Schema::hasTable('racao_consumos') && Schema::hasTable('racao')) {
            $rows = DB::table('racao_consumos as c')
                ->join('racao as r', 'r.id', '=', 'c.racao_id')
                ->orderByDesc('c.data_consumo')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.data_consumo',
                    'c.racao_id',
                    'r.codigo as racao_codigo',
                    'r.nome as racao_nome',
                    'c.quantidade',
                    'c.fase',
                    'c.lote',
                    'c.lote_fabricacao',
                ]);

            $consumos = $rows->map(function ($r) {
                $data = empty($r->data_consumo) ? null : Carbon::parse($r->data_consumo)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_consumo' => PigCycleService::formatDisplayDate($data),
                    'racao' => trim(($r->racao_codigo ? $r->racao_codigo.' - ' : '').($r->racao_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'fase' => (string) ($r->fase ?? ''),
                    'lote' => (string) ($r->lote ?? ''),
                    'lote_fabricacao' => (string) ($r->lote_fabricacao ?? ''),
                ];
            })->all();
        }

        // Listagem de vendas (últimas 200)
        if (Schema::hasTable('racao_vendas') && Schema::hasTable('racao')) {
            $rows = DB::table('racao_vendas as v')
                ->join('racao as r', 'r.id', '=', 'v.racao_id')
                ->orderByDesc('v.data_venda')
                ->orderByDesc('v.id')
                ->limit(200)
                ->get([
                    'v.id',
                    'v.data_venda',
                    'v.racao_id',
                    'r.codigo as racao_codigo',
                    'r.nome as racao_nome',
                    'v.quantidade',
                    'v.valor_unitario',
                    'v.valor_total',
                    'v.comprador',
                ]);

            $vendas = $rows->map(function ($r) {
                $data = empty($r->data_venda) ? null : Carbon::parse($r->data_venda)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_venda' => PigCycleService::formatDisplayDate($data),
                    'racao' => trim(($r->racao_codigo ? $r->racao_codigo.' - ' : '').($r->racao_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'valor_unitario' => (float) $r->valor_unitario,
                    'valor_total' => (float) $r->valor_total,
                    'comprador' => (string) ($r->comprador ?? ''),
                ];
            })->all();
        }

        // Listagem de acertos (últimas 200)
        if (Schema::hasTable('racao_acertos') && Schema::hasTable('racao')) {
            $rows = DB::table('racao_acertos as a')
                ->join('racao as r', 'r.id', '=', 'a.racao_id')
                ->orderByDesc('a.data_acerto')
                ->orderByDesc('a.id')
                ->limit(200)
                ->get([
                    'a.id',
                    'a.data_acerto',
                    'a.racao_id',
                    'r.codigo as racao_codigo',
                    'r.nome as racao_nome',
                    'a.estoque_anterior',
                    'a.contagem',
                    'a.diferenca',
                ]);

            $acertos = $rows->map(function ($r) {
                $data = empty($r->data_acerto) ? null : Carbon::parse($r->data_acerto)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_acerto' => PigCycleService::formatDisplayDate($data),
                    'racao' => trim(($r->racao_codigo ? $r->racao_codigo.' - ' : '').($r->racao_nome ?? '')),
                    'estoque_anterior' => (float) $r->estoque_anterior,
                    'contagem' => (float) $r->contagem,
                    'diferenca' => (float) $r->diferenca,
                ];
            })->all();

            $ultimoAcerto = $acertos[0]['data_acerto'] ?? null;
        }

        // Dados do gráfico: movimentação dos últimos 6 meses (entradas por compra)
        $movimentacao = $this->buildMovimentacao6Meses();

        return view('nutricao.index', [
            'stats' => $stats,
            'racoesEstoque' => $racoesEstoque,
            'compras' => $compras,
            'consumos' => $consumos,
            'vendas' => $vendas,
            'acertos' => $acertos,
            'racoes' => $racoes,
            'fornecedores' => $fornecedores,
            'movimentacao' => $movimentacao,
            'ultimoAcerto' => $ultimoAcerto,
        ]);
    }

    public function storeCompra(Request $request)
    {
        $validated = $request->validate([
            'data_compra' => ['required', 'string', 'max:30'],
            'racao_id' => ['required', 'integer', 'exists:racao,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'nota_fiscal' => ['nullable', 'string', 'max:120'],
            'fornecedor_id' => ['nullable', 'integer'],
            'lote_fabricacao' => ['nullable', 'string', 'max:120'],
        ]);

        $dataCompra = $this->parseInputDate($validated['data_compra']);
        if (! $dataCompra) {
            return back()->withInput()->withErrors(['data_compra' => 'Data da compra inválida.']);
        }

        if (! Schema::hasTable('racao_compras')) {
            return back()
                ->withInput()
                ->withErrors(['data_compra' => "Tabela 'racao_compras' não existe. Execute a migration 0.14."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            RacaoCompra::create([
                'data_compra' => $dataCompra,
                'racao_id' => (int) $validated['racao_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'nota_fiscal' => $validated['nota_fiscal'] ?? null,
                'fornecedor_id' => $validated['fornecedor_id'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
            ]);

            // Atualiza o estoque da ração
            $racao = Racao::findOrFail((int) $validated['racao_id']);
            $estoqueAtual = (float) ($racao->estoque ?? 0);
            $custoAtual = (float) ($racao->custo_por_kg ?? 0);

            $novoEstoque = $estoqueAtual + $quantidade;

            // Custo médio ponderado (apenas se houve quantidade)
            $novoCusto = $custoAtual;
            if ($novoEstoque > 0 && $quantidade > 0) {
                $valorEstoqueAtual = $estoqueAtual * $custoAtual;
                $valorEntrada = $quantidade * $valorUnitario;
                $novoCusto = ($valorEstoqueAtual + $valorEntrada) / $novoEstoque;
            }

            $racao->update([
                'estoque' => $novoEstoque,
                'custo_por_kg' => $novoCusto,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('nutricao.index')->with('success', 'Compra de ração registrada e estoque atualizado!');
    }

    public function storeConsumo(Request $request)
    {
        $validated = $request->validate([
            'data_consumo' => ['required', 'string', 'max:30'],
            'racao_id' => ['required', 'integer', 'exists:racao,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'fase' => ['nullable', 'string', 'max:60'],
            'lote' => ['nullable', 'string', 'max:120'],
            'lote_fabricacao' => ['nullable', 'string', 'max:120'],
        ]);

        $dataConsumo = $this->parseInputDate($validated['data_consumo']);
        if (! $dataConsumo) {
            return back()->withInput()->withErrors(['data_consumo' => 'Data do consumo inválida.']);
        }

        if (! Schema::hasTable('racao_consumos')) {
            return back()
                ->withInput()
                ->withErrors(['data_consumo' => "Tabela 'racao_consumos' não existe. Execute a migration 0.15."]);
        }

        $quantidade = (float) $validated['quantidade'];

        DB::beginTransaction();
        try {
            RacaoConsumo::create([
                'data_consumo' => $dataConsumo,
                'racao_id' => (int) $validated['racao_id'],
                'quantidade' => $quantidade,
                'fase' => $validated['fase'] ?? null,
                'lote' => $validated['lote'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
            ]);

            // Decrementa o estoque da ração
            $racao = Racao::findOrFail((int) $validated['racao_id']);
            $estoqueAtual = (float) ($racao->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $racao->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('nutricao.index')->with('success', 'Consumo de ração registrado e estoque atualizado!');
    }

    public function storeVenda(Request $request)
    {
        $validated = $request->validate([
            'data_venda' => ['required', 'string', 'max:30'],
            'racao_id' => ['required', 'integer', 'exists:racao,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'comprador' => ['nullable', 'string', 'max:180'],
        ]);

        $dataVenda = $this->parseInputDate($validated['data_venda']);
        if (! $dataVenda) {
            return back()->withInput()->withErrors(['data_venda' => 'Data da venda inválida.']);
        }

        if (! Schema::hasTable('racao_vendas')) {
            return back()
                ->withInput()
                ->withErrors(['data_venda' => "Tabela 'racao_vendas' não existe. Execute a migration 0.15."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            RacaoVenda::create([
                'data_venda' => $dataVenda,
                'racao_id' => (int) $validated['racao_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'comprador' => $validated['comprador'] ?? null,
            ]);

            // Decrementa o estoque da ração
            $racao = Racao::findOrFail((int) $validated['racao_id']);
            $estoqueAtual = (float) ($racao->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $racao->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('nutricao.index')->with('success', 'Venda de ração registrada e estoque atualizado!');
    }

    public function storeAcerto(Request $request)
    {
        $validated = $request->validate([
            'data_acerto' => ['required', 'string', 'max:30'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.racao_id' => ['required', 'integer', 'exists:racao,id'],
            'itens.*.contagem' => ['required', 'numeric', 'min:0'],
        ]);

        $dataAcerto = $this->parseInputDate($validated['data_acerto']);
        if (! $dataAcerto) {
            return back()->withInput()->withErrors(['data_acerto' => 'Data do acerto inválida.']);
        }

        if (! Schema::hasTable('racao_acertos')) {
            return back()
                ->withInput()
                ->withErrors(['data_acerto' => "Tabela 'racao_acertos' não existe. Execute a migration 0.15."]);
        }

        DB::beginTransaction();
        try {
            foreach ($validated['itens'] as $item) {
                $racao = Racao::findOrFail((int) $item['racao_id']);
                $estoqueAnterior = (float) ($racao->estoque ?? 0);
                $contagem = (float) $item['contagem'];
                $diferenca = $contagem - $estoqueAnterior;

                RacaoAcerto::create([
                    'data_acerto' => $dataAcerto,
                    'racao_id' => (int) $item['racao_id'],
                    'estoque_anterior' => $estoqueAnterior,
                    'contagem' => $contagem,
                    'diferenca' => $diferenca,
                ]);

                $racao->update([
                    'estoque' => $contagem,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('nutricao.index')->with('success', 'Acerto de estoque realizado e estoque atualizado!');
    }

    private function buildMovimentacao6Meses(): array
    {
        $meses = [];
        $hoje = Carbon::now()->startOfMonth();
        $nomesMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        for ($i = 5; $i >= 0; $i--) {
            $mes = $hoje->copy()->subMonths($i);
            $mesIdx = (int) $mes->format('n') - 1;
            $meses[] = [
                'label' => $nomesMes[$mesIdx].'/'.$mes->format('y'),
                'ano' => (int) $mes->format('Y'),
                'mes' => (int) $mes->format('m'),
                'entradas' => 0.0,
                'saldo' => 0.0,
            ];
        }

        if (! Schema::hasTable('racao_compras')) {
            return $meses;
        }

        $inicio = $hoje->copy()->subMonths(5)->startOfMonth();
        $rows = DB::table('racao_compras')
            ->where('data_compra', '>=', $inicio->format('Y-m-d'))
            ->selectRaw('YEAR(data_compra) as ano, MONTH(data_compra) as mes, COALESCE(SUM(quantidade), 0) as total')
            ->groupBy('ano', 'mes')
            ->get();

        $totais = [];
        foreach ($rows as $r) {
            $totais[((int) $r->ano).'-'.((int) $r->mes)] = (float) $r->total;
        }

        $saldoAcumulado = 0.0;
        foreach ($meses as &$m) {
            $chave = $m['ano'].'-'.$m['mes'];
            $m['entradas'] = $totais[$chave] ?? 0.0;
            $saldoAcumulado += $m['entradas'];
            $m['saldo'] = $saldoAcumulado;
        }
        unset($m);

        return $meses;
    }

    private function parseInputDate(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }
        $carbon = PigCycleService::parseFilterDate($input);

        return $carbon ? $carbon->format('Y-m-d') : null;
    }
}
