<?php

use App\Http\Controllers\AcompanhamentoFemeasController;
use App\Http\Controllers\AlteracoesController;
use App\Http\Controllers\CausaController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CriteriosController;
use App\Http\Controllers\CriteriosLogsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FemeaCompraController;
use App\Http\Controllers\FemeaController;
use App\Http\Controllers\FemeaDescarteController;
use App\Http\Controllers\FemeaMorteController;
use App\Http\Controllers\FemeaMovimentoController;
use App\Http\Controllers\FemeaVendaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\GestacaoCioController;
use App\Http\Controllers\GestacaoCoberturaController;
use App\Http\Controllers\GestacaoController;
use App\Http\Controllers\GestacaoMetasController;
use App\Http\Controllers\GestacaoPerdaController;
use App\Http\Controllers\GestacaoSaltaCioController;
use App\Http\Controllers\GrupoCausaController;
use App\Http\Controllers\MachoCompraController;
use App\Http\Controllers\MachoDescarteController;
use App\Http\Controllers\MachoMorteController;
use App\Http\Controllers\MachoMovimentoController;
use App\Http\Controllers\MachoVendaController;
use App\Http\Controllers\MaternidadeController;
use App\Http\Controllers\CrecheController;
use App\Http\Controllers\MetasController;
use App\Http\Controllers\PlantelApiController;
use App\Http\Controllers\PlantelRelatorioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RacaController;
use App\Http\Controllers\RacaoController;
use App\Http\Controllers\SemenController;
use App\Http\Controllers\TipoRacaoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\UtilitariosController;
use App\Http\Controllers\ZerarSistemaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth'])->name('dashboard');
Route::get('/plantel/analises/retencao', function () { return view('plantel.analises.retencao'); })->middleware(['auth'])->name('plantel.analises.retencao');
Route::get('/plantel/analises/ficha', function () { return view('plantel.analises.ficha'); })->middleware(['auth'])->name('plantel.analises.ficha');
Route::get('/plantel/analises/formularios/cio-leitoa/pdf', function () {
    $linhas = request()->has('linhas') ? (int) request('linhas') : 24;
    if ($linhas < 10) $linhas = 10;
    if ($linhas > 50) $linhas = 50;

    $pageHeightMm = 297;
    $marginTopMm = 10;
    $marginBottomMm = 12;
    $headerBlockMm = 14;
    $tableHeadMm = 10;
    $footerReserveMm = 10;
    $availableBodyMm = $pageHeightMm - $marginTopMm - $marginBottomMm - $headerBlockMm - $tableHeadMm - $footerReserveMm - 2;
    if ($availableBodyMm < 120) $availableBodyMm = 120;

    $rowHeightMm = round($availableBodyMm / $linhas, 1);
    if ($rowHeightMm < 5.0) $rowHeightMm = 5.0;
    if ($rowHeightMm > 10.0) $rowHeightMm = 10.0;
    $filename = 'formulario-cio-leitoa-'.now()->format('Ymd-Hi').'.pdf';

    $response = \Barryvdh\DomPDF\Facade\Pdf::loadView('plantel.analises.formularios.cio-leitoa', compact('linhas', 'rowHeightMm'))
        ->setPaper('a4', 'portrait')
        ->stream($filename);

    return $response
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->middleware(['auth'])->name('plantel.analises.formularios.cio-leitoa.pdf');
Route::get('/gestacao', GestacaoController::class)->middleware(['auth'])->name('gestacao');
Route::get('/gestacao/formulario-cobertura/pdf', function() {
    return response()->view('gestacao.formulario-cobertura', [
        'tipo' => request('tipo', 'em_branco'),
        'matriz' => request('matriz', 'todas'),
        'leitoa' => request('leitoa', 'todas'),
        'ordenar' => request('ordenar', 'matriz'),
        'quantidade' => request('quantidade', 10),
        'dias_vazias_inicio' => request('dias_vazias_inicio'),
        'dias_vazias_fim' => request('dias_vazias_fim'),
        'idade_inicio' => request('idade_inicio'),
        'idade_fim' => request('idade_fim')
    ])->header('Content-Type', 'text/html');
})->middleware(['auth'])->name('gestacao.formulario.pdf');
Route::get('/maternidade', [MaternidadeController::class, 'index'])->middleware(['auth'])->name('maternidade');
Route::get('/creche', [CrecheController::class, 'index'])->middleware(['auth'])->name('creche');
Route::get('/creche/lotes/{id}', [CrecheController::class, 'showLote'])->middleware(['auth'])->whereNumber('id')->name('creche.lotes.show');
Route::get('/terminacao', function() {
    return view('terminacao');
})->middleware(['auth'])->name('terminacao');
Route::post('/creche/lotes', [CrecheController::class, 'storeLote'])->middleware(['auth'])->name('creche.lotes.store');
Route::post('/creche/compras', [CrecheController::class, 'storeCompra'])->middleware(['auth'])->name('creche.compras.store');
Route::post('/creche/mortes', [CrecheController::class, 'storeMorte'])->middleware(['auth'])->name('creche.mortes.store');
Route::post('/maternidade/partos', [MaternidadeController::class, 'storeParto'])->middleware(['auth'])->name('maternidade.partos.store');
Route::post('/maternidade/desmames', [MaternidadeController::class, 'storeDesmame'])->middleware(['auth'])->name('maternidade.desmames.store');
Route::post('/maternidade/mortes', [MaternidadeController::class, 'storeMorteLeitao'])->middleware(['auth'])->name('maternidade.mortes.store');
Route::post('/maternidade/causas', [MaternidadeController::class, 'storeCausa'])->middleware(['auth'])->name('maternidade.causas.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/photo/{path}', [ProfileController::class, 'photo'])->where('path', '.*')->name('profile.photo');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas de Administração
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    })->name('index');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::patch('/usuarios/{user}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{user}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('/causas', [CausaController::class, 'index'])->name('causas.index');
    Route::post('/causas', [CausaController::class, 'store'])->name('causas.store');
    Route::patch('/causas/{causa}', [CausaController::class, 'update'])->name('causas.update');
    Route::delete('/causas/{causa}', [CausaController::class, 'destroy'])->name('causas.destroy');
    Route::patch('/causas/{causa}/toggle', [CausaController::class, 'toggleSituacao'])->name('causas.toggle');
    Route::get('/causas/export/pdf', [CausaController::class, 'exportPdf'])->name('causas.export-pdf');
    Route::get('/racoes', [RacaoController::class, 'index'])->name('racoes.index');
    Route::post('/racoes', [RacaoController::class, 'store'])->name('racoes.store');
    Route::get('/racoes/{racao}', [RacaoController::class, 'show'])->name('racoes.show');
    Route::patch('/racoes/{racao}/estoque', [RacaoController::class, 'updateEstoque'])->name('racoes.update-estoque');
    Route::get('/racoes/export/pdf', [RacaoController::class, 'exportPdf'])->name('racoes.export-pdf');
    Route::get('/racoes/{racao}/pdf', [RacaoController::class, 'fichaPdf'])->name('racoes.ficha-pdf');
    Route::get('/fornecedores', [FornecedorController::class, 'page'])->name('fornecedores.index');
    Route::post('/fornecedores', [FornecedorController::class, 'store'])->name('fornecedores.store');
    Route::patch('/fornecedores/{fornecedor}', [FornecedorController::class, 'update'])->name('fornecedores.update');
    Route::delete('/fornecedores/{fornecedor}', [FornecedorController::class, 'destroy'])->name('fornecedores.destroy');
    Route::get('/semen', [SemenController::class, 'page'])->name('semen.index');
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/tipos-racao', [TipoRacaoController::class, 'store'])->name('tipos-racao.store');
    Route::post('/racas', [RacaController::class, 'store'])->name('racas.store');
    Route::post('/plantel/femeas/compras', [FemeaCompraController::class, 'store'])->name('plantel.femeas.compras.store');
    Route::post('/plantel/femeas/mortes', [FemeaMorteController::class, 'store'])->name('plantel.femeas.mortes.store');
    Route::post('/plantel/femeas/descarte', [FemeaDescarteController::class, 'store'])->name('plantel.femeas.descarte.store');
    Route::post('/plantel/femeas/venda', [FemeaVendaController::class, 'store'])->name('plantel.femeas.venda.store');
    Route::delete('/plantel/femeas/movimentos/{id}', [FemeaMovimentoController::class, 'destroy'])->whereNumber('id')->name('plantel.femeas.movimentos.destroy');
    Route::post('/plantel/machos/compras', [MachoCompraController::class, 'store'])->name('plantel.machos.compras.store');
    Route::post('/plantel/machos/mortes', [MachoMorteController::class, 'store'])->name('plantel.machos.mortes.store');
    Route::post('/plantel/machos/descarte', [MachoDescarteController::class, 'store'])->name('plantel.machos.descarte.store');
    Route::post('/plantel/machos/venda', [MachoVendaController::class, 'store'])->name('plantel.machos.venda.store');
    Route::delete('/plantel/machos/movimentos/{id}', [MachoMovimentoController::class, 'destroy'])->whereNumber('id')->name('plantel.machos.movimentos.destroy');
    Route::get('/plantel/femeas', [FemeaController::class, 'index'])->name('plantel.femeas.index');
    Route::get('/plantel/femeas/{femea}', [FemeaController::class, 'show'])->name('plantel.femeas.show');
    Route::get('/relatorios/plantel/femeas', [PlantelRelatorioController::class, 'femeas'])->name('relatorios.plantel.femeas');
    Route::get('/relatorios/plantel/machos', [PlantelRelatorioController::class, 'machos'])->name('relatorios.plantel.machos');
    Route::get('/metas', [MetasController::class, 'page'])->name('metas.index');
    Route::post('/metas', [MetasController::class, 'store'])->name('metas.store');
    Route::get('/criterios', function() { return redirect()->route('admin.metas.index'); })->name('criterios.index');
    Route::post('/criterios', [MetasController::class, 'store'])->name('criterios.store');
    Route::get('/criterios/logs', [CriteriosLogsController::class, 'page'])->name('criterios.logs');
    Route::get('/zerar', [ZerarSistemaController::class, 'page'])->name('zerar.index');
    Route::post('/zerar', [ZerarSistemaController::class, 'store'])->name('zerar.store');
    Route::post('/grupo-causa', [GrupoCausaController::class, 'store'])->name('grupo-causa.store');
    Route::get('/alteracoes', [AlteracoesController::class, 'index'])->name('alteracoes.index');
});

