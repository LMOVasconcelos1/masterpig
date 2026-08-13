<x-modal name="modal-nova-transferencia" maxWidth="xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.transferencias.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-indigo-900 tracking-tight">Nova Transferência · Terminação</h3>
                <p class="text-xs text-gray-500 mt-1">Entre baias, galpões ou lotes de terminação diferentes.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-transferencia')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2 grid grid-cols-2 gap-4 p-4 rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100">
                <div class="text-xs font-black uppercase tracking-wider text-indigo-700 col-span-2 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Origem
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote de Origem *</label>
                    <select name="lote_origem_id" required class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
                        <option value="">Selecione...</option>
                        @forelse($lotesCadastrados ?? [] as $l)
                            <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                        @empty
                            <option value="" disabled>Nenhum lote cadastrado</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Quantidade *</label>
                    <input type="number" name="quantidade" required min="1" placeholder="Ex: 30"
                        class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização Origem</label>
                    <input type="text" name="localizacao_origem" maxlength="120"
                        class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Baia Origem</label>
                    <input type="text" name="baia_origem" maxlength="60"
                        class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
                </div>
            </div>

            <div class="md:col-span-2 grid grid-cols-2 gap-4 p-4 rounded-2xl bg-gradient-to-br from-sky-50 to-emerald-50 border border-sky-100">
                <div class="text-xs font-black uppercase tracking-wider text-sky-700 col-span-2 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Destino
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote de Destino <span class="text-gray-400 font-normal text-xs">(vazio = mesmo lote)</span></label>
                    <select name="lote_destino_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
                        <option value="">- Mesmo lote (só troca de baia) -</option>
                        @forelse($lotesCadastrados ?? [] as $l)
                            <option value="{{ $l['id'] }}">{{ $l['nome'] }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipo</label>
                    <select name="tipo" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
                        <option value="baia">Troca de Baia</option>
                        <option value="lote">Entre Lotes</option>
                        <option value="hospital">Para Hospital</option>
                        <option value="desclassificado">Desclassificado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização Destino</label>
                    <input type="text" name="localizacao_destino" maxlength="120"
                        class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Baia Destino</label>
                    <input type="text" name="baia_destino" maxlength="60"
                        class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data *</label>
                <input type="hidden" name="data_transferencia" :value="transfDataIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refTransfData"
                           :value="transfData"
                           @input="transfData = $event.target.value"
                           @focus="openDatePicker('transf_data', $refs)"
                           @click="openDatePicker('transf_data', $refs)"
                           @blur="normalizeDisplay('transfDataIso', 'transfData')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600"
                            @click="openDatePicker('transf_data', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Total (kg) <span class="text-gray-400 font-normal text-xs">opcional</span></label>
                <input type="number" step="0.01" name="peso_total" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Motivo</label>
                <input type="text" name="motivo" maxlength="255" placeholder="Ex: agrupamento de lotes, hospital, classificação..."
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:border-indigo-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-transferencia')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-right-left text-xs"></i> Confirmar Transferência
            </button>
        </div>
    </form>
</x-modal>
