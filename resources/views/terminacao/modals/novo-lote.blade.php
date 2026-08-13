<x-modal name="modal-novo-lote" maxWidth="xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.lotes.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-gray-900 tracking-tight">Novo Lote de Terminação</h3>
                <p class="text-xs text-gray-500 mt-1">Crie manualmente um lote de engorda / acabamento.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-novo-lote')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Identificação do Lote *</label>
                <input type="text" name="nome" required maxlength="120" placeholder="Ex: 13/26 ou LOTE-001"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
                <x-input-error :messages="$errors->lote->get('nome') ?? []" class="mt-1" />
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Origem</label>
                <select name="origem" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
                    <option value="creche">Creche (transferido)</option>
                    <option value="compra">Compra / Entrada direta</option>
                    <option value="transferencia">Transferência interna</option>
                    <option value="outro">Outro</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data de Entrada</label>
                <input type="hidden" name="data_entrada" :value="loteDataEntradaIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refLoteDataEntrada"
                           :value="loteDataEntrada"
                           @input="loteDataEntrada = $event.target.value"
                           @focus="openDatePicker('lote_data_entrada', $refs)"
                           @click="openDatePicker('lote_data_entrada', $refs)"
                           @blur="normalizeDisplay('loteDataEntradaIso', 'loteDataEntrada')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-amber-600"
                            @click="openDatePicker('lote_data_entrada', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Quantidade Inicial</label>
                <input type="number" name="quantidade_inicial" min="0" placeholder="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
            </div>

            @if(!empty($crecheLotes))
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote de Origem (Creche) <span class="text-gray-400 font-normal text-xs">opcional</span></label>
                <select name="creche_lote_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
                    <option value="">- Nenhum / Manual -</option>
                    @foreach($crecheLotes as $cl)
                        <option value="{{ $cl['id'] ?? 0 }}">{{ $cl['nome'] ?? 'Lote '.$cl['id'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Galpão</label>
                <input type="text" name="galpao" maxlength="80" placeholder="Ex: G1, Galpão Sul"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização / Baia</label>
                <input type="text" name="localizacao" maxlength="120" placeholder="Ex: Baia 12, Setor A"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Meta de Dias <span class="text-gray-400 font-normal text-xs">(padrão 90)</span></label>
                <input type="number" name="meta_dias_terminacao" min="1" placeholder="90" value="90"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Meta Peso Abate (kg) <span class="text-gray-400 font-normal text-xs">(padrão 115)</span></label>
                <input type="number" step="0.01" name="meta_peso_abate_kg" min="0" placeholder="115.00" value="115.00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Características <span class="text-gray-400 font-normal text-xs">(genética, grupo, etc.)</span></label>
                <textarea name="caracteristicas" rows="2" maxlength="1000" placeholder="Ex: Genética PIC, machos castrados, lote misto..."
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm"></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 hover:border-amber-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-novo-lote')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk text-xs"></i> Salvar Lote
            </button>
        </div>
    </form>
</x-modal>
