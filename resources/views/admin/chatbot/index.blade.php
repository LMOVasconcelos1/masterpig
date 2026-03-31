@extends('layouts.dashboard')

@section('title', 'Chatbot')
@section('page_title', 'Chatbot')

@section('content')
<div class="max-w-3xl mx-auto" x-data="{
    input: '',
    loading: false,
    messages: [
        { role: 'bot', text: 'Me pergunte algo do banco.\n\nExemplos:\n- Quantas fêmeas ativas?\n- Quantos machos ativos?\n- Quantas leitoas ativas?\n- Quantas matrizes ativas?\n- Quantas mortes no este mês?\n- Quantas vendas nos últimos 30 dias?' }
    ],
    async send() {
        const text = String(this.input || '').trim();
        if (!text || this.loading) return;
        this.messages.push({ role: 'user', text });
        this.input = '';
        this.loading = true;
        try {
            const r = await fetch('/api/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\\'csrf-token\\']').getAttribute('content'),
                },
                body: JSON.stringify({ message: text }),
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data?.message || 'Erro ao consultar.');
            this.messages.push({ role: 'bot', text: String(data?.answer || '') || 'Sem resposta.' });
        } catch (e) {
            this.messages.push({ role: 'bot', text: String(e?.message || 'Erro ao consultar.') });
        } finally {
            this.loading = false;
            this.$nextTick(() => {
                const el = this.$refs.list;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    },
}" class="space-y-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between">
            <div>
                <div class="font-bold text-gray-900 dark:text-gray-100">Assistente do Sui Control</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Respostas baseadas no banco de dados.</div>
            </div>
        </div>
        <div x-ref="list" class="p-5 space-y-3 h-[60vh] overflow-y-auto bg-white dark:bg-gray-900">
            <template x-for="(m, idx) in messages" :key="idx">
                <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm whitespace-pre-wrap"
                        :class="m.role === 'user' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200'">
                        <span x-text="m.text"></span>
                    </div>
                </div>
            </template>
            <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">Consultando...</div>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
            <form @submit.prevent="send()" class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="sr-only">Mensagem</label>
                    <textarea
                        x-model="input"
                        rows="2"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm focus:ring-primary-500 focus:border-primary-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        placeholder="Digite sua pergunta…"
                        @keydown.enter.prevent="if(!$event.shiftKey) send(); else input += '\n';"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Enter envia, Shift+Enter quebra linha.</div>
                </div>
                <button type="submit" :disabled="loading || !input.trim()" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Enviar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

