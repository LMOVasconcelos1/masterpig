<?php

namespace App\Http\Controllers;

use App\Helpers\PdfLogoHelper;
use App\Models\Causa;
use App\Models\GrupoCausa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            $query->where('nome', 'like', '%'.$request->nome.'%');
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
            'situacao' => ['nullable', 'boolean'],
            'grupo_causa_id' => ['required', 'exists:grupo_causa,id'],
        ]);

        $validated['situacao'] = $request->boolean('situacao');

        Causa::create($validated);

        return redirect()->to(route('admin.causas.index', [], false))->with('success', 'Causa cadastrada com sucesso!');
    }

    public function update(Request $request, Causa $causa)
    {
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('causa', 'codigo')->ignore($causa->id)],
            'nome' => ['required', 'string', 'max:255'],
            'situacao' => ['nullable', 'boolean'],
            'grupo_causa_id' => ['required', 'exists:grupo_causa,id'],
        ]);

        $validated['situacao'] = $request->boolean('situacao');

        $causa->update($validated);

        return redirect()->to(route('admin.causas.index', [], false))->with('success', 'Causa atualizada com sucesso!');
    }

    public function destroy(Causa $causa)
    {
        try {
            $causa->delete();
        } catch (\Throwable $e) {
            return redirect()->to(route('admin.causas.index', [], false))->with('error', 'Não foi possível excluir a causa. Verifique se ela está sendo utilizada.');
        }

        return redirect()->to(route('admin.causas.index', [], false))->with('success', 'Causa excluída com sucesso!');
    }

    /**
     * Alterna a situação da causa via AJAX.
     */
    public function toggleSituacao(Causa $causa)
    {
        $causa->situacao = ! $causa->situacao;
        $causa->save();

        return response()->json([
            'success' => true,
            'situacao' => $causa->situacao,
            'message' => 'Situação atualizada com sucesso!',
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
            $query->where('nome', 'like', '%'.$request->nome.'%');
        }

        if ($request->filled('situacao')) {
            $query->where('situacao', $request->situacao);
        }

        $causas = $query->get();

        $logoData = PdfLogoHelper::buildLogoData();
        $logoDataUri = $logoData['logoDataUri'];

        $data = [
            'causas' => $causas,
            'emitidoEm' => $logoData['emitidoEm'],
            'logoDataUri' => $logoDataUri,
        ];

        $pdf = Pdf::loadView('admin.causas.report', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'Helvetica',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return $pdf->stream('relatorio-causas-'.now()->format('Y-m-d').'.pdf');
    }
}
