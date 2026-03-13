<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $explicitAppUrl = env('MASTERPIG_APP_URL');
        $shouldForceAppUrl = ! app()->environment('local')
            || (is_string($explicitAppUrl) && trim($explicitAppUrl) !== '');

        if ($shouldForceAppUrl) {
            $appUrl = (string) (config('masterpig.app_url') ?: config('app.url'));
            URL::forceRootUrl($appUrl);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if (is_string($scheme) && $scheme !== '') {
                URL::forceScheme($scheme);
            }
        }

        View::composer('layouts.dashboard', function ($view) {
            $notificacoes = [];

            if (! Auth::check()) {
                $view->with([
                    'notificacoes' => $notificacoes,
                    'notificacoesCount' => 0,
                ]);

                return;
            }

            if (! Schema::hasTable('meta')) {
                $view->with([
                    'notificacoes' => $notificacoes,
                    'notificacoesCount' => 0,
                ]);

                return;
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
                $view->with([
                    'notificacoes' => $notificacoes,
                    'notificacoesCount' => 0,
                ]);

                return;
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

            $view->with([
                'notificacoes' => $notificacoes,
                'notificacoesCount' => count($notificacoes),
            ]);
        });
    }
}
