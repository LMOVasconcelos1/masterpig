<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatbotController extends Controller
{
    public function page()
    {
        abort_unless((bool) config('masterpig.chatbot_enabled', false), 404);

        return view('admin.chatbot.index');
    }

    public function ask(Request $request)
    {
        abort_unless((bool) config('masterpig.chatbot_enabled', false), 404);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $raw = trim((string) $validated['message']);
        $text = $this->normalize($raw);

        if ($text === '') {
            return response()->json([
                'answer' => 'Envie uma pergunta.',
            ], 422);
        }

        try {
            $answer = $this->handleQuestion($text);
        } catch (\Throwable $e) {
            return response()->json([
                'answer' => 'Não consegui consultar o banco para essa pergunta.',
            ], 500);
        }

        return response()->json([
            'answer' => $answer,
        ]);
    }

    private function handleQuestion(string $text): string
    {
        $range = $this->parseDateRange($text);

        $isFemea = str_contains($text, 'femea') || str_contains($text, 'femeas') || str_contains($text, 'leitoa') || str_contains($text, 'matriz');
        $isMacho = str_contains($text, 'macho') || str_contains($text, 'machos');

        if (str_contains($text, 'quantos') || str_contains($text, 'quantas')) {
            if (str_contains($text, 'morte') || str_contains($text, 'mortes') || str_contains($text, 'mortalidade')) {
                return $this->countMovimentos('morte', $isFemea, $isMacho, $range);
            }

            if (str_contains($text, 'descarte') || str_contains($text, 'descartes')) {
                return $this->countMovimentos('descarte', $isFemea, $isMacho, $range);
            }

            if (str_contains($text, 'venda') || str_contains($text, 'vendas')) {
                return $this->countMovimentos('venda', $isFemea, $isMacho, $range);
            }

            if (str_contains($text, 'compra') || str_contains($text, 'compras')) {
                return $this->countMovimentos('compra', $isFemea, $isMacho, $range);
            }

            if ($isFemea || $isMacho) {
                return $this->countAnimais($text);
            }
        }

        return 'Posso responder, por enquanto:\n'
            ."- Quantas fêmeas ativas?\n"
            ."- Quantos machos ativos?\n"
            ."- Quantas leitoas ativas?\n"
            ."- Quantas matrizes ativas?\n"
            ."- Quantas mortes/descartes/vendas/compras no mês/últimos 30 dias?\n";
    }

    private function countAnimais(string $text): string
    {
        $wantAtivos = str_contains($text, 'ativo');
        $wantTodas = str_contains($text, 'todas') || str_contains($text, 'todos');
        $includeMovidos = $wantTodas || ! $wantAtivos ? true : false;

        $filterTipo = null;
        if (str_contains($text, 'leitoa')) {
            $filterTipo = ['leitoa'];
        }
        if (str_contains($text, 'matriz')) {
            $filterTipo = ['matriz_vazia', 'matriz_gestante'];
        }

        $counts = [];

        if ((str_contains($text, 'femea') || str_contains($text, 'femeas') || $filterTipo) && Schema::hasTable('femea')) {
            $q = DB::table('femea');
            if ($filterTipo) {
                $q->whereIn('tipo_compra', $filterTipo);
            }

            if (! $includeMovidos && Schema::hasTable('femea_movimento')) {
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('femea_movimento as fm')
                        ->whereColumn('fm.femea_id', 'femea.id')
                        ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
                });
            }

            $counts[] = ['label' => 'Fêmeas', 'value' => (int) $q->count()];
        }

        if (str_contains($text, 'macho') || str_contains($text, 'machos')) {
            if (Schema::hasTable('macho')) {
                $q = DB::table('macho');
                if (! $includeMovidos && Schema::hasTable('macho_movimento')) {
                    $q->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('macho_movimento as mm')
                            ->whereColumn('mm.macho_id', 'macho.id')
                            ->whereIn('mm.acao', ['morte', 'descarte', 'venda']);
                    });
                }

                $counts[] = ['label' => 'Machos', 'value' => (int) $q->count()];
            }
        }

        if (empty($counts)) {
            return 'Não encontrei tabelas de plantel para consultar.';
        }

        $suffix = $wantAtivos ? ' ativos' : '';
        $parts = array_map(fn ($row) => $row['label'].$suffix.': '.$row['value'], $counts);

        return implode("\n", $parts);
    }

    private function countMovimentos(string $acao, bool $isFemea, bool $isMacho, ?array $range): string
    {
        $acao = mb_strtolower($acao);
        $parts = [];

        if (($isFemea || (! $isFemea && ! $isMacho)) && Schema::hasTable('femea_movimento')) {
            $q = DB::table('femea_movimento')->where('acao', $acao);
            if ($range) {
                $q->whereBetween('data', [$range['start'], $range['end']]);
            }
            $parts[] = 'Fêmeas: '.(int) $q->count();
        }

        if (($isMacho || (! $isFemea && ! $isMacho)) && Schema::hasTable('macho_movimento')) {
            $q = DB::table('macho_movimento')->where('acao', $acao);
            if ($range) {
                $q->whereBetween('data', [$range['start'], $range['end']]);
            }
            $parts[] = 'Machos: '.(int) $q->count();
        }

        if (empty($parts)) {
            return 'Não encontrei tabelas de movimentos para consultar.';
        }

        $period = $range ? (' ('.date('d/m/Y', strtotime($range['start'])).' a '.date('d/m/Y', strtotime($range['end'])).')') : '';

        return ucfirst($acao).$period.":\n".implode("\n", $parts);
    }

    private function parseDateRange(string $text): ?array
    {
        $today = now()->startOfDay();

        if (preg_match('/ultimos\\s+(\\d+)\\s+dias/', $text, $m)) {
            $days = max(1, (int) $m[1]);
            $start = $today->copy()->subDays($days - 1);
            $end = $today->copy()->endOfDay();

            return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
        }

        if (str_contains($text, 'este mes') || str_contains($text, 'esse mes')) {
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->endOfMonth();

            return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
        }

        if (str_contains($text, 'mes passado')) {
            $start = $today->copy()->subMonthNoOverflow()->startOfMonth();
            $end = $today->copy()->subMonthNoOverflow()->endOfMonth();

            return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        } elseif (function_exists('iconv')) {
            $conv = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($conv)) {
                $text = $conv;
            }
        }

        $text = preg_replace('/[^a-z0-9\\s\\?]/', ' ', $text) ?? $text;
        $text = preg_replace('/\\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
