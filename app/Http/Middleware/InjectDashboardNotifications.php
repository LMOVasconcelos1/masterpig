<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injeta as variáveis $notificacoes e $notificacoesCount no layout
 * 'layouts.dashboard'.
 *
 * ⚠️  ESTRATÉGIA (como existia ANTES / garantia não quebrar login):
 *     Este middleware SÓ é executado NO FINAL do pipeline WEB:
 *       EnsureLocalSessionCookie → (...) → EnsureTenantSelected →
 *       ApplyUserSchema → InjectDashboardNotifications
 *
 *     Ou seja: quando chegamos aqui, a conexão DEFAULT JÁ É 'tenant',
 *     a sessão JÁ TEM tenant_db + tenant_user, e o login JÁ FOI
 *     validado. Nunca roda durante o processo de login.
 *
 * 🔥 OTIMIZAÇÕES PERFORMANCE (v2):
 *     - whereNotExists → whereNotIn (reduz de O(N×M) → O(N+M))
 *     - Timeout global e circuit breakers a cada bloco (previne FatalError)
 *     - CountAtivas unificado em 1 query GROUP BY (não 2 separadas)
 *     - Heavy work SÓ rota /dashboard (evita custo em todas as páginas)
 */
class InjectDashboardNotifications
{
    private const MAX_SEGUNDOS = 20;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $empty = [
            'notificacoes' => [],
            'notificacoesCount' => 0,
        ];

        // ⏱️ Timeout global — previne FatalError "Maximum execution time"
        $startMs = microtime(true);
        if (function_exists('set_time_limit')) {
            $limiteAtual = (int) ini_get('max_execution_time');
            if ($limiteAtual > 0 && $limiteAtual < 300) {
                @set_time_limit(300);
            }
        }
        @ini_set('max_execution_time', '300');
        $timeout = static function () use ($startMs): bool {
            return (microtime(true) - $startMs) > self::MAX_SEGUNDOS;
        };

        try {
            // Proteção 0: 🔥 SÓ roda queries PESADAS na rota /dashboard.
            // Demais páginas (relatórios, cadastros etc.) não precisam destes
            // cálculos — evita 50+ queries DESNECESSÁRIAS em todo clique.
            $route = Route::current();
            $isDashboard = false;
            if ($route !== null) {
                $routeName = (string) ($route->getName() ?? '');
                $routeUri  = (string) ($route->uri ?? '');
                $isDashboard = $routeName === 'dashboard' || str_ends_with($routeUri, '/dashboard');
            }
            if (! $isDashboard) {
                $uri = (string) ($request->server->get('REQUEST_URI') ?? '');
                $isDashboard = str_starts_with($uri, '/dashboard') || $uri === '/dashboard';
            }
            if (! $isDashboard) {
                View::share($empty);
                return $response;
            }

            // Proteção 1: só injeta em requests HTML (não API / JSON)
            $accept = (string) ($request->headers->get('Accept') ?? '');
            if ($accept !== '' && ! str_contains($accept, 'text/html')) {
                return $response;
            }

            // Proteção 2: blacklist URI (login/logout etc. — redundante pois
            // EnsureTenantSelected já filtra, mas mantém seguro)
            $uri = (string) ($request->server->get('REQUEST_URI') ?? '');
            $blacklist = ['/login','/logout','password/','sanctum','/_debugbar','/livewire','/api/','broadcasting','/up'];
            foreach ($blacklist as $needle) {
                if (stripos($uri, $needle) !== false) {
                    View::share($empty);
                    return $response;
                }
            }

            // Proteção 3: sessão DEVE ter tenant_db + tenant_user
            try {
                $sess = $request->hasSession() ? $request->session() : null;
                if ($sess !== null) {
                    $tenantDb   = $sess->get('tenant_db');
                    $tenantUser = $sess->get('tenant_user');
                    if (! is_string($tenantDb) || $tenantDb === ''
                        || ! is_string($tenantUser) || $tenantUser === '') {
                        View::share($empty);
                        return $response;
                    }
                } else {
                    View::share($empty);
                    return $response;
                }
            } catch (\Throwable) {
                View::share($empty);
                return $response;
            }

            // Proteção 4: conexão DEFAULT DEVE ser 'tenant'
            try {
                $conn = (string) config('database.default');
                $tenantConn = \App\Support\TenantDatabase::tenantConnectionName();
                if ($conn !== $tenantConn) {
                    View::share($empty);
                    return $response;
                }
            } catch (\Throwable) {
                View::share($empty);
                return $response;
            }
        } catch (\Throwable) {
            View::share($empty);
            return $response;
        }

