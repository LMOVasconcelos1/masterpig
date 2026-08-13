<x-modal name="modal-nova-venda" maxWidth="2xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.vendas.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-emerald-900 tracking-tight">Nova Venda / Envio para Abate</h3>
                <p class="text-xs text-gray-500 mt-1">Registro de saída final dos animais da terminação.</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-venda')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data da Venda *</label>
                <input type="hidden" name="data_venda" :value="vendaDataIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refVendaData"
                           :value="vendaData"
                           @input="vendaData = $event.target.value"
                           @focus="openDatePicker('venda_data', $refs)"
                           @click="openDatePicker('venda_data', $refs)"
                           @blur="normalizeDisplay('vendaDataIso', 'vendaData')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-emerald-600"
                            @click="openDatePicker('venda_data', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote *</label>
                <select name="lote_id" required class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
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
                <input type="number" name="quantidade" required min="1" placeholder="Ex: 115"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipo de Saída</label>
                <select name="tipo_saida" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
                    <option value="abate">Abate (padrão)</option>
                    <option value="venda_vivo">Venda Vivo</option>
                    <option value="doacao">Doação</option>
                    <option value="outro">Outro</option>
                </select>
            </div>

            <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-4 p-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
                <div class="text-xs font-black uppercase tracking-wider text-emerald-700 col-span-2 mb-1">Pesagens</div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Peso Total (kg) · Granja</label>
                    <input type="number" step="0.01" name="peso_total_kg" min="0"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Peso Médio (kg)</label>
                    <input type="number" step="0.01" name="peso_medio_kg" min="0"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Peso Frigorífico (kg)</label>
                    <input type="number" step="0.01" name="peso_frigorifico_kg" min="0"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Rendimento Carcaça (%)</label>
                    <input type="number" step="0.01" name="rendimento_carcaca_pct" min="0" max="100" placeholder="Ex: 76,50"
                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Valor Unitário (R$)</label>
                <input type="number" step="0.01" name="valor_unitario" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Valor Total (R$)</label>
                <input type="number" step="0.01" name="valor_total" min="0"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Comprador / Frigorífico (cadastrado)</label>
                <select name="comprador_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
                    <option value="">- Selecionar -</option>
                    @forelse($fornecedores ?? [] as $f)
                        <option value="{{ $f->id ?? 0 }}">{{ $f->nome ?? 'Fornecedor' }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Nome do Frigorífico</label>
                <input type="text" name="frigorifico_nome" maxlength="200" placeholder="Ex: Frigorífico XYZ Ltda"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Motorista</label>
                <input type="text" name="motorista_nome" maxlength="200"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Placa do Caminhão</label>
                <input type="text" name="placa_caminhao" maxlength="20" placeholder="Ex: ABC1D23"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">NF Saída</label>
                <input type="text" name="nota_fiscal_saida" maxlength="120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Chave NFe</label>
                <input type="text" name="chave_nfe" maxlength="80"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Local de Saída / Baia</label>
                <input type="text" name="localizacao" maxlength="120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-venda')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-truck-fast text-xs"></i> Registrar Venda / Abate
            </button>
        </div>
    </form>
</x-modal>
