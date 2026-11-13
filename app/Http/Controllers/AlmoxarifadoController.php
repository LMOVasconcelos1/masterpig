<?php

namespace App\Http\Controllers;

use App\Models\Almoxarifado;
use App\Models\AlmoxarifadoAcerto;
use App\Models\AlmoxarifadoCompra;
use App\Models\AlmoxarifadoConsumo;
use App\Models\AlmoxarifadoVenda;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlmoxarifadoController extends Controller
{
    public function index()
    {
        $stats = [
            'estoque_total' => 0.0,
            'valor_estoque' => 0.0,
        ];

        $materiaisEstoque = [];
        $compras = [];
        $consumos = [];
        $vendas = [];
        $acertos = [];
        $materiais = [];
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

        // Resumo de estoque e valor por material
        if (Schema::hasTable('almoxarifado')) {
            $rows = DB::table('almoxarifado')
                ->orderBy('codigo')
                ->get([
                    'id',
                    'codigo',
                    'nome',
                    'grupo',
                    'estoque',
                    'custo_por_unidade',
                ]);

            foreach ($rows as $row) {
                $estoque = (float) ($row->estoque ?? 0);
                $custo = (float) ($row->custo_por_unidade ?? 0);
                $valorTotal = $estoque * $custo;

                $stats['estoque_total'] += $estoque;
                $stats['valor_estoque'] += $valorTotal;

                $materiaisEstoque[] = [
                    'id' => (int) $row->id,
                    'codigo' => (string) $row->codigo,
                    'nome' => (string) $row->nome,
                    'grupo' => (string) ($row->grupo ?? ''),
                    'estoque' => $estoque,
                    'custo' => $custo,
                    'valor_total' => $valorTotal,
                ];

                if (! empty($row->grupo) && ! in_array($row->grupo, $grupos, true)) {
                    $grupos[] = (string) $row->grupo;
                }
            }

            // Lista de materiais para os selects dos modais
            $materiais = $rows->map(fn ($r) => [
                'id' => (int) $r->id,
                'codigo' => (string) $r->codigo,
                'nome' => (string) $r->nome,
                'grupo' => (string) ($r->grupo ?? ''),
                'estoque' => (float) ($r->estoque ?? 0),
            ])->all();
        }

        // Listagem de compras (últimas 200)
        if (Schema::hasTable('almoxarifado_compras') && Schema::hasTable('almoxarifado')) {
            $rows = DB::table('almoxarifado_compras as c')
                ->join('almoxarifado as m', 'm.id', '=', 'c.almoxarifado_id')
                ->orderByDesc('c.data_compra')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.data_compra',
                    'c.almoxarifado_id',
                    'm.codigo as material_codigo',
                    'm.nome as material_nome',
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
                    'material' => trim(($r->material_codigo ? $r->material_codigo.' - ' : '').($r->material_nome ?? '')),
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
        if (Schema::hasTable('almoxarifado_consumos') && Schema::hasTable('almoxarifado')) {
            $rows = DB::table('almoxarifado_consumos as c')
                ->join('almoxarifado as m', 'm.id', '=', 'c.almoxarifado_id')
                ->orderByDesc('c.data_consumo')
                ->orderByDesc('c.id')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.almoxarifado_id',
                    'm.codigo as material_codigo',
                    'm.nome as material_nome',
                    'c.fase',
                    'c.quantidade',
                    'c.lote',
                    'c.lote_fabricacao',
                    'c.data_consumo',
                ]);

            $consumos = $rows->map(function ($r) {
                $data = empty($r->data_consumo) ? null : Carbon::parse($r->data_consumo)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'material' => trim(($r->material_codigo ? $r->material_codigo.' - ' : '').($r->material_nome ?? '')),
                    'fase' => (string) ($r->fase ?? ''),
                    'quantidade' => (float) $r->quantidade,
                    'lote' => (string) ($r->lote ?? ''),
                    'lote_fabricacao' => (string) ($r->lote_fabricacao ?? ''),
                    'data_consumo' => PigCycleService::formatDisplayDate($data),
                ];
            })->all();
        }

        // Listagem de vendas (últimas 200)
        if (Schema::hasTable('almoxarifado_vendas') && Schema::hasTable('almoxarifado')) {
            $rows = DB::table('almoxarifado_vendas as v')
                ->join('almoxarifado as m', 'm.id', '=', 'v.almoxarifado_id')
                ->orderByDesc('v.data_venda')
                ->orderByDesc('v.id')
                ->limit(200)
                ->get([
                    'v.id',
                    'v.data_venda',
                    'v.almoxarifado_id',
                    'm.codigo as material_codigo',
                    'm.nome as material_nome',
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
                    'material' => trim(($r->material_codigo ? $r->material_codigo.' - ' : '').($r->material_nome ?? '')),
                    'quantidade' => (float) $r->quantidade,
                    'valor_unitario' => (float) $r->valor_unitario,
                    'valor_total' => (float) $r->valor_total,
                    'comprador' => (string) ($r->comprador ?? ''),
                ];
            })->all();
        }

        // Listagem de acertos (últimas 200)
        if (Schema::hasTable('almoxarifado_acertos') && Schema::hasTable('almoxarifado')) {
            $rows = DB::table('almoxarifado_acertos as a')
                ->join('almoxarifado as m', 'm.id', '=', 'a.almoxarifado_id')
                ->orderByDesc('a.data_acerto')
                ->orderByDesc('a.id')
                ->limit(200)
                ->get([
                    'a.id',
                    'a.data_acerto',
                    'a.almoxarifado_id',
                    'm.codigo as material_codigo',
                    'm.nome as material_nome',
                    'a.estoque_anterior',
                    'a.contagem',
                    'a.diferenca',
                ]);

            $acertos = $rows->map(function ($r) {
                $data = empty($r->data_acerto) ? null : Carbon::parse($r->data_acerto)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_acerto' => PigCycleService::formatDisplayDate($data),
                    'material' => trim(($r->material_codigo ? $r->material_codigo.' - ' : '').($r->material_nome ?? '')),
                    'estoque_anterior' => (float) $r->estoque_anterior,
                    'contagem' => (float) $r->contagem,
                    'diferenca' => (float) $r->diferenca,
                ];
            })->all();

            $ultimoAcerto = $acertos[0]['data_acerto'] ?? null;
        }

        // Dados do gráfico: movimentação dos últimos 6 meses
        $movimentacao = $this->buildMovimentacao6Meses();

        return view('almoxarifado.index', [
            'stats' => $stats,
            'materiaisEstoque' => $materiaisEstoque,
            'compras' => $compras,
            'consumos' => $consumos,
            'vendas' => $vendas,
            'acertos' => $acertos,
            'materiais' => $materiais,
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
            'almoxarifado_id' => ['required', 'integer', 'exists:almoxarifado,id'],
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

        if (! Schema::hasTable('almoxarifado_compras')) {
            return back()
                ->withInput()
                ->withErrors(['data_compra' => "Tabela 'almoxarifado_compras' não existe. Execute a migration 0.17."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            AlmoxarifadoCompra::create([
                'data_compra' => $dataCompra,
                'almoxarifado_id' => (int) $validated['almoxarifado_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'nota_fiscal' => $validated['nota_fiscal'] ?? null,
                'fornecedor' => $validated['fornecedor'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
            ]);

            // Atualiza o estoque do material (custo médio ponderado)
            $material = Almoxarifado::findOrFail((int) $validated['almoxarifado_id']);
            $estoqueAtual = (float) ($material->estoque ?? 0);
            $custoAtual = (float) ($material->custo_por_unidade ?? 0);

            $novoEstoque = $estoqueAtual + $quantidade;

            $novoCusto = $custoAtual;
            if ($novoEstoque > 0 && $quantidade > 0) {
                $valorEstoqueAtual = $estoqueAtual * $custoAtual;
                $valorEntrada = $quantidade * $valorUnitario;
                $novoCusto = ($valorEstoqueAtual + $valorEntrada) / $novoEstoque;
            }

            $material->update([
                'estoque' => $novoEstoque,
                'custo_por_unidade' => $novoCusto,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('almoxarifado.index')->with('success', 'Compra de material registrada e estoque atualizado!');
    }

    public function storeConsumo(Request $request)
    {
        $validated = $request->validate([
            'almoxarifado_id' => ['required', 'integer', 'exists:almoxarifado,id'],
            'fase' => ['required', 'string', 'max:100'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'lote' => ['nullable', 'string', 'max:120'],
            'lote_fabricacao' => ['nullable', 'string', 'max:120'],
            'data_consumo' => ['required', 'string', 'max:30'],
        ]);

        $dataConsumo = $this->parseInputDate($validated['data_consumo']);
        if (! $dataConsumo) {
            return back()->withInput()->withErrors(['data_consumo' => 'Data do consumo inválida.']);
        }

        if (! Schema::hasTable('almoxarifado_consumos')) {
            return back()
                ->withInput()
                ->withErrors(['data_consumo' => "Tabela 'almoxarifado_consumos' não existe. Execute a migration 0.17."]);
        }

        $quantidade = (float) $validated['quantidade'];

        DB::beginTransaction();
        try {
            AlmoxarifadoConsumo::create([
                'almoxarifado_id' => (int) $validated['almoxarifado_id'],
                'fase' => $validated['fase'],
                'quantidade' => $quantidade,
                'lote' => $validated['lote'] ?? null,
                'lote_fabricacao' => $validated['lote_fabricacao'] ?? null,
                'data_consumo' => $dataConsumo,
            ]);

            // Decrementa o estoque
            $material = Almoxarifado::findOrFail((int) $validated['almoxarifado_id']);
            $estoqueAtual = (float) ($material->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $material->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('almoxarifado.index')->with('success', 'Consumo de material registrado e estoque atualizado!');
    }

    public function storeVenda(Request $request)
    {
        $validated = $request->validate([
            'data_venda' => ['required', 'string', 'max:30'],
            'almoxarifado_id' => ['required', 'integer', 'exists:almoxarifado,id'],
            'quantidade' => ['required', 'numeric', 'min:0'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'comprador' => ['nullable', 'string', 'max:255'],
        ]);

        $dataVenda = $this->parseInputDate($validated['data_venda']);
        if (! $dataVenda) {
            return back()->withInput()->withErrors(['data_venda' => 'Data da venda inválida.']);
        }

        if (! Schema::hasTable('almoxarifado_vendas')) {
            return back()
                ->withInput()
                ->withErrors(['data_venda' => "Tabela 'almoxarifado_vendas' não existe. Execute a migration 0.17."]);
        }

        $quantidade = (float) $validated['quantidade'];
        $valorUnitario = (float) $validated['valor_unitario'];
        $valorTotal = (float) $validated['valor_total'];

        DB::beginTransaction();
        try {
            AlmoxarifadoVenda::create([
                'data_venda' => $dataVenda,
                'almoxarifado_id' => (int) $validated['almoxarifado_id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnitario,
                'valor_total' => $valorTotal,
                'comprador' => $validated['comprador'] ?? null,
            ]);

            // Decrementa o estoque
            $material = Almoxarifado::findOrFail((int) $validated['almoxarifado_id']);
            $estoqueAtual = (float) ($material->estoque ?? 0);
            $novoEstoque = max(0, $estoqueAtual - $quantidade);

            $material->update([
                'estoque' => $novoEstoque,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('almoxarifado.index')->with('success', 'Venda de material registrada e estoque atualizado!');
    }

    public function storeAcerto(Request $request)
    {
        $validated = $request->validate([
            'data_acerto' => ['required', 'string', 'max:30'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.almoxarifado_id' => ['required', 'integer', 'exists:almoxarifado,id'],
            'itens.*.contagem' => ['required', 'numeric', 'min:0'],
        ]);

        $dataAcerto = $this->parseInputDate($validated['data_acerto']);
        if (! $dataAcerto) {
            return back()->withInput()->withErrors(['data_acerto' => 'Data do acerto inválida.']);
        }

        if (! Schema::hasTable('almoxarifado_acertos')) {
            return back()
                ->withInput()
                ->withErrors(['data_acerto' => "Tabela 'almoxarifado_acertos' não existe. Execute a migration 0.17."]);
        }

        DB::beginTransaction();
        try {
            foreach ($validated['itens'] as $item) {
                $material = Almoxarifado::findOrFail((int) $item['almoxarifado_id']);
                $estoqueAnterior = (float) ($material->estoque ?? 0);
                $contagem = (float) $item['contagem'];
                $diferenca = $contagem - $estoqueAnterior;

                AlmoxarifadoAcerto::create([
                    'data_acerto' => $dataAcerto,
                    'almoxarifado_id' => (int) $item['almoxarifado_id'],
                    'estoque_anterior' => $estoqueAnterior,
                    'contagem' => $contagem,
                    'diferenca' => $diferenca,
                ]);

                $material->update([
                    'estoque' => $contagem,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('almoxarifado.index')->with('success', 'Acerto de estoque realizado e estoque atualizado!');
    }

    public function storeMaterial(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:almoxarifado,codigo'],
            'nome' => ['required', 'string', 'max:255'],
            'grupo' => ['nullable', 'string', 'max:255'],
            'estoque_inicial' => ['nullable', 'numeric', 'min:0'],
            'custo_por_unidade' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'integer', 'exists:fornecedor,id'],
        ]);

        if (! Schema::hasTable('almoxarifado')) {
            return back()
                ->withInput()
                ->withErrors(['nome' => "Tabela 'almoxarifado' não existe. Execute a migration 0.17."]);
        }

        Almoxarifado::create([
            'codigo' => $validated['codigo'],
            'nome' => $validated['nome'],
            'grupo' => $validated['grupo'] ?? null,
            'estoque' => (float) ($validated['estoque_inicial'] ?? 0),
            'custo_por_unidade' => isset($validated['custo_por_unidade']) ? (float) $validated['custo_por_unidade'] : null,
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
        ]);

        return redirect()->route('almoxarifado.index')->with('success', 'Material cadastrado com sucesso!');
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

        if (! Schema::hasTable('almoxarifado_compras')) {
            return $meses;
        }

        $inicio = $hoje->copy()->subMonths(5)->startOfMonth();

        // Entradas (compras)
        $entradasRows = DB::table('almoxarifado_compras')
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
        if (Schema::hasTable('almoxarifado_consumos')) {
            $consumoRows = DB::table('almoxarifado_consumos')
                ->where('data_consumo', '>=', $inicio->format('Y-m-d'))
                ->selectRaw('YEAR(data_consumo) as ano, MONTH(data_consumo) as mes, COALESCE(SUM(quantidade), 0) as total')
                ->groupBy('ano', 'mes')
                ->get();

            foreach ($consumoRows as $r) {
                $chave = ((int) $r->ano).'-'.((int) $r->mes);
                $saidas[$chave] = ($saidas[$chave] ?? 0.0) + (float) $r->total;
            }
        }

        if (Schema::hasTable('almoxarifado_vendas')) {
            $vendaRows = DB::table('almoxarifado_vendas')
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