        // =====================
        // CÓDIGO REAL
        // =====================
        try {
            $notificacoes = [];

            if ($timeout()) { View::share($empty); return $response; }

            if (! Auth::check()) {
                View::share($empty);
                return $response;
            }

            if (! Schema::hasTable('meta')) {
                View::share($empty);
                return $response;
            }

            if ($timeout()) { View::share($empty); return $response; }

            $metas = DB::table('meta')
                ->whereIn('chave', [
                    'meta_plantel_estoque_leitoas',
                    'meta_plantel_estoque_matrizes',
                    'meta_entrada_peso_leitoa',
                    'meta_entrada_peso_matriz',
                    'meta_entrada_peso_macho',
                ])
                ->pluck('valor', 'chave');

            $metaLeitoas = isset($metas['meta_plantel_estoque_leitoas']) ? (float) $metas['meta_plantel_estoque_leitoas'] : null;
            $metaMatrizes = isset($metas['meta_plantel_estoque_matrizes']) ? (float) $metas['meta_plantel_estoque_matrizes'] : null;
            $metaEntradaPesoLeitoa = isset($metas['meta_entrada_peso_leitoa']) ? (float) $metas['meta_entrada_peso_leitoa'] : null;
            $metaEntradaPesoMatriz = isset($metas['meta_entrada_peso_matriz']) ? (float) $metas['meta_entrada_peso_matriz'] : null;
            $metaEntradaPesoMacho = isset($metas['meta_entrada_peso_macho']) ? (float) $metas['meta_entrada_peso_macho'] : null;

            if (! Schema::hasTable('femea')) {
                View::share($empty);
                return $response;
            }

            if ($timeout()) { View::share($empty); return $response; }

            // 🔥 OTIMIZAÇÃO countAtivas:
            // whereNotExists (subquery correlacionada = O(N×M))
            //   → 2 queries: ids_saida + WHERE NOT IN + GROUP BY (O(N+M))
            //   + 1 query única para LEITOA + MATRIZ_VAZIA + MATRIZ_GESTANTE
            $leitoasAtivas = 0;
            $matrizesAtivas = 0;
            $hasMov = Schema::hasTable('femea_movimento');
            try {
                $idsSaida = [];
                if ($hasMov) {
                    $idsSaida = DB::table('femea_movimento')
                        ->whereIn('acao', ['morte', 'descarte', 'venda'])
                        ->distinct()
                        ->pluck('femea_id')
                        ->all();
                }

                if ($timeout()) { View::share($empty); return $response; }

                $stats = DB::table('femea')
                    ->when(! empty($idsSaida), static function ($q) use ($idsSaida) {
                        $q->whereNotIn('id', $idsSaida);
                    })
                    ->whereIn('tipo_compra', ['leitoa', 'matriz_vazia', 'matriz_gestante'])
                    ->select('tipo_compra', DB::raw('COUNT(*) as total'))
                    ->groupBy('tipo_compra')
                    ->pluck('total', 'tipo_compra');

                $leitoasAtivas = (int) ($stats['leitoa'] ?? 0);
                $matrizesAtivas = (int) ($stats['matriz_vazia'] ?? 0) + (int) ($stats['matriz_gestante'] ?? 0);
            } catch (\Throwable) {
                $leitoasAtivas = 0;
                $matrizesAtivas = 0;
            }

            if ($metaLeitoas !== null && $metaLeitoas > 0 && $leitoasAtivas < $metaLeitoas) {
                $notificacoes[] = [
                    'titulo' => 'Estoque de leitoas abaixo da meta',
                    'descricao' => 'Atual: '.$leitoasAtivas.' | Meta: '.(int) $metaLeitoas,
                    'tipo' => 'alerta',
                ];
            }

            if ($metaMatrizes !== null && $metaMatrizes > 0 && $matrizesAtivas < $metaMatrizes) {
                $notificacoes[] = [
                    'titulo' => 'Estoque de matrizes abaixo da meta',
                    'descricao' => 'Atual: '.$matrizesAtivas.' | Meta: '.(int) $metaMatrizes,
                    'tipo' => 'alerta',
                ];
            }

            if ($timeout()) { View::share($notificacoes + $empty); return $response; }

            $inicio = Carbon::now()->subDays(30)->toDateString();

            if (
                Schema::hasTable('femea_movimento')
                && Schema::hasColumn('femea_movimento', 'acao')
                && Schema::hasColumn('femea_movimento', 'data')
                && Schema::hasColumn('femea', 'peso_compra')
            ) {
                $entriesBase = DB::table('femea_movimento as fm')
                    ->join('femea as f', 'f.id', '=', 'fm.femea_id')
                    ->where('fm.acao', 'compra')
                    ->where('fm.data', '>=', $inicio);

                if ($metaEntradaPesoLeitoa !== null && $metaEntradaPesoLeitoa > 0) {
                    if ($timeout()) { View::share($notificacoes + $empty); return $response; }
                    try {
                        $base = (clone $entriesBase)->where('f.tipo_compra', 'leitoa');
                        $total = (int) $base->count();
                        if ($total > 0) {
                            $q = (clone $entriesBase)->where('f.tipo_compra', 'leitoa')->whereNotNull('f.peso_compra');
                            $count = (int) $q->count();
                            if ($count === 0) {
                                $notificacoes[] = [
                                    'titulo' => 'Entrada de leitoas sem peso informado',
                                    'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoLeitoa, 2, ',', '.').' kg',
                                    'tipo' => 'alerta',
                                ];
                            } else {
                                $avg = (float) $q->avg('f.peso_compra');
                                if ($avg < $metaEntradaPesoLeitoa) {
                                    $missing = $total > $count ? ' | Sem peso: '.($total - $count) : '';
                                    $notificacoes[] = [
                                        'titulo' => 'Entrada de leitoas abaixo da meta de peso',
                                        'descricao' => 'Média 30d: '.number_format($avg, 2, ',', '.').' kg | Meta: '.number_format($metaEntradaPesoLeitoa, 2, ',', '.').' kg | Entradas: '.$count.$missing,
                                        'tipo' => 'alerta',
                                    ];
                                }
                            }
                        }
                    } catch (\Throwable) {}
                }

                if ($timeout()) { View::share($notificacoes + $empty); return $response; }

                if ($metaEntradaPesoMatriz !== null && $metaEntradaPesoMatriz > 0) {
                    try {
                        $base = (clone $entriesBase)->whereIn('f.tipo_compra', ['matriz_vazia', 'matriz_gestante']);
                        $total = (int) $base->count();
                        if ($total > 0) {
                            $q = (clone $entriesBase)->whereIn('f.tipo_compra', ['matriz_vazia', 'matriz_gestante'])->whereNotNull('f.peso_compra');
                            $count = (int) $q->count();
                            if ($count === 0) {
                                $notificacoes[] = [
                                    'titulo' => 'Entrada de matrizes sem peso informado',
                                    'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoMatriz, 2, ',', '.').' kg',
                                    'tipo' => 'alerta',
                                ];
                            } else {
                                $avg = (float) $q->avg('f.peso_compra');
                                if ($avg < $metaEntradaPesoMatriz) {
                                    $missing = $total > $count ? ' | Sem peso: '.($total - $count) : '';
                                    $notificacoes[] = [
                                        'titulo' => 'Entrada de matrizes abaixo da meta de peso',
                                        'descricao' => 'Média 30d: '.number_format($avg, 2, ',', '.').' kg | Meta: '.number_format($metaEntradaPesoMatriz, 2, ',', '.').' kg | Entradas: '.$count.$missing,
                                        'tipo' => 'alerta',
                                    ];
                                }
                            }
                        }
                    } catch (\Throwable) {}
                }
            }

            if ($timeout()) { View::share($notificacoes + $empty); return $response; }

            if (
                Schema::hasTable('macho')
                && Schema::hasTable('macho_movimento')
                && Schema::hasColumn('macho_movimento', 'acao')
                && Schema::hasColumn('macho_movimento', 'data')
                && Schema::hasColumn('macho', 'peso_compra')
                && $metaEntradaPesoMacho !== null
                && $metaEntradaPesoMacho > 0
            ) {
                try {
                    $idsMachoSaida = DB::table('macho_movimento')
                        ->whereIn('acao', ['morte', 'descarte', 'venda'])
                        ->distinct()
                        ->pluck('macho_id')
                        ->all();

                    $base = DB::table('macho_movimento as mm')
                        ->join('macho as m', 'm.id', '=', 'mm.macho_id')
                        ->when(! empty($idsMachoSaida), static function ($q) use ($idsMachoSaida) {
                            $q->whereNotIn('m.id', $idsMachoSaida);
                        })
                        ->where('mm.acao', 'compra')
                        ->where('mm.data', '>=', $inicio);

                    $total = (int) (clone $base)->count();
                    if ($total > 0) {
                        $q = (clone $base)->whereNotNull('m.peso_compra');
                        $count = (int) $q->count();
                        if ($count === 0) {
                            $notificacoes[] = [
                                'titulo' => 'Entrada de machos sem peso informado',
                                'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoMacho, 2, ',', '.').' kg',
                                'tipo' => 'alerta',
                            ];
                        } else {
                            $avg = (float) $q->avg('m.peso_compra');
                            if ($avg < $metaEntradaPesoMacho) {
                                $missing = $total > $count ? ' | Sem peso: '.($total - $count) : '';
                                $notificacoes[] = [
                                    'titulo' => 'Entrada de machos abaixo da meta de peso',
                                    'descricao' => 'Média 30d: '.number_format($avg, 2, ',', '.').' kg | Meta: '.number_format($metaEntradaPesoMacho, 2, ',', '.').' kg | Entradas: '.$count.$missing,
                                    'tipo' => 'alerta',
                                ];
                            }
                        }
                    }
                } catch (\Throwable) {}
            }

            View::share([
                'notificacoes' => $notificacoes,
                'notificacoesCount' => count($notificacoes),
            ]);
        } catch (\Throwable) {
            View::share($empty);
        }

        return $response;
    }
}
