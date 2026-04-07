@extends('layouts.dashboard')

@section('title', 'Terminação')
@section('page_title', '')

@section('content')
    <div x-data="{ tab: 'lancamentos' }" class="space-y-6">
        <!-- Header & Topbar -->
        <div class="mb-6 -mx-3 sm:-mx-6 px-3 sm:px-6 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
            <div class="pt-4 pb-2">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Terminação</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manejo de suínos em fase de terminação</p>
            </div>
            <nav class="-mb-px flex space-x-8 overflow-x-auto">
                <button type="button" @click="tab = 'lancamentos'" 
                    :class="tab === 'lancamentos' ? 'border-transparent bg-gradient-to-r from-[#0A1128] to-[#C5A059] text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="whitespace-nowrap pb-3 px-4 border-b-2 font-medium text-sm transition-all duration-300 rounded-t-lg">
                    Lançamentos
                </button>
            </nav>
        </div>

        <div x-show="tab === 'lancamentos'" x-cloak class="space-y-8">
            <!-- Banner de Desenvolvimento -->
            <div class="bg-amber-50 dark:bg-amber-950/40 border-l-4 border-amber-400 p-4 rounded-r-lg">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-exclamation-triangle text-amber-600 dark:text-amber-400 text-xl animate-pulse"></i>
                    <div>
                        <h3 class="font-bold text-amber-800 dark:text-amber-300">Tela em Desenvolvimento</h3>
                        <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                            Esta funcionalidade está sendo desenvolvida e estará disponível em breve.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card Placeholder -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-12 text-center">
                    <i class="fa-solid fa-hammer text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">
                        Funcionalidade em Desenvolvimento
                    </h3>
                    <p class="text-gray-500 dark:text-gray-500 max-w-md mx-auto">
                        O sistema de manejo de terminação está sendo implementado com controle de peso, conversão alimentar e qualidade da carcaça.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
