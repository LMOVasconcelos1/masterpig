<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\GrupoCausa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class CausaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Causa::with('grupoCausa');

        if ($request->filled('grupo_id')) {
            $query->where('grupo_causa_id', $request->grupo_id);
        }

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('situacao')) {
            $query->where('situacao', $request->situacao);
        }

        $causas = $query->get();
        $gruposCausa = GrupoCausa::all();
        
        return view('admin.causas.index', compact('causas', 'gruposCausa'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:causa,codigo'],
            'nome' => ['required', 'string', 'max:255'],
            'situacao' => ['nullable'],
            'grupo_causa_id' => ['required', 'exists:grupo_causa,id'],
        ]);

        $validated['situacao'] = $request->has('situacao');

        Causa::create($validated);

        return redirect()->route('admin.causas.index')->with('success', 'Causa cadastrada com sucesso!');
    }

    /**
     * Alterna a situação da causa via AJAX.
     */
    public function toggleSituacao(Causa $causa)
    {
        $causa->situacao = !$causa->situacao;
        $causa->save();

        return response()->json([
            'success' => true,
            'situacao' => $causa->situacao,
            'message' => 'Situação atualizada com sucesso!'
        ]);
    }

    /**
     * Exporta a listagem de causas para PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = Causa::with('grupoCausa');

        if ($request->filled('grupo_id')) {
            $query->where('grupo_causa_id', $request->grupo_id);
        }

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('situacao')) {
            $query->where('situacao', $request->situacao);
        }

        $causas = $query->get();
        
        $data = [
            'causas' => $causas,
            'data_emissao' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('admin.causas.report', $data);
        
        return $pdf->stream('relatorio-causas-' . now()->format('Y-m-d') . '.pdf');
    }
}
