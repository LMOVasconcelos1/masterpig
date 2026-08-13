<x-modal name="modal-nova-entrada" maxWidth="xl">
    <form accept-charset="UTF-8" method="POST" action="{{ route('terminacao.entradas.store', [], false) }}" class="p-6 space-y-5">
        @csrf
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-lg font-black text-emerald-900 tracking-tight">Nova Entrada na Terminação</h3>
                <p class="text-xs text-gray-500 mt-1">Registre entrada de animais (compra, transferência ou ajuste).</p>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-entrada')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data da Entrada *</label>
                <input type="hidden" name="data_entrada" :value="entradaDataEntradaIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refEntradaDataEntrada"
                           :value="entradaDataEntrada"
                           @input="entradaDataEntrada = $event.target.value"
                           @focus="openDatePicker('entrada_data_entrada', $refs)"
                           @click="openDatePicker('entrada_data_entrada', $refs)"
                           @blur="normalizeDisplay('entradaDataEntradaIso', 'entradaDataEntrada')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-emerald-600"
                            @click="openDatePicker('entrada_data_entrada', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Lote de Destino *</label>
                <select name="lote_id" required class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
                    <option value="">Selecione um lote...</option>
                    @forelse($lotesCadastrados ?? [] as $l)
                        <option value="{{ $l['id'] }}">{{ $l['nome'] }} · {{ $l['situacao'] }}</option>
                    @empty
                        <option value="" disabled>-- Nenhum lote cadastrado. Crie primeiro em "Novo Lote". --</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Quantidade *</label>
                <input type="number" name="quantidade" required min="1" placeholder="Ex: 120"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipo de Origem</label>
                <select name="origem" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
                    <option value="creche">Creche (padrão)</option>
                    <option value="compra">Compra direta</option>
                    <option value="transferencia">Transferência</option>
                    <option value="outro">Outro / Ajuste</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Total (kg) <span class="text-gray-400 font-normal text-xs">ou médio abaixo</span></label>
                <input type="number" step="0.01" name="peso_total" min="0" placeholder="3.000,00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Peso Médio (kg/animal)</label>
                <input type="number" step="0.01" name="peso_medio" min="0" placeholder="25,00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Data Nascimento (média)</label>
                <input type="hidden" name="data_nascimento" :value="entradaDataNascimentoIso">
                <div class="relative">
                    <input type="text"
                           x-ref="refEntradaDataNascimento"
                           :value="entradaDataNascimento"
                           @input="entradaDataNascimento = $event.target.value"
                           @focus="openDatePicker('entrada_data_nascimento', $refs)"
                           @click="openDatePicker('entrada_data_nascimento', $refs)"
                           @blur="normalizeDisplay('entradaDataNascimentoIso', 'entradaDataNascimento')"
                           class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm pr-10"
                           :placeholder="calendarType === '1000_dias' ? 'Dia PIG (ex: 842)' : 'DD/MM/AAAA'"
                           inputmode="numeric"
                           autocomplete="off">
                    <button type="button" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-emerald-600"
                            @click="openDatePicker('entrada_data_nascimento', $refs)">
                        <i class="fa-solid fa-calendar-days"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Fornecedor <span class="text-gray-400 font-normal text-xs">(se for compra)</span></label>
                <select name="fornecedor_id" class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
                    <option value="">- Não se aplica -</option>
                    @if(!empty($fornecedores))
                        @foreach($fornecedores as $f) <option value="{{ $f->id ?? 0 }}">{{ $f->nome ?? 'Fornecedor' }}</option> @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Localização / Setor</label>
                <input type="text" name="localizacao" maxlength="120" placeholder="Ex: Galpão 1"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Baia</label>
                <input type="text" name="baia" maxlength="60" placeholder="Ex: B-05"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Nota Fiscal</label>
                <input type="text" name="nota_fiscal" maxlength="120" placeholder="Nº da NF"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Valor Unitário (R$)</label>
                <input type="number" step="0.01" name="valor_unitario" min="0" placeholder="0,00"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Observações</label>
                <textarea name="observacoes" rows="2" maxlength="1000"
                    class="w-full border-2 border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 hover:border-emerald-300 transition shadow-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
            <button type="button" @click="$dispatch('close-modal', 'modal-nova-entrada')" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
                Cancelar
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-black shadow-sm transition inline-flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i> Registrar Entrada
            </button>
        </div>
    </form>
</x-modal>
