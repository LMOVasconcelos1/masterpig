<?php

namespace App\Http\Controllers;

use App\Models\Sanidade;
use App\Models\SanidadeAcerto;
use App\Models\SanidadeCompra;
use App\Models\SanidadeConsumo;
use App\Models\SanidadeVenda;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SanidadeController extends Controller
{
    public function index()
    {
        $stats = [
            'vacinas_estoque' => 0.0,
            'medicamentos_estoque' => 0.0,
            'desinfetantes_estoque' => 0.0,
            'valor_estoque' => 0.0,
        ];

        $itensEstoque = [];
        $compras = [];
        $consumos = [];
        $vendas = [];
        $acertos = [];
        $itens = [];
        $grupos = [];
        $fornecedores = [];
        $ultimoAcerto = null;

        // Lista de fornecedores para o select
        if (Schema::hasTable('fornecedor')) {
            $fornecedores = DB::table('fornecedor')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
                ->all();
        }

        // Resumo de estoque e valor por item de sanidade
        if (Schema::hasTable('sanidade')) {
            $rows = DB::table('sanidade')
                ->orderBy('codigo')
                ->get([
                    'id',
                    'codigo',
                    'nome',
                    'tipo',
                    'grupo',
                    'estoque',
                    'custo_por_unidade',
                ]);

            foreach ($rows as $row) {
                $estoque = (float) ($row->estoque ?? 0);
                $custo = (float) ($row->custo_por_unidade ?? 0);
                $valorTotal = $estoque * $custo;

                if ($row->tipo === 'vacina') {
                    $stats['vacinas_estoque'] += $estoque;
                } elseif ($row->tipo === 'medicamento') {
                    $stats['medicamentos_estoque'] += $estoque;
                } elseif ($row->tipo === 'desinfetante') {
                    $stats['desinfetantes_estoque'] += $estoque;
                }
                $stats['valor_estoque'] += $valorTotal;

                $itensEstoque[] = [
                    'id' => (int) $row->id,
                    'codigo' => (string) $row->codigo,
                    'nome' => (string) $row->nome,
                    'tipo' => (string) $row->tipo,
                    'grupo' => (string) ($row->grupo ?? ''),
                    'estoque' => $estoque,
                    'custo' => $custo,
                    'valor_total' => $valorTotal,
                ];

                if (! empty($row->grupo) && ! in_array($row->grupo, $grupos, true)) {
                    $grupos[] = (string) $row->grupo;
                }
            }

            // Lista de itens para os selects dos modais
            $itens = $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'codigo' => (string) $r->codigo,
                'nome' => (string) $r->nome,
                'tipo' => (string) $r->tipo,
                'grupo' => (string) ($r->grupo ?? ''),
                'estoque' => (float) ($r->estoque ?? 0),
            ])->all();
        }

        // Listagem de compras (últimas 200)
        if (Schema::hasTable('sanidade_compras') && Schema::hasTable('sanidade')) {
            $rows = DB::table('sanidade_compras as c')
                ->join('sanidade as s', 's.id', '=', 'c.sanidade_id')
                ->orderByDesc('c.data_compra')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.data_compra',
                    'c.sanidade_id',
                    's.codigo as sanidade_codigo',
                    's.nome as sanidade_nome',
                    'c.quantidade',
                    'c.valor_unitario',
                    'c.valor_total',
                    'c.nota_fiscal',
                    'c.fornecedor',
                    'c.lote_fabricacao',
                ]);

            $compras = $rows->map(function ($r) {
                $data = empty($r->data_compra) ? null : Carbon::parse($r->data_compra)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_compra' => PigCycleService::formatDisplayDate($data),
                    'sanidade' => trim(($r->sanidade_codigo ? $r->sanidade_codigo.' - ' : '').($r->sanidade_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'valor_unitario' => (float) $r->valor_unitario,
                    'valor_total' => (float) $r->valor_total,
                    'nota_fiscal' => (string) ($r->nota_fiscal ?? ''),
                    'fornecedor' => (string) ($r->fornecedor ?? ''),
                    'lote_fabricacao' => (string) ($r->lote_fabricacao ?? ''),
                ];
            })->all();
        }

        // Listagem de consumos (últimas 200)
        if (Schema::hasTable('sanidade_consumos') && Schema::hasTable('sanidade')) {
            $rows = DB::table('sanidade_consumos as c')
                ->join('sanidade as s', 's.id', '=', 'c.sanidade_id')
                ->orderByDesc('c.data_inicial')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.sanidade_id',
                    's.codigo as sanidade_codigo',
                    's.nome as sanidade_nome',
                    'c.fase',
                    'c.quantidade',
                    'c.lote',
                    'c.lote_fabricacao',
                    'c.data_inicial',
                    'c.data_final',
                ]);

            $consumos = $rows->map(function ($r) {
                $dataIni = empty($r->data_inicial) ? null : Carbon::parse($r->data_inicial)->startOfDay();
                $dataFim = empty($r->data_final) ? null : Carbon::parse($r->data_final)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'sanidade' => trim(($r->sanidade_codigo ? $r->sanidade_codigo.' - ' : '').($r->sanidade_nome ?? '')),
                    'fase' => (string) ($r->fase ?? ''),
                    'quantidade' => (float) $r->quantidade,
                    'lote' => (string) ($r->lote ?? ''),
                    'lote_fabricacao' => (string) ($r->lote_fabricacao ?? ''),
                    'data_inicial' => PigCycleService::formatDisplayDate($dataIni),
                    'data_final' => PigCycleService::formatDisplayDate($dataFim),
                ];
            })->all();
        }

        // Listagem de vendas (últimas 200)
        if (Schema::hasTable('sanidade_vendas') && Schema::hasTable('sanidade')) {
            $rows = DB::table('sanidade_vendas as v')
                ->join('sanidade as s', 's.id', '=', 'v.sanidade_id')
                ->orderByDesc('v.data_venda')
                ->orderByDesc('v.id')
                ->limit(200)
                ->get([
                    'v.id',
                    'v.data_venda',
                    'v.sanidade_id',
                    's.codigo as sanidade_codigo',
                    's.nome as sanidade_nome',
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
                    'sanidade' => trim(($r->sanidade_codigo ? $r->sanidade_codigo.' - ' : '').($r->sanidade_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'valor_unitario' => (float) $r->valor_unitario,
                    'valor_total' => (float) $r->valor_total,
                    'comprador' => (string) ($r->comprador ?? ''),
                ];
            })->all();
        }

        // Listagem de acertos (últimas 200)
        if (Schema::hasTable('sanidade_acertos') && Schema::hasTable('sanidade')) {
            $rows = DB::table('sanidade_acertos as a')
                ->join('sanidade as s', 's.id', '=', 'a.sanidade_id')
                ->orderByDesc('a.data_acerto')
                ->orderByDesc('a.id')
                ->limit(200)
                ->get([
                    'a.id',
                    'a.data_acerto',
                    'a.sanidade_id',
                    's.codigo as sanidade_codigo',
                    's.nome as sanidade_nome',
                    's.tipo as sanidade_tipo',
                    'a.estoque_anterior',
                    'a.contagem',
                    'a.diferenca',
                ]);

            $acertos = $rows->map(function ($r) {
                $data = empty($r->data_acerto) ? null : Carbon::parse($r->data_acerto)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_acerto' => PigCycleService::formatDisplayDate($data),
                    'sanidade' => trim(($r->sanidade_codigo ? $r->sanidade_codigo.' - ' : '').($r->sanidade_nome ?? '')),
                    'tipo' => (string) ($r->sanidade_tipo ?? ''),
                    'estoque_anterior' => (float) $r->estoque_anterior,
                    'contagem' => (float) $r->contagem,
                    'diferenca' => (float) $r->diferenca,
                ];
            })->all();

            $ultimoAcerto = $acertos[0]['data_acerto'] ?? null;
        }

        // Dados do gráfico: movimentação dos últimos 6 meses
        $movimentacao = $this->buildMovimentacao6Meses();

        return view('sanidade.index', [
            'stats' => $stats,
            'itensEstoque' => $itensEstoque,
            'compras' => $compras,
            'consumos' => $consumos,
            'vendas' => $vendas,
            'acertos' => $acertos,
            'itens' => $itens,
            'grupos' => $grupos,
            'fornecedores' => $fornecedores,
            'movimentacao' => $movimentacao,
            'ultimoAcerto' => $ultimoAcerto,
        ]);
    }

    public function storeCompra(Request $request)
    {
        $validated = $request->validate([
            'data_compra' => ['required', 'string', 'max:30'],
            'sanidade_id' => ['required', 'integer', 'exists:sanidade,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'nota_fiscal' => ['nullable', 'string', 'max:120'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'lote_fabricacao' => ['nullable', 'string', 'max:120'],
        ]);

        $dataCompra = $this->parseInputDate($validated['data_compra']);
        if (! $dataCompra) {
            return back()->withInput()->withErrors(['data_compra' => 'Data da compra inválida.']);
        }

        if (! Schema::hasTable('sanidade_compras')) {
            return back()
                ->withInput()
                ->withErrors(['data_compra' => "Tabela 'sanidade_compras' não existe. Execute a migration 0.16."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            SanidadeCompra::create([
                'data_compra' => $dataCompra,
                'sanidade_id' => (int) $validated['sanidade_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'nota_fiscal' => $validated['nota_fiscal'] ?? null,
                'fornecedor' => $validated['fornecedor'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
            ]);

            // Atualiza o estoque do item de sanidade (custo médio ponderado)
            $item = Sanidade::findOrFail((int) $validated['sanidade_id']);
            $estoqueAtual = (float) ($item->estoque ?? 0);
            $custoAtual = (float) ($item->custo_por_unidade ?? 0);

            $novoEstoque = $estoqueAtual + $quantidade;

            $novoCusto = $custoAtual;
            if ($novoEstoque > 0 && $quantidade > 0) {
                $valorEstoqueAtual = $estoqueAtual * $custoAtual;
                $valorEntrada = $quantidade * $valorUnitario;
                $novoCusto = ($valorEstoqueAtual + $valorEntrada) / $novoEstoque;
            }

            $item->update([
                'estoque' => $novoEstoque,
                'custo_por_unidade' => $novoCusto,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('sanidade.index')->with('success', 'Compra de sanidade registrada e estoque atualizado!');
    }

    public function storeConsumo(Request $request)
    {
        $validated = $request->validate([
            'tipo' => ['required', 'in:medicamento,vacina,desinfetante'],
            'sanidade_id' => ['required', 'integer', 'exists:sanidade,id'],
            'fase' => ['required', 'string', 'max:100'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'lote' => ['nullable', 'string', 'max:120'],
            'lote_fabricacao' => ['nullable', 'string', 'max:120'],
            'data_inicial' => ['required', 'string', 'max:30'],
            'data_final' => ['nullable', 'string', 'max:30'],
        ]);

        $dataInicial = $this->parseInputDate($validated['data_inicial']);
        if (! $dataInicial) {
            return back()->withInput()->withErrors(['data_inicial' => 'Data inicial inválida.']);
        }

        $dataFinal = ! empty($validated['data_final']) ? $this->parseInputDate($validated['data_final']) : null;
        if (! empty($validated['data_final']) && ! $dataFinal) {
            return back()->withInput()->withErrors(['data_final' => 'Data final inválida.']);
        }

        if (! Schema::hasTable('sanidade_consumos')) {
            return back()
                ->withInput()
                ->withErrors(['data_inicial' => "Tabela 'sanidade_consumos' não existe. Execute a migration 0.16."]);
        }

        $quantidade = (float) $validated['quantidade'];

        DB::beginTransaction();
        try {
            SanidadeConsumo::create([
                'sanidade_id' => (int) $validated['sanidade_id'],
                'fase' => $validated['fase'],
                'quantidade' => $quantidade,
                'lote' => $validated['lote'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
            ]);

            // Decrementa o estoque
            $item = Sanidade::findOrFail((int) $validated['sanidade_id']);
            $estoqueAtual = (float) ($item->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $item->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('sanidade.index')->with('success', 'Consumo de sanidade registrado e estoque atualizado!');
    }

    public function storeVenda(Request $request)
    {
        $validated = $request->validate([
            'data_venda' => ['required', 'string', 'max:30'],
            'sanidade_id' => ['required', 'integer', 'exists:sanidade,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'comprador' => ['nullable', 'string', 'max:255'],
        ]);

        $dataVenda = $this->parseInputDate($validated['data_venda']);
        if (! $dataVenda) {
            return back()->withInput()->withErrors(['data_venda' => 'Data da venda inválida.']);
        }

        if (! Schema::hasTable('sanidade_vendas')) {
            return back()
                ->withInput()
                ->withErrors(['data_venda' => "Tabela 'sanidade_vendas' não existe. Execute a migration 0.16."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            SanidadeVenda::create([
                'data_venda' => $dataVenda,
                'sanidade_id' => (int) $validated['sanidade_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'comprador' => $validated['comprador'] ?? null,
            ]);

            // Decrementa o estoque
            $item = Sanidade::findOrFail((int) $validated['sanidade_id']);
            $estoqueAtual = (float) ($item->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $item->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('sanidade.index')->with('success', 'Venda de sanidade registrada e estoque atualizado!');
    }

    public function storeAcerto(Request $request)
    {
        $validated = $request->validate([
            'data_acerto' => ['required', 'string', 'max:30'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.sanidade_id' => ['required', 'integer', 'exists:sanidade,id'],
            'itens.*.contagem' => ['required', 'numeric', 'min:0'],
        ]);

        $dataAcerto = $this->parseInputDate($validated['data_acerto']);
        if (! $dataAcerto) {
            return back()->withInput()->withErrors(['data_acerto' => 'Data do acerto inválida.']);
        }

        if (! Schema::hasTable('sanidade_acertos')) {
            return back()
                ->withInput()
                ->withErrors(['data_acerto' => "Tabela 'sanidade_acertos' não existe. Execute a migration 0.16."]);
        }

        DB::beginTransaction();
        try {
            foreach ($validated['itens'] as $item) {
                $sanidade = Sanidade::findOrFail((int) $item['sanidade_id']);
                $estoqueAnterior = (float) ($sanidade->estoque ?? 0);
                $contagem = (float) $item['contagem'];
                $diferenca = $contagem - $estoqueAnterior;

                SanidadeAcerto::create([
                    'data_acerto' => $dataAcerto,
                    'sanidade_id' => (int) $item['sanidade_id'],
                    'estoque_anterior' => $estoqueAnterior,
                    'contagem' => $contagem,
                    'diferenca' => $diferenca,
                ]);

                $sanidade->update([
                    'estoque' => $contagem,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('sanidade.index')->with('success', 'Acerto de estoque realizado e estoque atualizado!');
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:sanidade,codigo'],
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'in:medicamento,vacina,desinfetante'],
            'grupo' => ['nullable', 'string', 'max:255'],
            'estoque_inicial' => ['nullable', 'numeric', 'min:0'],
            'custo_por_unidade' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedor,id'],
        ]);

        if (! Schema::hasTable('sanidade')) {
            return back()
                ->withInput()
                ->withErrors(['nome' => "Tabela 'sanidade' não existe. Execute a migration 0.16."]);
        }

        Sanidade::create([
            'codigo' => $validated['codigo'],
            'nome' => $validated['nome'],
            'tipo' => $validated['tipo'],
            'grupo' => $validated['grupo'] ?? null,
            'estoque' => (float) ($validated['estoque_inicial'] ?? 0),
            'custo_por_unidade' => isset($validated['custo_por_unidade']) ? (float) $validated['custo_por_unidade'] : null,
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
        ]);

        return redirect()->route('sanidade.index')->with('success', 'Item de sanidade cadastrado com sucesso!');
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
                'saidas' => 0.0,
                'saldo' => 0.0,
            ];
        }

        if (! Schema::hasTable('sanidade_compras')) {
            return $meses;
        }

        $inicio = $hoje->copy()->subMonths(5)->startOfMonth();

        // Entradas (compras)
        $entradasRows = DB::table('sanidade_compras')
            ->where('data_compra', '>=', $inicio->format('Y-m-d'))
            ->selectRaw('YEAR(data_compra) as ano, MONTH(data_compra) as mes, COALESCE(SUM(quantidade), 0) as total')
            ->groupBy('ano', 'mes')
            ->get();

        $entradas = [];
        foreach ($entradasRows as $r) {
            $entradas[((int) $r->ano).'-'.((int) $r->mes)] = (float) $r->total;
        }

        // Saídas (consumos + vendas)
        $saidas = [];
        if (Schema::hasTable('sanidade_consumos')) {
            $consumoRows = DB::table('sanidade_consumos')
                ->where('data_inicial', '>=', $inicio->format('Y-m-d'))
                ->selectRaw('YEAR(data_inicial) as ano, MONTH(data_inicial) as mes, COALESCE(SUM(quantidade), 0) as total')
                ->groupBy('ano', 'mes')
                ->get();

            foreach ($consumoRows as $r) {
                $chave = ((int) $r->ano).'-'.((int) $r->mes);
                $saidas[$chave] = ($saidas[$chave] ?? 0.0) + (float) $r->total;
            }
        }

        if (Schema::hasTable('sanidade_vendas')) {
            $vendaRows = DB::table('sanidade_vendas')
                ->where('data_venda', '>=', $inicio->format('Y-m-d'))
                ->selectRaw('YEAR(data_venda) as ano, MONTH(data_venda) as mes, COALESCE(SUM(quantidade), 0) as total')
                ->groupBy('ano', 'mes')
                ->get();

            foreach ($vendaRows as $r) {
                $chave = ((int) $r->ano).'-'.((int) $r->mes);
                $saidas[$chave] = ($saidas[$chave] ?? 0.0) + (float) $r->total;
            }
        }

        $saldoAcumulado = 0.0;
        foreach ($meses as &$m) {
            $chave = $m['ano'].'-'.$m['mes'];
            $m['entradas'] = $entradas[$chave] ?? 0.0;
            $m['saidas'] = $saidas[$chave] ?? 0.0;
            $saldoAcumulado += $m['entradas'] - $m['saidas'];
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
