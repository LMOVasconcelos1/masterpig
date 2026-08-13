<x-modal name="modal-nova-morte" maxWidth="lg">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.mortes.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-rose-900 tracking-tight">Registrar Morte · Terminação</h3>
                <p class="text-xs text-gray-500 mt-1">Informe a causa e a localização para auditoria.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-morte')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data da Morte *</label>
                <input type="hidden" name="data_morte" :value="morteDataIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refMorteData"
                           :value="morteData"
                           @input="morteData = $event.target.value"
                           @focus="openDatePicker('morte_data', $refs)"
                           @click="openDatePicker('morte_data', $refs)"
                           @blur="normalizeDisplay('morteDataIso', 'morteData')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-rose-600"
                            @click="openDatePicker('morte_data', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote *</label>
                <select name="lote_id" required class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
                    <option value="">Selecione...</option>
                    @forelse($lotesCadastrados ?? [] as $l)
                        <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                    @empty
                        <option value="" disabled>Nenhum lote cadastrado</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Quantidade *</label>
                <input type="number" name="quantidade" required min="1" placeholder="Ex: 2"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipo de Morte</label>
                <select name="tipo_morte" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
                    <option value="natural">Natural</option>
                    <option value="acidente">Acidente</option>
                    <option value="eutanásia">Eutanásia</option>
                    <option value="outro">Outro</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Causa <span class="text-gray-400 font-normal text-xs">(cadastrada)</span></label>
                <select name="causa_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
                    <option value="">Selecionar da lista...</option>
                    @forelse($causas ?? [] as $c)
                        <option value="{{ $c->id ?? 0 }}">{{ $c->nome ?? $c['nome'] ?? 'Causa '.$c->id }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Médio (kg)</label>
                <input type="number" step="0.01" name="peso_medio" min="0" placeholder="Ex: 70,00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Causa (texto livre) <span class="text-gray-400 font-normal text-xs">se não achar na lista</span></label>
                <input type="text" name="causa" maxlength="255" placeholder="Descreva rapidamente..."
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Local / Baia</label>
                <input type="text" name="localizacao" maxlength="120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Baia (específica)</label>
                <input type="text" name="baia" maxlength="60"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Identificação / Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000" placeholder="Nº brinco, baia hospitalar, etc."
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 hover:border-rose-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-morte')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-skull text-xs"></i> Confirmar Morte
            </button>
        </div>
    </form>
</x-modal>
