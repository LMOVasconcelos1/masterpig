<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
 */
class InjectDashboardNotifications
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $empty = [
            'notificacoes' => [],
            'notificacoesCount' => 0,
        ];

        try {
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

            if (! Auth::check()) {
                View::share($empty);
                return $response;
            }

            if (! Schema::hasTable('meta')) {
                View::share($empty);
                return $response;
            }

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

            $hasMov = Schema::hasTable('femea_movimento');

            $countAtivas = function (array $tipos) use ($hasMov) {
                $query = DB::table('femea')->whereIn('tipo_compra', $tipos);
                if ($hasMov) {
                    $query->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('femea_movimento as fm')
                            ->whereColumn('fm.femea_id', 'femea.id')
                            ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
                    });
                }

                return (int) $query->count();
            };

            $leitoasAtivas = $countAtivas(['leitoa']);
            $matrizesAtivas = $countAtivas(['matriz_vazia', 'matriz_gestante']);

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

            $inicio = Carbon::now()->subDays(30)->toDateString();

            if (
                Schema::hasTable('femea_movimento')
                && Schema::hasColumn('femea_movimento', 'acao')
                && Schema::hasColumn('femea_movimento', 'data')
                && Schema::hasColumn('femea', 'peso_compra')
            ) {
                $entriesQuery = DB::table('femea_movimento as fm')
                    ->join('femea as f', 'f.id', '=', 'fm.femea_id')
                    ->where('fm.acao', 'compra')
                    ->where('fm.data', '>=', $inicio);

                if ($metaEntradaPesoLeitoa !== null && $metaEntradaPesoLeitoa > 0) {
                    $base = (clone $entriesQuery)->where('f.tipo_compra', 'leitoa');
                    $total = (int) $base->count();
                    $q = (clone $entriesQuery)->where('f.tipo_compra', 'leitoa')->whereNotNull('f.peso_compra');
                    $count = (int) $q->count();

                    if ($total > 0 && $count === 0) {
                        $notificacoes[] = [
                            'titulo' => 'Entrada de leitoas sem peso informado',
                            'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoLeitoa, 2, ',', '.').' kg',
                            'tipo' => 'alerta',
                        ];
                    } elseif ($count > 0) {
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

                if ($metaEntradaPesoMatriz !== null && $metaEntradaPesoMatriz > 0) {
                    $base = (clone $entriesQuery)->whereIn('f.tipo_compra', ['matriz_vazia', 'matriz_gestante']);
                    $total = (int) $base->count();
                    $q = (clone $entriesQuery)->whereIn('f.tipo_compra', ['matriz_vazia', 'matriz_gestante'])->whereNotNull('f.peso_compra');
                    $count = (int) $q->count();

                    if ($total > 0 && $count === 0) {
                        $notificacoes[] = [
                            'titulo' => 'Entrada de matrizes sem peso informado',
                            'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoMatriz, 2, ',', '.').' kg',
                            'tipo' => 'alerta',
                        ];
                    } elseif ($count > 0) {
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
            }

            if (
                Schema::hasTable('macho')
                && Schema::hasTable('macho_movimento')
                && Schema::hasColumn('macho_movimento', 'acao')
                && Schema::hasColumn('macho_movimento', 'data')
                && Schema::hasColumn('macho', 'peso_compra')
                && $metaEntradaPesoMacho !== null
                && $metaEntradaPesoMacho > 0
            ) {
                $base = DB::table('macho_movimento as mm')
                    ->join('macho as m', 'm.id', '=', 'mm.macho_id')
                    ->where('mm.acao', 'compra')
                    ->where('mm.data', '>=', $inicio);

                $total = (int) (clone $base)->count();
                $q = (clone $base)->whereNotNull('m.peso_compra');
                $count = (int) $q->count();

                if ($total > 0 && $count === 0) {
                    $notificacoes[] = [
                        'titulo' => 'Entrada de machos sem peso informado',
                        'descricao' => 'Entradas 30d: '.$total.' | Meta: '.number_format($metaEntradaPesoMacho, 2, ',', '.').' kg',
                        'tipo' => 'alerta',
                    ];
                } elseif ($count > 0) {
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
