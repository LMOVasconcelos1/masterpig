<?php
/**
 * Flagsmith — Interactive Tour Center (MOBILE-FIRST)
 * Componente flutuante (canto inferior esquerdo, respeita safe-area)
 * que organiza tutoriais POR MANEJO e exibe progresso %.
 * Apenas dispara tooltip/highlight após tutorial selecionado.
 *
 * Melhorias:
 *  1. Botão posicionado ACIMA da barra de aviso "versão" (bottom-20 / 84px min)
 *  2. Filtro automático: mostra APENAS tutoriais do MANEJO ATUAL (detectado via URL pathname).
 *     Usuário pode expandir para "ver todos" se quiser.
 *  3. Highlight mais preciso: padding 14px, scrollIntoView com padding extra,
 *     fallback highlight maior caso elemento não exista, e poll de 1.2s enquanto
 *     tutorial está aberto para reposicionar (lazy-load / dinâmicos).
 *
 * CONTROLE DE LIBERAÇÃO:
 *   Altere $TOUR_CENTER_LIBERADO para true quando quiser liberar para todos os usuários.
 *   Enquanto false, componente NÃO RENDERIZA NADA (sem botão flutuante, sem script).
 */
$TOUR_CENTER_LIBERADO = false;
?>
@if($TOUR_CENTER_LIBERADO)
<style>
    /* x-cloak precisa de CSS inline para OCULTAR antes do Alpine inicializar */
    [x-cloak] { display: none !important; }
</style>

