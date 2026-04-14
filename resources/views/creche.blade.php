@extends('layouts.dashboard')

@section('title', 'Creche')
@section('page_title', '')

@section('content')
    <div x-data="{ tab: 'lancamentos' }" class="space-y-6">
        <!-- Header & Topbar -->
        <div>
            <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-white mb-2">Creche</h2>
                    <p class="text-sm text-white">Manejo de leitões em fase de creche</p>
                </div>
                <nav class="flex justify-center space-x-8 overflow-x-auto mt-6">
                    <button type="button" @click="tab = 'lancamentos'" 
                        :class="tab === 'lancamentos' ? 'border-primary-500 text-primary-600' : 'border-transparent text-white hover:text-amber-100 hover:border-gray-300'"
                        class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Lançamentos
                    </button>
                </nav>
            </div>
        </div>

        <div x-show="tab === 'lancamentos'" x-cloak class="space-y-8" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <div class="text-center">
                        <h6 class="font-bold text-primary-700 uppercase text-xs tracking-wider">Lançamentos</h6>
                        <div class="text-sm text-gray-500 mt-1">Manejo de leitões em fase de creche</div>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Banner de Desenvolvimento -->
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-exclamation-triangle text-amber-600 text-xl animate-pulse"></i>
                            <div>
                                <h3 class="font-bold text-amber-800">Tela em Desenvolvimento</h3>
                                <p class="text-sm text-amber-700 mt-1">
                                    Esta funcionalidade está sendo desenvolvida e estará disponível em breve.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Placeholder -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6">
                        <div class="px-6 py-12 text-center">
                            <i class="fa-solid fa-hammer text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">
                                Funcionalidade em Desenvolvimento
                            </h3>
                            <p class="text-gray-500 max-w-md mx-auto">
                                O sistema de manejo de creche está sendo implementado com as melhores práticas de gestão suína.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
