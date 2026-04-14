@extends('layouts.dashboard')

@section('title', 'Clientes')

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Clientes</h2>
            <p class="text-sm text-white">Cadastro e gestão de clientes</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Cadastro de Clientes</h6>
            <div class="text-sm text-gray-500 mt-1">Cadastro em desenvolvimento.</div>
        </div>
    </div>
    <div class="p-6">
        <div class="bg-amber-50 border border-amber-100 text-amber-900 rounded-xl px-4 py-3 text-sm">
            Tela em desenvolvimento.
        </div>
    </div>
</div>
@endsection

