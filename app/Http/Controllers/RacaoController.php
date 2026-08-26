<?php

namespace App\Http\Controllers;

use App\Helpers\PdfLogoHelper;
use App\Models\Racao;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = Racao::query();

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%'.$request->codigo.'%');
        }

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }

        $query->with(['fornecedor', 'tipoRacao']);
        $racoes = $query->orderBy('codigo')->get();

        return view('admin.racoes.index', compact('racoes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:racao,codigo'],
            'nome' => ['required', 'string', 'max:255'],
            'classificacao' => ['required', 'string', 'max:255'],
            'tipo_racao_id' => ['required', 'exists:tipo_racao,id'],
            'fase_animal' => ['required', 'string', 'max:255'],
            'estoque' => ['required', 'numeric', 'min:0'],
            'proteina_bruta' => ['nullable', 'numeric', 'min:0'],
            'energia_metabolizavel' => ['nullable', 'numeric', 'min:0'],
            'fibra' => ['nullable', 'numeric', 'min:0'],
            'lisina' => ['nullable', 'numeric', 'min:0'],
            'calcio' => ['nullable', 'numeric', 'min:0'],
            'fosforo' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'exists:fornecedor,id'],
            'marca' => ['nullable', 'string', 'max:255'],
            'custo_por_kg' => ['nullable', 'numeric', 'min:0'],
            'unidade_compra' => ['nullable', 'string', 'max:50'],
            'peso_embalagem' => ['nullable', 'numeric', 'min:0'],
        ]);

        Racao::create($validated);

        return redirect()->to(route('admin.racoes.index', [], false))->with('success', 'Ração cadastrada com sucesso!');
    }

    public function show(Racao $racao)
    {
        return response()->json($racao->load(['fornecedor', 'tipoRacao']));
    }

    public function updateEstoque(Request $request, Racao $racao)
    {
        if (! Schema::hasTable('racao') || ! Schema::hasColumn('racao', 'estoque')) {
            return response()->json([
                'success' => false,
                'message' => 'A coluna de estoque ainda não foi criada no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'estoque' => ['required', 'numeric', 'min:0'],
        ]);

        $racao->update([
            'estoque' => $validated['estoque'],
        ]);

        return response()->json([
            'success' => true,
            'estoque' => $racao->estoque,
            'message' => 'Estoque atualizado com sucesso!',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $query = Racao::query()->with(['fornecedor', 'tipoRacao']);

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%'.$request->codigo.'%');
        }

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }

        $racoes = $query->orderBy('codigo')->get();

        $logoData = PdfLogoHelper::buildLogoData();
        $logoDataUri = $logoData['logoDataUri'];

        $data = [
            'racoes' => $racoes,
            'emitidoEm' => $logoData['emitidoEm'],
            'logoDataUri' => $logoDataUri,
        ];

        $pdf = Pdf::loadView('admin.racoes.report', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'Helvetica',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('relatorio-racoes-'.now()->format('Y-m-d').'.pdf');
    }

    public function fichaPdf(Racao $racao)
    {
        $racao->load(['fornecedor', 'tipoRacao']);

        $logoData = PdfLogoHelper::buildLogoData();
        $logoDataUri = $logoData['logoDataUri'];

        $data = [
            'racao' => $racao,
            'emitidoEm' => $logoData['emitidoEm'],
            'logoDataUri' => $logoDataUri,
        ];

        $pdf = Pdf::loadView('admin.racoes.ficha', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Helvetica',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('ficha-racao-'.$racao->codigo.'.pdf');
    }
}
