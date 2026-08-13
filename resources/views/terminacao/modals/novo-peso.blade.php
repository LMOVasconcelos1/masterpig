<x-modal name="modal-novo-peso" maxWidth="xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.pesos.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-orange-900 tracking-tight">Registrar Pesagem · Terminação</h3>
                <p class="text-xs text-gray-500 mt-1">Controle de peso médio, GPD e evolução do lote.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-novo-peso')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data da Pesagem *</label>
                <input type="hidden" name="data_pesagem" :value="pesoDataIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refPesoData"
                           :value="pesoData"
                           @input="pesoData = $event.target.value"
                           @focus="openDatePicker('peso_data', $refs)"
                           @click="openDatePicker('peso_data', $refs)"
                           @blur="normalizeDisplay('pesoDataIso', 'pesoData')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-orange-600"
                            @click="openDatePicker('peso_data', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote *</label>
                <select name="lote_id" required class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
                    <option value="">Selecione...</option>
                    @forelse($lotesCadastrados ?? [] as $l)
                        <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                    @empty
                        <option value="" disabled>Nenhum lote cadastrado</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Médio (kg) *</label>
                <input type="number" step="0.01" name="peso_medio_kg" required min="0" placeholder="Ex: 80,50"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-orange-800 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipo de Pesagem</label>
                <select name="tipo_pesagem" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
                    <option value="amostra">Amostra</option>
                    <option value="total">Lote Total</option>
                    <option value="individual">Individual</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Qtd. Amostra (nº pesados)</label>
                <input type="number" name="quantidade_amostra" min="1" placeholder="Ex: 20"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Qtd. Total no Lote</label>
                <input type="number" name="quantidade_lote" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Total (kg)</label>
                <input type="number" step="0.01" name="peso_total_kg" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">GPD Médio (g/dia) <span class="text-gray-400 font-normal text-xs">ganho/cabeça</span></label>
                <input type="number" step="0.001" name="gpd_medio" min="0" placeholder="Ex: 920,000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Mínimo (kg)</label>
                <input type="number" step="0.01" name="peso_minimo_kg" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Máximo (kg)</label>
                <input type="number" step="0.01" name="peso_maximo_kg" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Desvio Padrão</label>
                <input type="number" step="0.01" name="desvio_padrao" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Idade Média (dias)</label>
                <input type="number" name="idade_dias" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização</label>
                <input type="text" name="localizacao" maxlength="120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Baia</label>
                <input type="text" name="baia" maxlength="60"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 hover:border-orange-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-novo-peso')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-xs"></i> Salvar Pesagem
            </button>
        </div>
    </form>
</x-modal>
