@extends('layouts.dashboard')

@section('title', 'Utilitários - Começar do zero')
@section('page_title', 'Começar do zero')

@section('content')
<div x-data="{
    saving: false,
    error: '',
    cnpj: '',
    confirmOpen: false,
    openConfirm() {
        this.error = '';
        this.cnpj = '';
        this.confirmOpen = true;
        this.$nextTick(() => {
            const el = document.getElementById('cnpj-confirm');
            if (el) el.focus();
        });
    },
    reset() {
        this.saving = true;
        this.error = '';

        const payload = { cnpj: String(this.cnpj || '') };

        fetch('{{ route('admin.zerar.store', [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    let msg = data?.message || 'Erro ao zerar o sistema.';
                    if (data?.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey) msg = data.errors[firstKey][0];
                    }
                    throw new Error(msg);
                }
                return data;
            })
            .then((data) => {
                this.confirmOpen = false;
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message || 'Sistema zerado com sucesso!', type: 'success' } }));
                setTimeout(() => window.location.href = '{{ route('dashboard', [], false) }}', 500);
            })
            .catch((e) => {
                this.error = e.message || 'Erro ao zerar o sistema.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.error, type: 'error' } }));
            })
            .finally(() => { this.saving = false; });
    },
}" class="space-y-6">
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/50 text-red-900 dark:text-red-400 rounded-xl px-4 py-3 text-sm">
        Atenção: essa ação remove todos os animais e lançamentos (gestação e movimentos), mantendo apenas os cadastros.
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
            <h6 class="font-bold text-primary-700 dark:text-primary-400 uppercase text-xs tracking-wider">Começar do zero</h6>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Para confirmar, será necessário digitar o CNPJ do banco atual.</div>
        </div>
        <div class="p-6 bg-white dark:bg-gray-900">
            <button type="button" @click="openConfirm()" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                Começar do zero
            </button>
        </div>
    </div>

    <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="confirmOpen" @click="!saving && (confirmOpen = false)" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="confirmOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-flex flex-col align-bottom bg-white dark:bg-gray-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100 dark:border-gray-800">
                <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-semibold text-gray-900 dark:text-gray-100">Confirmar CNPJ</h3>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Digite o CNPJ do banco atual para confirmar a operação.</div>
                        </div>
                        <button type="button" @click="!saving && (confirmOpen = false)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700" title="Fechar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ</label>
                        <input id="cnpj-confirm" type="text" x-model="cnpj" placeholder="00.000.000/0000-00" class="mt-1 w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 rounded-xl">
                        <div x-show="error" x-text="error" class="mt-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/50 text-amber-800 dark:text-amber-400 rounded-xl px-4 py-3 text-sm" x-cloak></div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-2">
                    <button type="button" @click="!saving && (confirmOpen = false)" :disabled="saving" class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-5 py-2.5 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="reset()" :disabled="saving" class="inline-flex items-center justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                        <template x-if="!saving"><span>Confirmar e apagar</span></template>
                        <template x-if="saving"><span>Processando...</span></template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

