<x-modal name="modal-transf-creche" maxWidth="xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.transferir-da-creche', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-sky-900 tracking-tight flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 inline-flex items-center justify-center text-sm">
                        <i class="fa-solid fa-arrows-spin"></i>
                    </span>
                    Transferir Lote da Creche ? Terminação
                </h3>
                <p class="text-xs text-gray-500 mt-1">Movimenta animais do final da creche para o início da terminação.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-transf-creche')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 p-4 rounded-2xl bg-gradient-to-br from-sky-50 to-cyan-50 border border-sky-100">
                <label class="block text-sm font-black text-sky-800 mb-1.5">Lote da Creche de Origem *</label>
                <select name="creche_lote_id" required class="w-full border-2 border-white/80 rounded-xl px-3.5 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition shadow-sm">
                    <option value="">Selecione o lote da creche...</option>
                    @forelse($crecheLotes ?? [] as $cl)
                        <option value="{{ $cl['id'] ?? 0 }}">
                            {{ $cl['nome'] ?? 'Lote '.$cl['id'] }}
                            @if(!empty($cl['situacao'])) · <span class="text-xs opacity-80">{{ $cl['situacao'] }}</span> @endif
                        </option>
                    @empty
                        <option value="" disabled>-- Nenhum lote da creche encontrado no banco --</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data de Entrada *</label>
                <input type="hidden" name="data_entrada" :value="crecheDataEntradaIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refCrecheDataEntrada"
                           :value="crecheDataEntrada"
                           @input="crecheDataEntrada = $event.target.value"
                           @focus="openDatePicker('creche_data_entrada', $refs)"
                           @click="openDatePicker('creche_data_entrada', $refs)"
                           @blur="normalizeDisplay('crecheDataEntradaIso', 'crecheDataEntrada')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-sky-600"
                            @click="openDatePicker('creche_data_entrada', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Quantidade a transferir *</label>
                <input type="number" name="quantidade" required min="1" placeholder="Ex: 120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-xs font-bold text-amber-800 mb-2">
                    <i class="fa-solid fa-circle-info"></i> DESTINO: escolha abaixo se será em lote EXISTENTE ou criará um NOVO lote automaticamente.
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote de Terminação <span class="text-gray-400 font-normal text-xs">(existente)</span></label>
                <select name="terminacao_lote_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
                    <option value="">- Usar Nome do Novo Lote abaixo -</option>
                    @forelse($lotesCadastrados ?? [] as $l)
                        <option value="{{ $l['id'] }}">{{ $l['nome'] }} · {{ $l['situacao'] }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">OU Nome p/ Novo Lote <span class="text-gray-400 font-normal text-xs">(se acima vazio)</span></label>
                <input type="text" name="novo_lote_nome" maxlength="120" placeholder="Ex: 14/26-TERM"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Total (kg)</label>
                <input type="number" step="0.01" name="peso_total" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Médio (kg/animal)</label>
                <input type="number" step="0.01" name="peso_medio" min="0" placeholder="Ex: 25,00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data Nascimento (média)</label>
                <input type="hidden" name="data_nascimento" :value="crecheDataNascimentoIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refCrecheDataNascimento"
                           :value="crecheDataNascimento"
                           @input="crecheDataNascimento = $event.target.value"
                           @focus="openDatePicker('creche_data_nascimento', $refs)"
                           @click="openDatePicker('creche_data_nascimento', $refs)"
                           @blur="normalizeDisplay('crecheDataNascimentoIso', 'crecheDataNascimento')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-sky-600"
                            @click="openDatePicker('creche_data_nascimento', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização / Baia</label>
                <input type="text" name="localizacao" maxlength="120" placeholder="Galpão 1 · Baia 3"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 hover:border-sky-300 transition shadow-sm">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-transf-creche')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-check-double text-xs"></i> Confirmar Transferência
            </button>
        </div>
    </form>
</x-modal>
