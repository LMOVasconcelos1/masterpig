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
use App\Http\Controllers\TerminacaoController;
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
    ])->header('Content-Type', 'text/html; charset=utf-8');
})->middleware(['auth'])->name('gestacao.formulario.pdf');
Route::get('/maternidade', [MaternidadeController::class, 'index'])->middleware(['auth'])->name('maternidade');
Route::get('/creche', [CrecheController::class, 'index'])->middleware(['auth'])->name('creche');
Route::get('/creche/lotes/{id}', [CrecheController::class, 'showLote'])->middleware(['auth'])->whereNumber('id')->name('creche.lotes.show');
Route::get('/terminacao', [TerminacaoController::class, 'index'])->middleware(['auth'])->name('terminacao');
Route::get('/terminacao/lotes/{id}', [TerminacaoController::class, 'showLote'])->middleware(['auth'])->whereNumber('id')->name('terminacao.lotes.show');
Route::post('/terminacao/lotes', [TerminacaoController::class, 'storeLote'])->middleware(['auth'])->name('terminacao.lotes.store');
Route::post('/terminacao/entradas', [TerminacaoController::class, 'storeEntrada'])->middleware(['auth'])->name('terminacao.entradas.store');
Route::post('/terminacao/mortes', [TerminacaoController::class, 'storeMorte'])->middleware(['auth'])->name('terminacao.mortes.store');
Route::post('/terminacao/transferencias', [TerminacaoController::class, 'storeTransferencia'])->middleware(['auth'])->name('terminacao.transferencias.store');
Route::post('/terminacao/vendas', [TerminacaoController::class, 'storeVenda'])->middleware(['auth'])->name('terminacao.vendas.store');
Route::post('/terminacao/pesos', [TerminacaoController::class, 'storePeso'])->middleware(['auth'])->name('terminacao.pesos.store');
Route::post('/terminacao/lotes/{id}/fechar', [TerminacaoController::class, 'fecharLote'])->middleware(['auth'])->whereNumber('id')->name('terminacao.lotes.fechar');
Route::post('/terminacao/transferir-da-creche', [TerminacaoController::class, 'transferirDaCreche'])->middleware(['auth'])->name('terminacao.transferir-da-creche');
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

    Route::get('/documentacao/pdf', function () {
        $generatedAt = now()->format('d/m/Y H:i');
        $projectRoot = base_path();

        $html = <<<HTML
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MasterPig — Documentação</title>
  <style>
    @page { margin: 26mm 18mm 22mm 18mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; line-height: 1.45; color: #111827; }
    h1 { font-size: 22px; margin: 0 0 8px 0; }
    h2 { font-size: 16px; margin: 18px 0 8px 0; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb; }
    h3 { font-size: 13px; margin: 14px 0 6px 0; }
    .muted { color: #6b7280; }
    .cover { text-align: center; margin-top: 28mm; }
    .cover .subtitle { font-size: 12px; margin-top: 10px; }
    .kv { margin: 10px auto 0 auto; width: 100%; max-width: 520px; }
    .kv td { padding: 4px 0; vertical-align: top; }
    .kv td:first-child { width: 140px; color: #6b7280; }
    ul { margin: 6px 0 10px 18px; padding: 0; }
    li { margin: 2px 0; }
    .page-break { page-break-before: always; }
    .mono { font-family: DejaVu Sans Mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 10.5px; }
    .box { border: 1px solid #e5e7eb; padding: 10px 12px; border-radius: 6px; background: #fafafa; }
  </style>
</head>
<body>

  <div class="cover">
    <h1>MasterPig</h1>
    <div class="subtitle muted">Documentação do projeto (estado atual)</div>
    <table class="kv" cellspacing="0" cellpadding="0">
      <tr><td>Gerado em</td><td>{$generatedAt}</td></tr>
      <tr><td>Projeto</td><td>MasterPig (manejo suinícola)</td></tr>
      <tr><td>Stack</td><td>PHP 8.2 + Laravel 12 + MySQL/MariaDB (multi-tenant) + Blade + Alpine.js + Tailwind + Vite</td></tr>
      <tr><td>Raiz do projeto</td><td class="mono">{$projectRoot}</td></tr>
    </table>
  </div>

  <div class="page-break"></div>

  <h2>Visão Geral do projeto</h2>
  <ul>
    <li>Sistema web para controle de plantel (fêmeas e machos), gestação, maternidade e creche, com cadastros e parametrizações de apoio.</li>
    <li>Arquitetura multi-tenant por CNPJ: cada granja/cliente opera em um banco de dados dedicado, selecionado no login.</li>
    <li>Interface principal em Blade, com interações em Alpine.js consumindo endpoints internos em <span class="mono">/api/...</span>.</li>
  </ul>

  <h2>Arquitetura completa</h2>
  <h3>Camadas</h3>
  <ul>
    <li><b>UI (Blade)</b>: <span class="mono">resources/views</span> (layouts, telas de módulos e admin).</li>
    <li><b>Rotas</b>: <span class="mono">routes/web.php</span> e <span class="mono">routes/auth.php</span>.</li>
    <li><b>Controllers</b>: <span class="mono">app/Http/Controllers</span>.</li>
    <li><b>Serviços</b>: regras do ciclo/calendário em <span class="mono">app/Services</span>.</li>
    <li><b>Dados</b>: acesso majoritário via Query Builder (<span class="mono">DB::table</span>) com tolerância a schema variável usando <span class="mono">Schema::hasTable/hasColumn</span>.</li>
  </ul>

  <h3>Multi-tenant por CNPJ</h3>
  <div class="box">
    <ul>
      <li>No login, o CNPJ é normalizado e validado (14 dígitos) e o sistema tenta conectar no banco do tenant.</li>
      <li>Banco do tenant: <span class="mono">tenant_prefix + cnpj_digits</span> (padrão: <span class="mono">mp</span>).</li>
      <li>Usuário do tenant: <span class="mono">tenant_prefix + cnpj_digits</span>.</li>
      <li>Após autenticação, <span class="mono">tenant_db</span> e <span class="mono">tenant_user</span> ficam na sessão e a conexão default do Laravel passa a ser a conexão do tenant.</li>
    </ul>
  </div>

  <h2>Modelo de dados</h2>
  <h3>Entidades e tabelas (alto nível)</h3>
  <ul>
    <li><b>Usuários</b>: <span class="mono">usuario</span> (login via campo <span class="mono">usuario</span>, senha em <span class="mono">senha</span>, perfil).</li>
    <li><b>Cadastros</b>: <span class="mono">fornecedor</span>, <span class="mono">raca</span>, <span class="mono">tipo_racao</span>, <span class="mono">racao</span>.</li>
    <li><b>Causas</b>: <span class="mono">grupo_causa</span> e <span class="mono">causa</span> (situação ativa/inativa).</li>
    <li><b>Plantel</b>: <span class="mono">femea</span>, <span class="mono">macho</span>, <span class="mono">femea_movimento</span>, <span class="mono">macho_movimento</span>.</li>
    <li><b>Gestação</b>: <span class="mono">gestacao_cobertura</span>, <span class="mono">gestacao_perda</span>, <span class="mono">gestacao_cio</span>, <span class="mono">gestacao_salta_cio</span>.</li>
    <li><b>Maternidade</b>: <span class="mono">maternidade_parto</span>, <span class="mono">maternidade_desmame</span>, <span class="mono">maternidade_adocao</span> (e tabelas auxiliares quando presentes).</li>
    <li><b>Creche</b>: <span class="mono">creche_lotes</span>, <span class="mono">creche_compras</span> (e <span class="mono">creche_mortes</span> quando existe no tenant).</li>
    <li><b>Parâmetros</b>: <span class="mono">meta</span> (chave/valor) e <span class="mono">criterio_log</span> (logs).</li>
  </ul>

  <h2>Módulos e funcionalidades</h2>
  <ul>
    <li><b>Autenticação</b>: login com CNPJ (seleção do tenant), usuário e senha.</li>
    <li><b>Dashboard</b>: indicadores e inconsistências (ex.: cio previsto sem registro, parto atrasado, matriz vazia prolongada).</li>
    <li><b>Plantel</b>: cadastro de fêmeas/machos; registro de compra/morte/descarte/venda; ficha de matriz (JSON e PDF).</li>
    <li><b>Gestação</b>: coberturas, cios, salta cio e perdas; previsões de ciclo via serviço de calendário.</li>
    <li><b>Maternidade</b>: partos, desmames, adoções e controles associados (conforme tabelas disponíveis no tenant).</li>
    <li><b>Creche</b>: lotes, entradas por compra, saídas por mortalidade, saldo e métricas por lote.</li>
    <li><b>Admin/Cadastros</b>: usuários, fornecedores, rações, causas, metas/critérios, logs e utilitários.</li>
  </ul>

  <h2>Características técnicas — pontos fortes e aspectos notáveis</h2>
  <ul>
    <li><b>Isolamento por tenant</b> no nível de banco e usuário de banco (aplicação dinâmica da conexão do Laravel).</li>
    <li><b>Resiliência a schema</b>: verificações de tabelas/colunas antes de consultar (evita quebra em bases desatualizadas).</li>
    <li><b>Calendário “1000 dias” (Dia PIG)</b> opcional, com conversões e parsing de filtros.</li>
    <li><b>PDF</b>: geração via DomPDF para ficha e formulários operacionais.</li>
    <li><b>Permissões</b>: middleware de admin pode ser “enforçado” por configuração.</li>
  </ul>

  <h2>Organização do código — Controllers, Models, Views, Rotas</h2>
  <ul>
    <li><b>Controllers</b>: <span class="mono">app/Http/Controllers</span>.</li>
    <li><b>Models</b>: <span class="mono">app/Models</span> (Eloquent), mas com uso frequente de <span class="mono">DB::table</span>.</li>
    <li><b>Views</b>: <span class="mono">resources/views</span> (layouts, módulos e área admin).</li>
    <li><b>Rotas</b>: <span class="mono">routes/web.php</span> e <span class="mono">routes/auth.php</span> (inclui endpoints <span class="mono">/api/...</span> usados pelo frontend).</li>
    <li><b>Evolução do schema</b>: scripts por tenant em <span class="mono">banco_versions</span> e migrations pontuais em <span class="mono">database/migrations</span>.</li>
  </ul>

  <h2>Fluxo produtivo</h2>
  <ul>
    <li><b>Entrada</b>: login com CNPJ seleciona o tenant; autenticação consulta a tabela <span class="mono">usuario</span> do tenant.</li>
    <li><b>Operação</b>: registro e acompanhamento em Plantel → Gestação → Maternidade → Creche.</li>
    <li><b>Gestão</b>: parametrização por <span class="mono">meta</span> (metas/critérios) e logs em <span class="mono">criterio_log</span>.</li>
  </ul>

</body>
</html>
HTML;

        $filename = 'masterpig-documentacao-'.now()->format('Ymd-Hi').'.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream($filename)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('documentacao.pdf');

    Route::group([
        'prefix' => '/usuarios',
        'as' => 'usuarios.',
        'middleware' => [
            static function (\Illuminate\Http\Request $request, \Closure $next) {
                $auth = \Illuminate\Support\Facades\Auth::class;
                if (! $auth::check()) {
                    return redirect()->to(route('login', [], false));
                }
                $perfil = (string) ($auth::user()->perfil ?? '');
                if ($perfil !== \App\Services\PermissaoService::PERFIL_ADMIN) {
                    abort(403, 'Somente administradores podem gerenciar usuários e controle de acesso.');
                }
                return $next($request);
            },
        ],
    ], function (): void {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::patch('/{user}', [UsuarioController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsuarioController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/permissoes', [UsuarioController::class, 'savePermissoes'])->name('permissoes.update');
    });

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
    Route::get('/relatorios/plantel/femeas/filtro', [PlantelRelatorioController::class, 'femeasFilter'])->name('relatorios.plantel.femeas.filter');
    Route::get('/relatorios/plantel/femeas', [PlantelRelatorioController::class, 'femeas'])->name('relatorios.plantel.femeas');
    Route::get('/relatorios/plantel/machos/filtro', [PlantelRelatorioController::class, 'machosFilter'])->name('relatorios.plantel.machos.filter');
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

Route::middleware('auth')->get('/api/terminacao/lotes', function () {
    if (!Schema::hasTable('terminacao_lotes')) return response()->json([]);
    $situacao = request('situacao', 'aberto');
    $rows = \Illuminate\Support\Facades\DB::table('terminacao_lotes')
        ->when($situacao !== 'todos', fn ($q) => $q->where('situacao', $situacao))
        ->orderBy('nome')
        ->get(['id', 'nome', 'situacao', 'galpao', 'localizacao', 'data_entrada'])
        ->map(fn ($r) => ['id' => (int)$r->id, 'nome' => (string)$r->nome, 'situacao' => (string)$r->situacao, 'galpao' => (string)($r->galpao ?? ''), 'localizacao' => (string)($r->localizacao ?? ''), 'data_entrada' => $r->data_entrada ? \App\Services\PigCycleService::formatDisplayDate(\Illuminate\Support\Carbon::parse($r->data_entrada)) : '']);
    return response()->json($rows);
});

Route::middleware('auth')->get('/api/terminacao/lotes/{id}', function (int $id) {
    if (!Schema::hasTable('terminacao_lotes')) return response()->json(['error' => 'Tabela inexistente'], 404);
    $lote = \Illuminate\Support\Facades\DB::table('terminacao_lotes')->where('id', $id)->first();
    if (!$lote) return response()->json(['error' => 'Lote não encontrado'], 404);
    $saldo = \App\Services\TerminacaoService::calcularSaldoLote($id);
    $ultPeso = \App\Services\TerminacaoService::ultimaPesagem($id);
    return response()->json([
        'lote' => ['id' => (int)$lote->id, 'nome' => $lote->nome, 'situacao' => $lote->situacao, 'galpao' => $lote->galpao, 'localizacao' => $lote->localizacao],
        'saldo' => $saldo,
        'ultima_pesagem' => $ultPeso ? ['data' => $ultPeso['data_pesagem'] ? \App\Services\PigCycleService::formatDisplayDate($ultPeso['data_pesagem']) : null, 'peso_medio_kg' => $ultPeso['peso_medio_kg']] : null,
    ]);
})->whereNumber('id');

Route::middleware('auth')->get('/api/terminacao/inconsistencias', function () {
    return response()->json(\App\Services\TerminacaoService::buildInconsistencias());
});

Route::middleware('auth')->get('/api/terminacao/stats', function () {
    $stats = ['lotes_abertos' => 0, 'estoque' => 0, 'mortalidade_pct' => 0.0, 'vendidos_30d' => 0];
    if (Schema::hasTable('terminacao_lotes')) {
        $stats['lotes_abertos'] = (int) \Illuminate\Support\Facades\DB::table('terminacao_lotes')->where('situacao', 'aberto')->count();
        $ids = \Illuminate\Support\Facades\DB::table('terminacao_lotes')->where('situacao', 'aberto')->pluck('id')->all();

        $entradasPorLote = [];
        $mortesPorLote = [];
        $transfInPorLote = [];
        $transfOutPorLote = [];
        $vendasPorLote = [];

        if (!empty($ids)) {
            if (Schema::hasTable('terminacao_entradas')) {
                $rows = \Illuminate\Support\Facades\DB::table('terminacao_entradas')
                    ->selectRaw('lote_id, COALESCE(SUM(quantidade), 0) as total')
                    ->whereIn('lote_id', $ids)
                    ->groupBy('lote_id')
                    ->get();
                foreach ($rows as $r) { $entradasPorLote[(int)$r->lote_id] = (int)$r->total; }
            }

            if (Schema::hasTable('terminacao_mortes')) {
                $rows = \Illuminate\Support\Facades\DB::table('terminacao_mortes')
                    ->selectRaw('lote_id, COALESCE(SUM(quantidade), 0) as total')
                    ->whereIn('lote_id', $ids)
                    ->groupBy('lote_id')
                    ->get();
                foreach ($rows as $r) { $mortesPorLote[(int)$r->lote_id] = (int)$r->total; }
            }

            if (Schema::hasTable('terminacao_transferencias')) {
                $rowsOut = \Illuminate\Support\Facades\DB::table('terminacao_transferencias')
                    ->selectRaw('lote_origem_id as lote_id, COALESCE(SUM(quantidade), 0) as total')
                    ->whereIn('lote_origem_id', $ids)
                    ->groupBy('lote_origem_id')
                    ->get();
                foreach ($rowsOut as $r) { $transfOutPorLote[(int)$r->lote_id] = (int)$r->total; }

                $rowsIn = \Illuminate\Support\Facades\DB::table('terminacao_transferencias')
                    ->selectRaw('lote_destino_id as lote_id, COALESCE(SUM(quantidade), 0) as total')
                    ->whereIn('lote_destino_id', $ids)
                    ->groupBy('lote_destino_id')
                    ->get();
                foreach ($rowsIn as $r) { $transfInPorLote[(int)$r->lote_id] = (int)$r->total; }
            }

            if (Schema::hasTable('terminacao_vendas')) {
                $rows = \Illuminate\Support\Facades\DB::table('terminacao_vendas')
                    ->selectRaw('lote_id, COALESCE(SUM(quantidade), 0) as total')
                    ->whereIn('lote_id', $ids)
                    ->groupBy('lote_id')
                    ->get();
                foreach ($rows as $r) { $vendasPorLote[(int)$r->lote_id] = (int)$r->total; }
            }
        }

        $totalEnt = 0; $totalMort = 0; $estoque = 0;
        foreach ($ids as $id) {
            $idInt = (int)$id;
            $e = $entradasPorLote[$idInt] ?? 0;
            $m = $mortesPorLote[$idInt] ?? 0;
            $ti = $transfInPorLote[$idInt] ?? 0;
            $to = $transfOutPorLote[$idInt] ?? 0;
            $v = $vendasPorLote[$idInt] ?? 0;
            $totalEnt += $e;
            $totalMort += $m;
            $saldo = max(0, ($e + $ti) - ($m + $to + $v));
            $estoque += $saldo;
        }
        $stats['estoque'] = $estoque;
        $stats['mortalidade_pct'] = $totalEnt > 0 ? round(($totalMort / $totalEnt) * 100, 2) : 0.0;
        if (Schema::hasTable('terminacao_vendas')) {
            $stats['vendidos_30d'] = (int) \Illuminate\Support\Facades\DB::table('terminacao_vendas')
                ->where('data_venda', '>=', now()->subDays(30)->toDateString())
                ->sum('quantidade');
        }
    }
    return response()->json($stats);
});

require __DIR__.'/auth.php';
