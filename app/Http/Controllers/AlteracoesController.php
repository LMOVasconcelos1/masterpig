<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

class AlteracoesController extends Controller
{
    public function index()
    {
        $path = base_path('resources/alteracoes.json');
        $entries = [];

        if (is_file($path)) {
            $raw = file_get_contents($path);
            $decoded = json_decode($raw ?: '[]', true);
            if (is_array($decoded)) {
                $entries = $decoded;
            }
        }

        $entries = collect($entries)
            ->filter(fn ($e) => is_array($e))
            ->map(function ($e) {
                $date = $e['date'] ?? null;
                $parsed = null;
                if (is_string($date) && trim($date) !== '') {
                    try {
                        $parsed = Carbon::parse($date)->toDateString();
                    } catch (\Throwable) {
                        $parsed = null;
                    }
                }

                return [
                    'date' => $parsed,
                    'title' => is_string($e['title'] ?? null) ? trim((string) $e['title']) : '',
                    'items' => is_array($e['items'] ?? null) ? $e['items'] : [],
                ];
            })
            ->filter(fn ($e) => $e['title'] !== '' || count($e['items']) > 0)
            ->sortByDesc(fn ($e) => $e['date'] ?? '0000-00-00')
            ->values()
            ->all();

        return view('admin.alteracoes.index', [
            'entries' => $entries,
        ]);
    }
}
