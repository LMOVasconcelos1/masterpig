@extends('layouts.dashboard')

@section('title', 'Alterações')
@section('page_title', 'Alterações')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Registro de alterações</h6>
            <div class="text-sm text-gray-500 mt-1">Resumo das mudanças realizadas no sistema.</div>
        </div>
        <div class="p-6">
            @if(empty($entries))
                <div class="text-sm text-gray-500">Nenhuma alteração registrada.</div>
            @else
                <div class="space-y-4">
                    @foreach($entries as $entry)
                        <div class="rounded-2xl border border-gray-100 bg-white p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900">{{ $entry['title'] }}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $entry['date'] ? \Illuminate\Support\Carbon::parse($entry['date'])->format('d/m/Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                            @if(!empty($entry['items']))
                                <ul class="mt-3 space-y-2 text-sm text-gray-700 list-disc pl-5">
                                    @foreach($entry['items'] as $it)
                                        @if(is_string($it) && trim($it) !== '')
                                            <li>{{ $it }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