// API Routes
Route::get('/api/grupos-causa', [GrupoCausaController::class, 'index']);
Route::get('/api/fornecedores', [FornecedorController::class, 'index']);
Route::get('/api/tipos-racao', [TipoRacaoController::class, 'index']);
Route::get('/api/racas', [RacaController::class, 'index']);
Route::get('/api/semen', [SemenController::class, 'index']);
Route::post('/api/semen', [SemenController::class, 'store']);
Route::get('/api/semen/{id}', [SemenController::class, 'show'])->whereNumber('id');
Route::patch('/api/semen/{id}', [SemenController::class, 'update'])->whereNumber('id');
Route::delete('/api/semen/{id}', [SemenController::class, 'destroy'])->whereNumber('id');
Route::get('/api/plantel/femeas/compras', [FemeaCompraController::class, 'index']);
Route::get('/api/plantel/femeas', [PlantelApiController::class, 'femeas']);
Route::get('/api/plantel/femeas/cios', [PlantelApiController::class, 'cios']);
Route::put('/api/plantel/femeas/cios/{id}', [PlantelApiController::class, 'updateCio'])->whereNumber('id');
Route::delete('/api/plantel/femeas/cios/{id}', [PlantelApiController::class, 'deleteCio'])->whereNumber('id');
Route::put('/api/plantel/femeas/{id}', [PlantelApiController::class, 'updateFemea'])->whereNumber('id');
Route::delete('/api/plantel/femeas/{id}', [PlantelApiController::class, 'deleteFemea'])->whereNumber('id');
Route::get('/api/plantel/causas-morte', [PlantelApiController::class, 'causasMorte']);
Route::get('/api/plantel/causas-venda', [PlantelApiController::class, 'causasVenda']);
Route::get('/api/plantel/causas-descarte', [PlantelApiController::class, 'causasDescarte']);
Route::get('/api/plantel/femeas/mortes', [FemeaMovimentoController::class, 'mortes']);
Route::get('/api/plantel/femeas/descartes', [FemeaMovimentoController::class, 'descartes']);
Route::get('/api/plantel/femeas/vendas', [FemeaMovimentoController::class, 'vendas']);
Route::middleware('auth')->get('/api/plantel/femeas/acompanhamento', [AcompanhamentoFemeasController::class, 'index']);
Route::middleware('auth')->get('/api/plantel/femeas/acompanhamento/{id}', [AcompanhamentoFemeasController::class, 'show'])->whereNumber('id');
Route::middleware('auth')->get('/api/plantel/femeas/ficha/{id}', [\App\Http\Controllers\FichaMatrizController::class, 'show'])->whereNumber('id');
Route::middleware('auth')->get('/admin/plantel/femeas/{id}/ficha-pdf', [\App\Http\Controllers\FichaMatrizController::class, 'generatePdf'])->whereNumber('id')->name('admin.plantel.femeas.ficha-pdf');
Route::middleware('auth')->get('/api/plantel/femeas/retencao', [\App\Http\Controllers\RetencaoFemeasController::class, 'index']);
Route::get('/api/plantel/machos/compras', [MachoCompraController::class, 'index']);
Route::get('/api/plantel/machos', [PlantelApiController::class, 'machos']);
Route::get('/api/plantel/machos/mortes', [MachoMovimentoController::class, 'mortes']);
Route::get('/api/plantel/machos/descartes', [MachoMovimentoController::class, 'descartes']);
Route::get('/api/plantel/machos/vendas', [MachoMovimentoController::class, 'vendas']);
Route::get('/api/metas', [MetasController::class, 'index']);
Route::middleware('auth')->get('/api/usuarios', [UsuarioController::class, 'apiIndex']);
if ((bool) config('masterpig.chatbot_enabled', false)) {
    Route::middleware('auth')
        ->post('/api/chatbot', [ChatbotController::class, 'ask'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
}
Route::middleware('auth')->get('/api/utilitarios', [UtilitariosController::class, 'index']);
Route::middleware('auth')->post('/api/utilitarios/localizacoes', [UtilitariosController::class, 'storeLocalizacao']);
Route::middleware('auth')->post('/api/utilitarios/baias', [UtilitariosController::class, 'storeBaia']);
Route::middleware('auth')->get('/api/criterios', [CriteriosController::class, 'index']);
Route::middleware(['auth', 'admin'])->get('/api/criterios/logs', [CriteriosLogsController::class, 'index']);
Route::middleware('auth')->get('/api/gestacao/coberturas', [GestacaoCoberturaController::class, 'index']);
Route::middleware('auth')->post('/api/gestacao/coberturas', [GestacaoCoberturaController::class, 'store']);
Route::middleware('auth')->get('/api/gestacao/coberturas/{id}', [GestacaoCoberturaController::class, 'show'])->whereNumber('id');
Route::middleware('auth')->patch('/api/gestacao/coberturas/{id}', [GestacaoCoberturaController::class, 'update'])->whereNumber('id');
Route::middleware('auth')->delete('/api/gestacao/coberturas/{id}', [GestacaoCoberturaController::class, 'destroy'])->whereNumber('id');
Route::middleware('auth')->get('/api/gestacao/cio', [GestacaoCioController::class, 'index']);
Route::middleware('auth')->post('/api/gestacao/cio', [GestacaoCioController::class, 'store']);
Route::middleware('auth')->delete('/api/gestacao/cio/{id}', [GestacaoCioController::class, 'destroy'])->whereNumber('id');
Route::middleware('auth')->get('/api/gestacao/perdas', [GestacaoPerdaController::class, 'index']);
Route::middleware('auth')->post('/api/gestacao/perdas', [GestacaoPerdaController::class, 'store']);
Route::middleware('auth')->get('/api/gestacao/salta-cio', [GestacaoSaltaCioController::class, 'index']);
Route::middleware('auth')->post('/api/gestacao/salta-cio', [GestacaoSaltaCioController::class, 'store']);
Route::middleware('auth')->delete('/api/gestacao/salta-cio/{id}', [GestacaoSaltaCioController::class, 'destroy'])->whereNumber('id');
Route::middleware('auth')->get('/api/gestacao/metas', [GestacaoMetasController::class, 'index']);
Route::middleware('auth')->post('/api/gestacao/metas', [GestacaoMetasController::class, 'store']);

require __DIR__.'/auth.php';