<div x-data="tourCenter()" x-init="init()" x-cloak class="fixed z-[9998] left-0 bottom-[96px] sm:left-4 sm:bottom-24 w-full sm:w-auto">

    {{-- ================ BOTÃO FLUTUANTE MINI (FALLBACK - acesso secundário; principal é o MENU) ================ --}}
    <div class="p-3 pb-[max(12px,calc(env(safe-area-inset-bottom)+8px))] sm:p-0 flex justify-start sm:justify-start">
        <button type="button" @click="togglePanel()"
            title="Tour Center (Tutoriais)"
            class="relative group inline-flex items-center justify-center w-12 h-12 sm:w-11 sm:h-11 rounded-2xl text-white shadow-xl shadow-amber-900/40 transition-all duration-300 active:scale-[0.97] bg-gradient-to-br from-amber-500 via-amber-600 to-amber-800 border border-amber-400/40 focus:outline-none focus:ring-4 focus:ring-amber-300/50">
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white/60 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-white items-center justify-center text-[9px] font-black text-amber-700" x-text="categoriasVisiveis.length"></span>
            </span>
            <i class="fa-solid fa-flag text-base sm:text-sm"></i>
        </button>
    </div>

    {{-- ================ PAINEL DE TUTORIAIS (mobile full-height bottomsheet) ================ --}}
    <template x-if="panelOpen">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[80%]"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-[100%]"
            class="fixed inset-x-0 bottom-[84px] sm:static sm:mt-3 left-0 w-full sm:absolute sm:bottom-[78px] sm:left-0 sm:w-[440px] max-h-[82vh] sm:max-h-[78vh] rounded-t-[28px] sm:rounded-3xl border-t sm:border border-amber-100 bg-white/98 backdrop-blur-xl shadow-[0_-12px_50px_-20px_rgba(0,0,0,0.25)] sm:shadow-2xl sm:shadow-slate-900/20 ring-1 ring-black/5 overflow-hidden flex flex-col">

            <div class="sm:hidden h-1.5 w-12 rounded-full bg-slate-200 mx-auto mt-3 mb-1"></div>

            <div class="px-5 pt-3 pb-4 sm:px-5 sm:py-4 border-b border-amber-100/80 bg-gradient-to-r from-amber-50 via-amber-50/50 to-white flex items-start justify-between gap-3 shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-600 to-amber-800 text-white flex items-center justify-center shadow-md shrink-0">
                            <i class="fa-solid fa-flag text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-base font-black text-amber-950 tracking-tight">Flagsmith Tour Center</div>
                            <div class="text-xs text-amber-800/80 mt-0.5 flex items-center gap-1.5">
                                <span x-text="'Manejo atual: ' + manejoNome"></span>
                                <span x-show="categoriasVisiveis.length !== totalCategorias"
                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-primary-50 text-primary-800 text-[10px] font-bold uppercase border border-primary-100">
                                    <i class="fa-solid fa-filter text-[9px]"></i>
                                    filtrado
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" @click="filtrarManejo = true"
                                class="min-h-[36px] inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition-all"
                                :class="filtrarManejo
                                    ? 'bg-gradient-to-b from-amber-600 to-amber-700 text-white border border-amber-600 shadow-sm shadow-amber-900/20'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-800'">
                            <i class="fa-solid fa-filter text-[10px]"></i>
                            Apenas este manejo
                        </button>
                        <button type="button" @click="filtrarManejo = false"
                                class="min-h-[36px] inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition-all"
                                :class="!filtrarManejo
                                    ? 'bg-gradient-to-b from-slate-700 to-slate-800 text-white border border-slate-700 shadow-sm shadow-slate-900/20'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:text-slate-800'">
                            <i class="fa-solid fa-layer-group text-[10px]"></i>
                            Todos os manejos
                        </button>
                    </div>
                </div>
                <button @click="panelOpen = false" class="shrink-0 w-9 h-9 rounded-xl border border-amber-200/80 bg-white/80 text-amber-700 hover:bg-amber-100 hover:text-amber-900 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-3 sm:px-4 py-3 space-y-3">
                <template x-for="(cat, catIdx) in categoriasVisiveis" :key="cat.id">
                    <div class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
                        <button type="button" @click="toggleCategoria(cat.id)"
                                class="w-full px-4 py-3.5 flex items-center gap-3 text-left focus:outline-none focus:bg-amber-50/60">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg"
                                 :style="'background:'+cat.bg+';color:'+cat.color">
                                <i :class="cat.icon"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-black text-slate-900 truncate" x-text="cat.nome"></div>
                                    <div class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                         :class="cat.progresso===100 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'"
                                         x-text="cat.progresso + '%'"></div>
                                </div>
                                <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :style="'width:'+cat.progresso+'%;background:'+(cat.progresso===100?'#10b981':'linear-gradient(90deg,#f59e0b,#d97706)')"></div>
                                </div>
                                <div class="mt-1.5 text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <i class="fa-solid fa-book-open text-slate-400"></i>
                                    <span x-text="tutoriaisDaCategoria(cat.id).length + ' tutorial(is)'"></span>
                                    <span class="text-slate-300">•</span>
                                    <span x-text="tutoriaisConcluidosDaCategoria(cat.id) + ' concluído(s)'"></span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200 shrink-0"
                               :class="categoriasAbertas.includes(cat.id) ? 'rotate-180 text-amber-600' : ''"></i>
                        </button>

                        <template x-if="categoriasAbertas.includes(cat.id)">
                            <div class="border-t border-slate-100 bg-slate-50/60">
                                <div class="p-2.5 space-y-2.5">
                                    <template x-for="t in tutoriaisDaCategoria(cat.id)" :key="t.id">
                                        <div class="group rounded-xl border border-slate-200/80 bg-white hover:border-amber-200 hover:shadow-sm transition-all p-3">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-[10px] font-black"
                                                     :class="t.concluido ? 'bg-emerald-100 text-emerald-700' : (tutorialAtivo?.id===t.id ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-300/60' : 'bg-slate-100 text-slate-500')">
                                                    <i :class="t.concluido ? 'fa-solid fa-check' : (tutorialAtivo?.id===t.id ? 'fa-solid fa-play text-[9px]' : 'fa-solid fa-hashtag')"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div class="font-bold text-slate-900 leading-snug" x-text="t.titulo"></div>
                                                        <template x-if="t.concluido">
                                                            <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2 py-0.5">OK</span>
                                                        </template>
                                                    </div>
                                                    <p class="text-[12px] text-slate-600 mt-1 leading-snug" x-text="t.descricao"></p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                                                        <span class="inline-flex items-center gap-1">
                                                            <i class="fa-solid fa-stairs"></i>
                                                            <span x-text="t.steps.length + ' passos'"></span>
                                                        </span>
                                                        <span class="text-slate-300">•</span>
                                                        <span class="inline-flex items-center gap-1">
                                                            <i class="fa-solid fa-stopwatch"></i>
                                                            <span x-text="t.duracao + 's'"></span>
                                                        </span>
                                                        <template x-if="t.progresso && !t.concluido">
                                                            <>
                                                                <span class="text-slate-300">•</span>
                                                                <span class="text-amber-700 font-semibold" x-text="'Etapa ' + t.progresso + '/' + t.steps.length"></span>
                                                            </>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                <button type="button" @click="startTutorial(t)"
                                                    class="min-h-[40px] inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold transition-all"
                                                    :class="tutorialAtivo?.id===t.id
                                                        ? 'bg-amber-100 text-amber-900 border border-amber-200 ring-2 ring-amber-200/60'
                                                        : 'bg-gradient-to-b from-amber-600 to-amber-700 text-white border border-amber-600 shadow-sm shadow-amber-900/20 active:scale-[0.98]'">
                                                    <i class="fa-solid" :class="tutorialAtivo?.id===t.id ? 'fa-eye' : 'fa-play'"></i>
                                                    <span x-text="tutorialAtivo?.id===t.id ? (stepIndex>=0 ? 'Continuar' : 'Em andamento') : (t.concluido ? 'Refazer' : 'Iniciar')"></span>
                                                </button>
                                                <template x-if="tutorialAtivo?.id===t.id && stepIndex>=0">
                                                    <button type="button" @click="stopTutorial()"
                                                            class="min-h-[40px] inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2.5 text-sm font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 active:scale-[0.98] transition-all">
                                                        <i class="fa-solid fa-stop"></i>
                                                        Parar
                                                    </button>
                                                </template>
                                                <template x-if="t.concluido">
                                                    <button type="button" @click="resetarTutorial(t)"
                                                            class="ml-auto min-h-[40px] inline-flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-500 hover:text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-100 active:scale-[0.98] transition-all">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                        Resetar
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <div x-show="categoriasVisiveis.length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center" x-cloak>
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-white border border-slate-200 text-slate-400 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-ghost text-lg"></i>
                    </div>
                    <div class="font-bold text-slate-700">Sem tutoriais para este manejo</div>
                    <button @click="filtrarManejo = false" class="mt-3 inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold bg-gradient-to-b from-amber-600 to-amber-700 text-white border border-amber-600 shadow-sm shadow-amber-900/20">
                        <i class="fa-solid fa-layer-group"></i>
                        Ver todos os tutoriais
                    </button>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-amber-100/80 bg-amber-50/40 flex items-center justify-between gap-3 text-[11px] shrink-0 pb-[max(12px,env(safe-area-inset-bottom))]">
                <button @click="resetarTodos()"
                        class="min-h-[36px] inline-flex items-center gap-1.5 text-slate-500 hover:text-rose-700 font-semibold transition-colors">
                    <i class="fa-solid fa-rotate-left"></i>
                    Resetar
                </button>
                <div class="text-slate-500">
                    Geral: <span class="font-black text-amber-800 text-sm" x-text="progressoGeral + '%'"></span>
                </div>
            </div>
        </div>
    </template>

    {{-- ================ OVERLAY + HIGHLIGHT (SOBREPÕEM DURANTE TUTORIAL) ================ --}}
    <template x-if="stepIndex >= 0">
        <div class="fixed inset-0 z-[9998] bg-slate-950/55 pointer-events-none"></div>
    </template>

    <template x-if="stepIndex >= 0">
        <div id="tour-highlight"
             class="fixed z-[9998] pointer-events-none rounded-2xl ring-4 ring-amber-400 ring-offset-2 ring-offset-slate-950/10 shadow-[0_0_0_9999px_rgba(2,6,23,0.38)] transition-all duration-300 ease-out"></div>
    </template>

    {{-- ================ TOOLTIP DO PASSO (mobile: 95% width, topo se necessário) ================ --}}
    <template x-if="stepIndex >= 0 && tutorialAtivo">
        <div class="fixed z-[9999] w-[95vw] sm:w-[400px] max-w-[400px]"
             :style="tooltipStyle">
            <div class="relative rounded-3xl bg-white shadow-2xl shadow-slate-900/30 border border-amber-100 overflow-hidden">
                <div class="px-4 sm:px-5 pt-3.5 pb-3 bg-gradient-to-r from-amber-50 via-amber-50/60 to-white border-b border-amber-100/80 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-600 to-amber-800 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <i :class="(currentStep && currentStep.icon) ? currentStep.icon : 'fa-solid fa-circle-info'"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10.5px] font-black uppercase tracking-wider text-amber-700 leading-none">
                                Passo <span x-text="stepIndex + 1"></span>/<span x-text="tutorialAtivo.steps.length"></span>
                                <span class="font-normal normal-case tracking-normal text-amber-700/70" x-text="' · ' + tutorialAtivo.categoriaNome"></span>
                            </div>
                            <div class="text-sm font-black text-slate-900 mt-1 leading-snug"
                                 x-text="currentStep ? currentStep.titulo : ''"></div>
                        </div>
                    </div>
                    <button @click="stopTutorial()"
                            class="shrink-0 w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-100 flex items-center justify-center transition-all" title="Encerrar tour">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="px-4 sm:px-5 py-4 space-y-3">
                    <p class="text-[14px] leading-relaxed text-slate-700"
                       x-text="currentStep ? currentStep.descricao : ''"></p>
                    <template x-if="currentStep && currentStep.dica">
                        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3 text-[13px] text-amber-900 leading-relaxed flex items-start gap-2.5">
                            <i class="fa-solid fa-lightbulb text-amber-500 shrink-0 mt-0.5"></i>
                            <div x-text="currentStep.dica"></div>
                        </div>
                    </template>
                    <template x-if="currentStep && currentStep.actionLabel">
                        <div class="rounded-2xl bg-primary-50/80 border border-primary-100 p-3 text-[13px] text-primary-900 leading-relaxed flex items-start gap-2.5">
                            <i class="fa-solid fa-hand-pointer text-primary-600 shrink-0 mt-0.5"></i>
                            <div>
                                <strong class="font-black">Faça agora:</strong>
                                <span x-text="' ' + currentStep.actionLabel"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-4 sm:px-5 py-3 bg-slate-50/80 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pb-[max(12px,env(safe-area-inset-bottom))]">
                    <button type="button" @click="prevStep()"
                        :disabled="stepIndex === 0"
                        class="sm:w-auto w-full min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 hover:text-slate-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-slate-600">
                        <i class="fa-solid fa-arrow-left"></i>
                        Anterior
                    </button>

                    <div class="flex items-center justify-center gap-1.5 order-3 sm:order-2">
                        <template x-for="(_, i) in tutorialAtivo.steps" :key="i">
                            <div class="h-1.5 rounded-full transition-all duration-200 w-4"
                                 :class="i < stepIndex ? 'bg-emerald-400' : (i === stepIndex ? 'w-6 bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-slate-300')"></div>
                        </template>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 order-2 sm:order-3">
                        <template x-if="stepIndex === tutorialAtivo.steps.length - 1">
                            <button type="button" @click="finishTutorial()"
                                class="min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold bg-gradient-to-b from-emerald-500 to-emerald-600 text-white border border-emerald-500 shadow-sm shadow-emerald-900/20 active:scale-[0.98] transition-all">
                                <i class="fa-solid fa-check"></i>
                                Concluir
                            </button>
                        </template>
                        <template x-else>
                            <button type="button" @click="nextStep()"
                                class="min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold bg-gradient-to-b from-amber-600 to-amber-700 text-white border border-amber-600 shadow-sm shadow-amber-900/20 active:scale-[0.98] transition-all">
                                Próximo
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
/**
 * BRIDGE GLOBAL (FUNCIONA ANTES DO ALPINE INICIALIZAR)
 *
 * Os botões do menu (no topo da página) carregam ANTES do componente Tour Center
 * (que está no fim da página).  Então eventos/funções Alpine não existem no momento
 * do clique.  Solução: bufferizamos a intenção num singleton global e retentamos
 * chamar o Alpine a cada 50ms até ele inicializar (em geral 1~6 tentativas).
 */
