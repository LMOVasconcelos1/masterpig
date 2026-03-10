<?php

use App\Http\Controllers\CausaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FemeaCompraController;
use App\Http\Controllers\FemeaController;
use App\Http\Controllers\FemeaMorteController;
use App\Http\Controllers\FemeaDescarteController;
use App\Http\Controllers\FemeaMovimentoController;
use App\Http\Controllers\FemeaVendaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\GrupoCausaController;
use App\Http\Controllers\MachoCompraController;
use App\Http\Controllers\MachoDescarteController;
use App\Http\Controllers\MachoMorteController;
use App\Http\Controllers\MachoMovimentoController;
use App\Http\Controllers\MachoVendaController;
use App\Http\Controllers\PlantelApiController;
use App\Http\Controllers\PlantelRelatorioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RacaController;
use App\Http\Controllers\RacaoController;
use App\Http\Controllers\TipoRacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas de Administração
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/causas', [CausaController::class, 'index'])->name('causas.index');
    Route::post('/causas', [CausaController::class, 'store'])->name('causas.store');
    Route::patch('/causas/{causa}/toggle', [CausaController::class, 'toggleSituacao'])->name('causas.toggle');
    Route::get('/causas/export/pdf', [CausaController::class, 'exportPdf'])->name('causas.export-pdf');
    Route::get('/racoes', [RacaoController::class, 'index'])->name('racoes.index');
    Route::post('/racoes', [RacaoController::class, 'store'])->name('racoes.store');
    Route::get('/racoes/{racao}', [RacaoController::class, 'show'])->name('racoes.show');
    Route::patch('/racoes/{racao}/estoque', [RacaoController::class, 'updateEstoque'])->name('racoes.update-estoque');
    Route::get('/racoes/export/pdf', [RacaoController::class, 'exportPdf'])->name('racoes.export-pdf');
    Route::get('/racoes/{racao}/pdf', [RacaoController::class, 'fichaPdf'])->name('racoes.ficha-pdf');
    Route::post('/fornecedores', [FornecedorController::class, 'store'])->name('fornecedores.store');
    Route::post('/tipos-racao', [TipoRacaoController::class, 'store'])->name('tipos-racao.store');
    Route::post('/racas', [RacaController::class, 'store'])->name('racas.store');
    Route::post('/plantel/femeas/compras', [FemeaCompraController::class, 'store'])->name('plantel.femeas.compras.store');
    Route::post('/plantel/femeas/mortes', [FemeaMorteController::class, 'store'])->name('plantel.femeas.mortes.store');
    Route::post('/plantel/femeas/descarte', [FemeaDescarteController::class, 'store'])->name('plantel.femeas.descarte.store');
    Route::post('/plantel/femeas/venda', [FemeaVendaController::class, 'store'])->name('plantel.femeas.venda.store');
    Route::post('/plantel/machos/compras', [MachoCompraController::class, 'store'])->name('plantel.machos.compras.store');
    Route::post('/plantel/machos/mortes', [MachoMorteController::class, 'store'])->name('plantel.machos.mortes.store');
    Route::post('/plantel/machos/descarte', [MachoDescarteController::class, 'store'])->name('plantel.machos.descarte.store');
    Route::post('/plantel/machos/venda', [MachoVendaController::class, 'store'])->name('plantel.machos.venda.store');
    Route::get('/plantel/femeas/{femea}', [FemeaController::class, 'show'])->name('plantel.femeas.show');
    Route::get('/relatorios/plantel/femeas', [PlantelRelatorioController::class, 'femeas'])->name('relatorios.plantel.femeas');
    Route::get('/relatorios/plantel/machos', [PlantelRelatorioController::class, 'machos'])->name('relatorios.plantel.machos');
    Route::post('/grupo-causa', [GrupoCausaController::class, 'store'])->name('grupo-causa.store');
});

// API Routes
Route::get('/api/grupos-causa', [GrupoCausaController::class, 'index']);
Route::get('/api/fornecedores', [FornecedorController::class, 'index']);
Route::get('/api/tipos-racao', [TipoRacaoController::class, 'index']);
Route::get('/api/racas', [RacaController::class, 'index']);
Route::get('/api/plantel/femeas/compras', [FemeaCompraController::class, 'index']);
Route::get('/api/plantel/femeas', [PlantelApiController::class, 'femeas']);
Route::get('/api/plantel/causas-morte', [PlantelApiController::class, 'causasMorte']);
Route::get('/api/plantel/causas-venda', [PlantelApiController::class, 'causasVenda']);
Route::get('/api/plantel/causas-descarte', [PlantelApiController::class, 'causasDescarte']);
Route::get('/api/plantel/femeas/mortes', [FemeaMovimentoController::class, 'mortes']);
Route::get('/api/plantel/femeas/descartes', [FemeaMovimentoController::class, 'descartes']);
Route::get('/api/plantel/femeas/vendas', [FemeaMovimentoController::class, 'vendas']);
Route::get('/api/plantel/machos/compras', [MachoCompraController::class, 'index']);
Route::get('/api/plantel/machos', [PlantelApiController::class, 'machos']);
Route::get('/api/plantel/machos/mortes', [MachoMovimentoController::class, 'mortes']);
Route::get('/api/plantel/machos/descartes', [MachoMovimentoController::class, 'descartes']);
Route::get('/api/plantel/machos/vendas', [MachoMovimentoController::class, 'vendas']);

require __DIR__.'/auth.php';
