<?php

namespace App\Providers;

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
        $appUrl = (string) (config('masterpig.app_url') ?: config('app.url'));
        URL::forceRootUrl($appUrl);
        $scheme = parse_url($appUrl, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            URL::forceScheme($scheme);
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
                ])
                ->pluck('valor', 'chave');

            $metaLeitoas = isset($metas['meta_plantel_estoque_leitoas']) ? (float) $metas['meta_plantel_estoque_leitoas'] : null;
            $metaMatrizes = isset($metas['meta_plantel_estoque_matrizes']) ? (float) $metas['meta_plantel_estoque_matrizes'] : null;

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

            $view->with([
                'notificacoes' => $notificacoes,
                'notificacoesCount' => count($notificacoes),
            ]);
        });
    }
}