(function () {
    const pendingQueue = [];
    let retryCount = 0;
    const MAX_RETRIES = 40; // 40 * 50ms = até 2s de retry (tranquilo)

    function getAlpineData() {
        try {
            const el = document.querySelector('[x-data="tourCenter()"]');
            if (!el) return null;
            // Alpine 3: dados em el.__x (internals)
            if (el.__x && el.__x.$data && typeof el.__x.$data.togglePanel === 'function') {
                return el.__x.$data;
            }
            // Tentar via Alpine.store ou Alpine.$data fallback
            if (window.Alpine && typeof window.Alpine.$data === 'function') {
                const d = window.Alpine.$data(el);
                if (d && typeof d.togglePanel === 'function') return d;
            }
        } catch (e) {}
        return null;
    }

    function processQueue() {
        if (pendingQueue.length === 0) { retryCount = 0; return; }
        const data = getAlpineData();
        if (data) {
            // Alpine inicializado — esvaziamos a fila
            while (pendingQueue.length > 0) {
                const action = pendingQueue.shift();
                try {
                    if (action === 'toggle') data.togglePanel();
                    else if (action === 'open') { if (!data.panelOpen) data.panelOpen = true; }
                    else if (action === 'close') { if (data.panelOpen) data.panelOpen = false; }
                } catch (e) {}
            }
            retryCount = 0;
            return;
        }
        retryCount++;
        if (retryCount >= MAX_RETRIES) {
            // Desistimos (Alpine não carregou em 2s)
            pendingQueue.length = 0;
            retryCount = 0;
            try {
                console && console.warn && console.warn('[Tour Center] Alpine não inicializou após 2s.');
            } catch(e){}
            return;
        }
        setTimeout(processQueue, 50);
    }

    window.abrirTourCenter = function (action) {
        action = action || 'toggle';
        // Já tentamos chamar imediatamente (caso Alpine já tenha inicializado)
        const data = getAlpineData();
        if (data) {
            try {
                if (action === 'toggle') data.togglePanel();
                else if (action === 'open') { if (!data.panelOpen) data.panelOpen = true; }
                else if (action === 'close') { if (data.panelOpen) data.panelOpen = false; }
                return true;
            } catch(e){}
        }
        // Caso contrário: bufferizar e iniciar retry
        if (pendingQueue.length === 0) setTimeout(processQueue, 50);
        pendingQueue.push(action);
        // Também disparamos evento custom caso o listener Alpine já exista
        try {
            window.dispatchEvent(new CustomEvent('tourcenter:' + action));
        } catch(e){}
        return false;
    };
    // Expõe o buffer para o Alpine ler no init (backward compat extra)
    window.__tourCenterPending = pendingQueue;
})();

