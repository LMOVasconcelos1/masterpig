<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use Illuminate\Support\Facades\Schema;

class FemeaController extends Controller
{
    public function show(Femea $femea)
    {
        if (! Schema::hasTable('femea')) {
            abort(404);
        }

        $femea->load(['raca', 'fornecedor']);

        return view('admin.plantel.femeas.show', compact('femea'));
    }
}