function tourCenter() {
    const DEFAULT_TUTORIAIS = [
        {
            id: 'manejo_plantel_novo',
            categoria: 'plantel',
            categoriaNome: 'Plantel',
            titulo: 'Cadastrar uma nova fêmea',
            descricao: 'Aprenda a cadastrar sua primeira leitoa ou matriz no plantel ativo.',
            duracao: 45,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Abra o módulo Plantel',
                    descricao: 'O Plantel é o módulo central. Ele gerencia todas as fêmeas e machos reprodutores da granja.',
                    seletor: '#menu-plantel, .nav-plantel, [href*="plantel/femeas"], li:has(a[href*="plantel/femeas"]), [data-nav="plantel"]',
                    posicao: 'bottom',
                    icon: 'fa-solid fa-cow',
                    dica: 'Sempre comece pelo Plantel. Tudo (gestação, maternidade) gira em torno dele!'
                },
                {
                    titulo: 'Painel principal do Plantel',
                    descricao: 'Use as abas: Visão Geral, Lançamentos, Acompanhamento, Análise e Relatórios.',
                    seletor: '.dashboard-tabs, nav > button, .plantel-dashboard, .main-tabs',
                    fallback: { x: '50%', y: '18%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-compass'
                },
                {
                    titulo: 'Botão Nova Fêmea',
                    descricao: 'Clique aqui para abrir o formulário de cadastro (leitoa ou matriz).',
                    seletor: '.btn-nova-femea, #btn-nova-femea, button:has(> i.fa-plus), [data-action="nova-femea"], button:has-text("Nova Fêmea"), button:has-text("Cadastrar")',
                    fallback: { x: '80%', y: '25%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-plus-circle',
                    actionLabel: 'Clique para ver o modal abrir'
                }
            ]
        },
        {
            id: 'manejo_plantel_relatorios',
            categoria: 'plantel',
            categoriaNome: 'Plantel',
            titulo: 'Usar a aba Relatórios',
            descricao: 'Encontre relatório de fêmeas, machos e formulários para impressão.',
            duracao: 35,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Aba Relatórios',
                    descricao: 'Clique na aba ao lado de Análise para abrir os relatórios.',
                    seletor: 'button:has-text("Relatórios"), [x-data] button:has-text("Relatórios"), [data-tab="relatorios"]',
                    fallback: { x: '92%', y: '8%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-file-invoice',
                    actionLabel: 'Clique na aba Relatórios para abrir'
                },
                {
                    titulo: 'Relatório de Fêmeas',
                    descricao: 'Abra a página de filtros antes de gerar PDF ou CSV.',
                    seletor: 'a[href*="femeas/filter"], a[href*="femeas/filtro"], button:has-text("Filtrar e gerar")',
                    fallback: { x: '30%', y: '35%' },
                    posicao: 'right',
                    icon: 'fa-solid fa-sliders'
                }
            ]
        },
        {
            id: 'manejo_cio_cobertura',
            categoria: 'gestacao',
            categoriaNome: 'Gestação / Cobertura',
            titulo: 'Registrar cio e cobertura',
            descricao: 'Fluxo completo: detectar cio, lançar cobertura e marcar diagnóstico de gestação.',
            duracao: 60,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Acesse a aba Gestação',
                    descricao: 'Monitore fêmeas gestantes, vazias e ciclos reprodutivos.',
                    seletor: '[href*="gestacao"], .nav-gestacao, #menu-gestacao, li:has(a[href*="gestacao"]), [data-nav="gestacao"]',
                    posicao: 'bottom',
                    icon: 'fa-solid fa-heart-pulse',
                    dica: 'Use o Dia PIG para planejar coberturas de lote sincronizado.'
                },
                {
                    titulo: 'Lançar Cobertura',
                    descricao: 'Primeiro passo da gestação — registro de monta natural ou IA.',
                    seletor: '.btn-cobertura, #btn-cobertura, button:has-text("Cobertura"), button:has-text("Monta"), [data-action="cobertura"]',
                    fallback: { x: '50%', y: '30%' },
                    posicao: 'right',
                    icon: 'fa-solid fa-arrows-spin',
                    actionLabel: 'Abra e confira os campos: fêmea, macho/semen e data'
                },
                {
                    titulo: 'Retornos de cio',
                    descricao: 'Esse KPI mede quantas fêmeas voltaram no cio após a cobertura.',
                    seletor: '.kpi-retornos, .card-retornos, [data-kpi="retorno"], .retorno-kpi, .kpi-card:has-text("Retorno")',
                    fallback: { x: '25%', y: '30%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-repeat',
                    dica: 'O intervalo mínimo para retorno é configurado em Metas.'
                }
            ]
        },
        {
            id: 'manejo_maternidade',
            categoria: 'maternidade',
            categoriaNome: 'Maternidade',
            titulo: 'Fluxo básico de maternidade',
            descricao: 'Transferir fêmeas, registrar partos e desmamar leitegadas.',
            duracao: 55,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Módulo Maternidade',
                    descricao: 'Fêmeas entram por volta do dia 110 de gestação para parir.',
                    seletor: '[href*="maternidade"], .nav-maternidade, #menu-maternidade, li:has(a[href*="maternidade"]), [data-nav="maternidade"]',
                    posicao: 'bottom',
                    icon: 'fa-solid fa-baby',
                    dica: 'Use os alertas de pré-parto (configurável em Metas) para não perder a data!'
                },
                {
                    titulo: 'Registrar Parto',
                    descricao: 'Vivos, natimortos, mumificados e adaptações de leitegada.',
                    seletor: '.btn-parto, #btn-parto, button:has-text("Parto"), button:has-text("Nascimento"), [data-action="parto"]',
                    fallback: { x: '50%', y: '30%' },
                    posicao: 'top',
                    icon: 'fa-solid fa-person-breastfeeding',
                    actionLabel: 'Clique para ver os campos disponíveis'
                }
            ]
        },
        {
            id: 'manejo_morte_femea',
            categoria: 'plantel',
            categoriaNome: 'Plantel',
            titulo: 'Registrar morte ou descarte',
            descricao: 'Lançamento de saída de uma fêmea (morte, descarte, venda).',
            duracao: 40,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Botões de ação de saída',
                    descricao: 'Lance morte, venda ou descarte individual ou em lote.',
                    seletor: '.btn-morte, .btn-descarte, .btn-venda, #btn-morte-femea, button:has-text("Morte"), button:has-text("Descarte"), [data-action="morte"]',
                    fallback: { x: '50%', y: '20%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-skull',
                    dica: 'Sempre selecione a causa correta — ela alimenta os relatórios de mortalidade.'
                },
                {
                    titulo: 'Modal de saída',
                    descricao: 'Preencha data, causa (obrigatório), valor de venda e observações.',
                    seletor: '.modal-morte, #modal-morte-femea, [data-modal="morte-femea"]',
                    fallback: { x: '50%', y: '50%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-file-invoice'
                }
            ]
        },
        {
            id: 'manejo_relatorios',
            categoria: 'analises',
            categoriaNome: 'Análises e Relatórios',
            titulo: 'Gerar relatório de fêmeas',
            descricao: 'Use filtros antes de gerar PDF ou CSV do plantel.',
            duracao: 30,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Aba Análises',
                    descricao: 'KPIs e botões principais de relatórios.',
                    seletor: '.aba-analises, #tab-analises, [data-tab="analises"], #btn-abrir-analises, button:has-text("Análise")',
                    fallback: { x: '50%', y: '15%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-chart-column'
                },
                {
                    titulo: '"Filtrar e gerar" de Fêmeas',
                    descricao: 'Abre a página de filtros ANTES de exibir o relatório.',
                    seletor: 'a[href*="femeas/filtro"], a[href*="femeas/filter"], .btn-gerar-relatorio-femeas',
                    fallback: { x: '50%', y: '35%' },
                    posicao: 'right',
                    icon: 'fa-solid fa-sliders',
                    actionLabel: 'Clique e navegue até a página de filtros'
                }
            ]
        },
        {
            id: 'manejo_dia_pig',
            categoria: 'sistema',
            categoriaNome: 'Sistema',
            titulo: 'Conheça o Dia PIG',
            descricao: 'Calendário circular de 3 dígitos (001-999) que sincroniza toda a granja.',
            duracao: 25,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Dia PIG atual',
                    descricao: 'Todo evento reprodutivo usa esse calendário de 3 dígitos.',
                    seletor: '#dia-pig-atual, .dia-pig-display, .header-dia-pig, #btn-abrir-calendario, .pig-day, [data-dia-pig]',
                    fallback: { x: '85%', y: '8%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-calendar-day',
                    dica: 'Base dos alertas: Pré-Parto, Retornos, Diagnóstico de Gestação (todos configuráveis!).'
                }
            ]
        },
        {
            id: 'manejo_creche_terminacao',
            categoria: 'producao',
            categoriaNome: 'Produção (Creche/Term.)',
            titulo: 'Criar lote de terminação',
            descricao: 'Criar lote, lançar pesagens e registrar saídas (venda/morte).',
            duracao: 50,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Creche / Terminação',
                    descricao: 'Após desmame (±21 dias), os animais vão para creche e depois terminação.',
                    seletor: '[href*="creche"], [href*="terminacao"], .nav-creche, .nav-terminacao, li:has(a[href*="creche"]), li:has(a[href*="terminacao"]), [data-nav="producao"]',
                    fallback: { x: '50%', y: '12%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-warehouse'
                },
                {
                    titulo: 'Novo lote',
                    descricao: 'Sempre crie um lote com data, qtd inicial e identificação.',
                    seletor: '.btn-novo-lote, #btn-novo-lote, button:has-text("Novo Lote"), button:has-text("Novo lote"), [data-action="novo-lote"]',
                    fallback: { x: '50%', y: '30%' },
                    posicao: 'right',
                    icon: 'fa-solid fa-boxes-stacked',
                    actionLabel: 'Clique e explore os campos do modal'
                }
            ]
        },
        {
            id: 'manejo_admin_metas',
            categoria: 'sistema',
            categoriaNome: 'Sistema',
            titulo: 'Configurar metas e critérios',
            descricao: 'O administrador define valores como idade mínima de cobertura.',
            duracao: 35,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Tela Metas e Critérios',
                    descricao: 'Abra Ajustes → Metas para personalizar o sistema sem tocar em código.',
                    seletor: '[href*="ajustes/metas"], .nav-metas, li:has(a[href*="metas"]), [data-nav="metas"]',
                    fallback: { x: '50%', y: '50%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-sliders'
                },
                {
                    titulo: 'Idade mínima de cobertura',
                    descricao: 'Aqui você muda o bloqueio de idade para cobertura (padrão 210 dias).',
                    seletor: 'input[x-model="metas.criterio_cobertura_idade_min_dias"]',
                    fallback: { x: '30%', y: '60%' },
                    posicao: 'right',
                    icon: 'fa-solid fa-shield',
                    actionLabel: 'Mude o valor e clique em Salvar'
                }
            ]
        }
    ];

    const CATEGORIAS_DEFAULT = [
        { id: 'plantel',    nome: 'Plantel',                 icon: 'fa-solid fa-cow',          bg: '#fef3c7', color: '#92400e' },
        { id: 'gestacao',   nome: 'Gestação / Cobertura',    icon: 'fa-solid fa-heart-pulse',   bg: '#fce7f3', color: '#9d174d' },
        { id: 'maternidade',nome: 'Maternidade',             icon: 'fa-solid fa-baby',          bg: '#dbeafe', color: '#1e40af' },
        { id: 'producao',   nome: 'Produção (Creche/Term.)', icon: 'fa-solid fa-warehouse',     bg: '#dcfce7', color: '#166534' },
        { id: 'analises',   nome: 'Análises e Relatórios',   icon: 'fa-solid fa-chart-column',  bg: '#ede9fe', color: '#6d28d9' },
        { id: 'sistema',    nome: 'Sistema (Dia PIG etc.)',  icon: 'fa-solid fa-gears',         bg: '#fee2e2', color: '#991b1b' }
    ];

    const MANEJO_DETECTORS = [
        { id: 'plantel',    nome: 'Plantel Reprodutivo',  keywords: ['dashboard','plantel','femea','macho'] },
        { id: 'gestacao',   nome: 'Gestação / Cobertura', keywords: ['gestacao','cobertura','cio'] },
        { id: 'maternidade',nome: 'Maternidade',          keywords: ['maternidade','parto','desmame','leitegada'] },
        { id: 'producao',   nome: 'Produção (Creche/Term.)', keywords: ['creche','terminacao','lote','pesagem'] },
        { id: 'analises',   nome: 'Análises e Relatórios',keywords: ['analise','analises','relatorio','relatorios','grafico','kpi'] },
        { id: 'sistema',    nome: 'Sistema (Ajustes etc.)',keywords: ['ajuste','ajustes','meta','metas','usuario','perfil','config'] }
    ];

    const LS_KEY = 'flagsmith.tourcenter.v1';

    function loadStorage() {
        try { const raw = localStorage.getItem(LS_KEY); return raw ? JSON.parse(raw) : null; }
        catch (e) { return null; }
    }
    function saveStorage(state) {
        try { localStorage.setItem(LS_KEY, JSON.stringify(state)); } catch(e){}
    }
    function mergeComDefaults(stored) {
        if (!stored) return { tutoriais: JSON.parse(JSON.stringify(DEFAULT_TUTORIAIS)), categoriasAbertas: ['plantel'], filtrarManejo: true };
        const merged = JSON.parse(JSON.stringify(DEFAULT_TUTORIAIS));
        const byId = {};
        (stored.tutoriais || []).forEach(t => byId[t.id] = t);
        merged.forEach(t => {
            if (byId[t.id]) { t.concluido = !!byId[t.id].concluido; t.progresso = byId[t.id].progresso ?? null; }
        });
        return {
            tutoriais: merged,
            categoriasAbertas: Array.isArray(stored.categoriasAbertas) && stored.categoriasAbertas.length
                ? stored.categoriasAbertas : ['plantel'],
            filtrarManejo: stored.filtrarManejo !== false
        };
    }
    function detectarManejoAtual() {
        const path = (window.location.pathname || '').toLowerCase() + ' ' + (window.location.search || '').toLowerCase();
        for (const d of MANEJO_DETECTORS) {
            if (d.keywords.some(k => path.includes(k))) return d;
        }
        return { id: 'plantel', nome: 'Plantel Reprodutivo', keywords: [] };
    }

    return {
        panelOpen: false,
        filtrarManejo: true,
        categoriasAbertas: ['plantel'],
        tutoriais: [],
        tutorialAtivo: null,
        stepIndex: -1,
        tooltipStyle: 'opacity:0;visibility:hidden;left:16px;top:16px;',
        _updateTooltipTimer: null,
        _tourPollTimer: null,
        _manejoInfo: null,

        init() {
            this._manejoInfo = detectarManejoAtual();
            const loaded = mergeComDefaults(loadStorage());
            this.tutoriais = loaded.tutoriais;
            this.filtrarManejo = loaded.filtrarManejo;
            this.categoriasAbertas = loaded.categoriasAbertas && loaded.categoriasAbertas.length
                ? loaded.categoriasAbertas
                : (this.filtrarManejo ? [this._manejoInfo.id] : ['plantel']);

            window.addEventListener('resize', () => this.repositionTooltip());
            window.addEventListener('scroll', () => this.repositionTooltip(), true);
            window.addEventListener('orientationchange', () => this.repositionTooltip());
            window.addEventListener('hashchange', () => { this._manejoInfo = detectarManejoAtual(); });

            // Evento global disparado pelos botões do MENU (header, dropdown, sidebar mobile)
            window.addEventListener('tourcenter:toggle', () => {
                this.$nextTick(() => { this.togglePanel(); });
            });
            window.addEventListener('tourcenter:open', () => {
                this.$nextTick(() => { if (!this.panelOpen) this.panelOpen = true; });
            });
            window.addEventListener('tourcenter:close', () => {
                this.$nextTick(() => { if (this.panelOpen) this.panelOpen = false; });
            });
        },

        persist() {
            saveStorage({
                tutoriais: this.tutoriais.map(t => ({ id: t.id, concluido: t.concluido, progresso: t.progresso })),
                categoriasAbertas: this.categoriasAbertas,
                filtrarManejo: this.filtrarManejo
            });
        },

        get manejoNome() { return this._manejoInfo ? this._manejoInfo.nome : 'Plantel'; },
        get manejoId() { return this._manejoInfo ? this._manejoInfo.id : 'plantel'; },

        get categorias() {
            return CATEGORIAS_DEFAULT.map(c => {
                const list = this.tutoriais.filter(t => t.categoria === c.id);
                const total = list.length || 1;
                const acc = list.reduce((s, t) => {
                    if (t.concluido) return s + 100;
                    if (t.progresso && t.steps && t.steps.length) return s + Math.round((t.progresso / t.steps.length) * 100);
                    return s;
                }, 0);
                return { ...c, progresso: Math.round(acc / total), _qtd: list.length };
            });
        },
        get categoriasVisiveis() {
            const todas = this.categorias;
            if (!this.filtrarManejo) return todas;
            const mid = this.manejoId;
            const diretas = todas.filter(c => c.id === mid);
            // Inclui "sistema" (Dia PIG, metas) como sempre visível, pq ele ajuda qualquer módulo
            const extras = todas.filter(c => c.id === 'sistema');
            const seen = new Set();
            const merged = [];
            for (const c of [...diretas, ...extras]) {
                if (!seen.has(c.id)) { seen.add(c.id); merged.push(c); }
            }
            return merged;
        },
        get totalCategorias() { return CATEGORIAS_DEFAULT.length; },
        get progressoGeral() {
            const total = this.tutoriais.length || 1;
            const acc = this.tutoriais.reduce((s, t) => {
                if (t.concluido) return s + 100;
                if (t.progresso && t.steps && t.steps.length) return s + Math.round((t.progresso / t.steps.length) * 100);
                return s;
            }, 0);
            return Math.round(acc / total);
        },
        get currentStep() {
            if (!this.tutorialAtivo) return null;
            if (this.stepIndex < 0) return null;
            if (!this.tutorialAtivo.steps) return null;
            return this.tutorialAtivo.steps[this.stepIndex] || null;
        },

        togglePanel() { this.panelOpen = !this.panelOpen; },
        toggleCategoria(id) {
            const i = this.categoriasAbertas.indexOf(id);
            i >= 0 ? this.categoriasAbertas.splice(i, 1) : this.categoriasAbertas.push(id);
            this.persist();
        },
        tutoriaisDaCategoria(catId) { return this.tutoriais.filter(t => t.categoria === catId); },
        tutoriaisConcluidosDaCategoria(catId) { return this.tutoriais.filter(t => t.categoria === catId && t.concluido).length; },

        startTutorial(t) {
            this.tutorialAtivo = t;
            const totalSteps = t.steps ? t.steps.length : 0;
            this.stepIndex = (t.progresso && !t.concluido) ? Math.min(Math.max(0, t.progresso - 1), Math.max(0, totalSteps - 1)) : 0;
            this.panelOpen = false;
            t.progresso = this.stepIndex + 1;
            this.persist();
            this.$nextTick(() => { this.repositionTooltip(); this.piscarElementoAlvo(); });
            // Poll de reposicionamento enquanto tutorial estiver aberto (elementos dinâmicos / lazy load)
            if (this._tourPollTimer) clearInterval(this._tourPollTimer);
            this._tourPollTimer = setInterval(() => { this.repositionTooltip(); }, 1200);
        },

        stopTutorial() {
            if (this._tourPollTimer) { clearInterval(this._tourPollTimer); this._tourPollTimer = null; }
            if (this.tutorialAtivo) { this.tutorialAtivo.progresso = this.stepIndex + 1; this.persist(); }
            this.tutorialAtivo = null;
            this.stepIndex = -1;
            const hl = document.getElementById('tour-highlight');
            if (hl) hl.style.cssText = '';
            this.tooltipStyle = 'opacity:0;visibility:hidden;';
        },

        finishTutorial() {
            if (!this.tutorialAtivo) return;
            this.tutorialAtivo.concluido = true;
            this.tutorialAtivo.progresso = this.tutorialAtivo.steps ? this.tutorialAtivo.steps.length : 0;
            this.persist();
            if (typeof window.toast === 'function') window.toast('🏆 Tutorial concluído: ' + this.tutorialAtivo.titulo, 'success');
            this.stopTutorial();
        },

        resetarTutorial(t) { t.concluido = false; t.progresso = null; this.persist(); if (typeof window.toast === 'function') window.toast('Tutorial "'+t.titulo+'" resetado.', 'info'); },
        resetarTodos() {
            if (!confirm('Resetar progresso de TODOS os tutoriais?')) return;
            this.tutoriais.forEach(t => { t.concluido = false; t.progresso = null; });
            this.persist();
            if (typeof window.toast === 'function') window.toast('Todos os tutoriais foram resetados.', 'info');
        },

        nextStep() {
            if (!this.tutorialAtivo || !this.tutorialAtivo.steps) return;
            if (this.stepIndex < this.tutorialAtivo.steps.length - 1) {
                this.stepIndex++;
                this.tutorialAtivo.progresso = this.stepIndex + 1;
                this.persist();
                this.$nextTick(() => { this.repositionTooltip(); this.piscarElementoAlvo(); });
            }
        },
        prevStep() {
            if (this.stepIndex > 0) {
                this.stepIndex--;
                this.$nextTick(() => { this.repositionTooltip(); this.piscarElementoAlvo(); });
            }
        },
        piscarElementoAlvo() {
            const el = this.findTarget(this.currentStep ? this.currentStep.seletor : null);
            if (el && typeof el.scrollIntoView === 'function') {
                try {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                } catch(e){}
                try {
                    const orig = el.style.boxShadow;
                    el.style.boxShadow = '0 0 0 4px rgba(251,191,36,0.55), 0 0 30px 4px rgba(251,191,36,0.35)';
                    setTimeout(() => { try { el.style.boxShadow = orig; } catch(e){} }, 1200);
                } catch(e){}
            }
        },
        findTarget(sel) {
            if (!sel) return null;
            const selectors = sel.split(',').map(s => s.trim()).filter(Boolean);
            for (const s of selectors) {
                try {
                    const els = document.querySelectorAll(s);
                    for (const el of els) {
                        if (!el) continue;
                        // Preferir elementos VISÍVEIS / com tamanho não zero
                        const cs = el.getClientRects();
                        if (cs && cs.length > 0) return el;
                        const r = el.getBoundingClientRect();
                        if (r && r.width > 2 && r.height > 2) return el;
                    }
                    // Fallback: retorna o primeiro mesmo que invisível
                    const f = document.querySelector(s);
                    if (f) return f;
                } catch(e){}
            }
            return null;
        },
        repositionTooltip() {
            if (this.stepIndex < 0 || !this.tutorialAtivo || !this.currentStep) {
                this.tooltipStyle = 'opacity:0;visibility:hidden;';
                return;
            }
            clearTimeout(this._updateTooltipTimer);
            this._updateTooltipTimer = setTimeout(() => {
                const hl = document.getElementById('tour-highlight');
                const el = this.findTarget(this.currentStep.seletor);
                let rect;
                if (el) {
                    const r = el.getBoundingClientRect();
                    // Padding maior para enquadrar BOTÕES (em vez de 8 agora é 14px)
                    const pad = 14;
                    rect = {
                        top: Math.max(4, r.top - pad),
                        left: Math.max(4, r.left - pad),
                        width: r.width + pad * 2,
                        height: r.height + pad * 2
                    };
                } else if (this.currentStep.fallback) {
                    const fb = this.currentStep.fallback;
                    const w = 260, h = 96;
                    const parseV = (v, max) => typeof v === 'string' && v.includes('%') ? (max * parseFloat(v) / 100) : (typeof v === 'number' ? v : max/2);
                    rect = {
                        width: w,
                        height: h,
                        top: Math.max(16, parseV(fb.y, window.innerHeight) - h/2),
                        left: Math.max(16, parseV(fb.x, window.innerWidth) - w/2)
                    };
                } else {
                    rect = {
                        top: Math.max(20, window.innerHeight/2 - 70),
                        left: Math.max(20, window.innerWidth/2 - 140),
                        width: 280,
                        height: 140
                    };
                }
                if (hl) hl.style.cssText = `top:${rect.top}px;left:${rect.left}px;width:${rect.width}px;height:${rect.height}px;`;

                const isMobile = window.innerWidth < 640;
                const TOOLTIP_W = isMobile ? Math.min(window.innerWidth - 20, 420) : Math.min(window.innerWidth - 32, 400);
                const TOOLTIP_H = isMobile ? 420 : 360;
                let top, left;
                const pos = this.currentStep.posicao || 'auto';
                const margin = 14;

                const fitsBelow = (window.innerHeight - (rect.top + rect.height)) > TOOLTIP_H + margin + 40;
                const fitsAbove = rect.top > TOOLTIP_H + margin + 40;

                if (pos === 'top' || (pos === 'auto' && !fitsBelow && fitsAbove)) {
                    top = rect.top - TOOLTIP_H - margin;
                    left = isMobile ? (window.innerWidth - TOOLTIP_W) / 2 : Math.max(16, Math.min(window.innerWidth - TOOLTIP_W - 16, rect.left + rect.width/2 - TOOLTIP_W/2));
                } else if (pos === 'left') {
                    left = rect.left - TOOLTIP_W - margin;
                    if (left < 16 || isMobile) { left = Math.max(16, rect.left + rect.width + margin); if (left + TOOLTIP_W > window.innerWidth - 16) left = (window.innerWidth - TOOLTIP_W)/2; }
                    top = Math.max(16, Math.min(window.innerHeight - TOOLTIP_H - 16, rect.top + rect.height/2 - TOOLTIP_H/2));
                } else if (pos === 'right' && !isMobile) {
                    left = rect.left + rect.width + margin;
                    if (left + TOOLTIP_W > window.innerWidth - 16) {
                        left = Math.max(16, rect.left - TOOLTIP_W - margin);
                    }
                    top = Math.max(16, Math.min(window.innerHeight - TOOLTIP_H - 16, rect.top + rect.height/2 - TOOLTIP_H/2));
                } else {
                    top = rect.top + rect.height + margin;
                    if (top + TOOLTIP_H > window.innerHeight - 16) {
                        top = Math.max(16, window.innerHeight - TOOLTIP_H - 32);
                    }
                    left = isMobile ? (window.innerWidth - TOOLTIP_W) / 2 : Math.max(16, Math.min(window.innerWidth - TOOLTIP_W - 16, rect.left + rect.width/2 - TOOLTIP_W/2));
                }
                this.tooltipStyle = `top:${Math.round(top)}px;left:${Math.round(left)}px;width:${Math.round(TOOLTIP_W)}px;`;
            }, 90);
        }
    };
}
</script>
@else
    {{-- Tour Center desabilitado (em desenvolvimento). Altere $TOUR_CENTER_LIBERADO=true no topo deste arquivo para ativar. --}}
@endif
