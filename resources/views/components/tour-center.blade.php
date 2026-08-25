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
$TOUR_CENTER_LIBERADO = true;
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
                                <i class="fa-solid fa-location-dot text-[10px]"></i>
                                <span x-text="'Tutoriais de: ' + manejoNome"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="panelOpen = false" class="shrink-0 w-9 h-9 rounded-xl border border-amber-200/80 bg-white/80 text-amber-700 hover:bg-amber-100 hover:text-amber-900 flex items-center justify-center transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="px-3 sm:px-4 pt-3 shrink-0">
                <div class="rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 px-3.5 py-3 flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 shrink-0"></i>
                    <div class="flex-1 text-[12px] leading-relaxed">
                        <strong class="font-black block">Tour Center em desenvolvimento</strong>
                        <span class="text-amber-800/90">
                            Alguns tutoriais podem estar desatualizados, com passos incorretos ou destacar elementos errados. Use-os como referência e, se necessário, consulte a equipe.
                        </span>
                    </div>
                </div>
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
                    <div class="font-bold text-slate-700">Sem tutoriais para este manejo ainda</div>
                    <div class="text-[12px] text-slate-500 mt-1">Novos guias serão adicionados em breve.</div>
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

    {{-- ================ MÁSCARA DE CLIQUES (BLOQUEIA TUDO FORA DO SPOTLIGHT) ================ --}}
    <template x-if="stepIndex >= 0 && tutorialAtivo">
        <div id="tour-mask"
             class="fixed inset-0 z-[9997] block"
             @click.capture="onTourMaskClick($event)"
             @keydown.capture.prevent.stop="_onTourMaskKeydown($event)"
             role="presentation" aria-hidden="true">
        </div>
    </template>

    {{-- ================ HIGHLIGHT (SOBREPÕEM DURANTE TUTORIAL) — box-shadow gigante já funciona como overlay fullscreen ================ --}}
    <template x-if="stepIndex >= 0">
        <div id="tour-highlight"
             class="fixed z-[9998] pointer-events-none rounded-2xl ring-[5px] ring-amber-400 ring-offset-0 shadow-[0_0_0_9999px_rgba(2,6,23,0.78)] shadow-amber-400/15 transition-[top,left,width,height,border-radius] duration-250 ease-out"></div>
    </template>

    {{-- ================ TOOLTIP DO PASSO (mobile: anexado NO TOPO ou NA BASE da tela — NUNCA cobre o spotlight) ================ --}}
    <template x-if="stepIndex >= 0 && tutorialAtivo">
        <div id="tour-tooltip-container" class="fixed z-[9999] inset-x-0 sm:inset-auto pointer-events-none"
             :style="containerStyleForTooltip">
            <div id="tour-tooltip" class="w-full sm:w-[min(92vw,400px)] sm:max-w-[400px] max-w-full mx-auto sm:mx-0 pointer-events-auto
                        rounded-none sm:rounded-3xl border border-amber-100 sm:border-amber-100 bg-white
                        shadow-[0_30px_80px_-20px_rgba(2,6,23,0.38)] sm:shadow-2xl sm:shadow-slate-900/30
                        overflow-hidden pb-[max(0px,env(safe-area-inset-bottom))] flex flex-col
                        max-h-[var(--tt-max-h,62svh)]
                        sm:max-h-[min(82svh,620px)]
                        translate-y-0 translate-x-0 transition-transform duration-200 ease-out">
                <div class="relative rounded-none sm:rounded-3xl overflow-hidden w-full flex flex-col h-full">
                    <div class="px-3.5 sm:px-5 pt-3 pb-2.5 sm:py-3 bg-gradient-to-r from-amber-50 via-amber-50/60 to-white border-b border-amber-100/80 flex items-start justify-between gap-3 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-600 to-amber-800 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <i :class="(currentStep && currentStep.icon) ? currentStep.icon : 'fa-solid fa-circle-info'"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] sm:text-[10.5px] font-black uppercase tracking-wider text-amber-700 leading-none mt-0.5">
                                    Passo <span x-text="stepIndex + 1"></span>/<span x-text="(tutorialAtivo && tutorialAtivo.steps) ? tutorialAtivo.steps.length : 0"></span>
                                    <span class="font-normal normal-case tracking-normal text-amber-700/70" x-text="(tutorialAtivo && tutorialAtivo.categoriaNome) ? (' · ' + tutorialAtivo.categoriaNome) : ''"></span>
                                </div>
                                <div class="text-[15px] sm:text-sm font-black text-slate-900 mt-0.5 leading-snug"
                                     x-text="currentStep ? currentStep.titulo : ''"></div>
                            </div>
                        </div>
                        <button @click="stopTutorial()"
                                class="shrink-0 w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-100 flex items-center justify-center transition-all" title="Encerrar tour">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="px-3.5 sm:px-5 py-3 sm:py-4 space-y-2.5 sm:space-y-3 overflow-y-auto -mr-1 pr-1 min-h-0">
                        <p class="text-[13.5px] sm:text-[14px] leading-relaxed text-slate-700"
                           x-text="currentStep ? currentStep.descricao : ''"></p>
                        <template x-if="currentStep && currentStep.dica">
                            <div class="rounded-2xl bg-amber-50 border border-amber-100 p-2.5 sm:p-3 text-[12.5px] sm:text-[13px] text-amber-900 leading-relaxed flex items-start gap-2 sm:gap-2.5 shrink-0">
                                <i class="fa-solid fa-lightbulb text-amber-500 shrink-0 mt-0.5 text-[13px] sm:text-base"></i>
                                <div x-text="currentStep.dica"></div>
                            </div>
                        </template>
                        <template x-if="currentStep && currentStep.actionLabel">
                            <div class="rounded-2xl bg-primary-50/80 border border-primary-100 p-2.5 sm:p-3 text-[12.5px] sm:text-[13px] text-primary-900 leading-relaxed flex items-start gap-2 sm:gap-2.5 shrink-0">
                                <i class="fa-solid fa-hand-pointer text-primary-600 shrink-0 mt-0.5 text-[13px] sm:text-base"></i>
                                <div>
                                    <strong class="font-black">Faça agora:</strong>
                                    <span x-text="' ' + currentStep.actionLabel"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-3.5 sm:px-5 py-2.5 sm:py-3 bg-slate-50/80 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3 shrink-0 pb-[max(10px,env(safe-area-inset-bottom))]">
                        <button type="button" @click="prevStep()"
                            :disabled="stepIndex === 0"
                            class="sm:w-auto w-full min-h-[42px] sm:min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-3.5 sm:px-4 py-2 sm:py-2.5 text-[13.5px] sm:text-sm font-bold border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 hover:text-slate-800 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-slate-600">
                            <i class="fa-solid fa-arrow-left"></i>
                            Anterior
                        </button>

                        <div class="flex items-center justify-center gap-1.5 order-3 sm:order-2 w-full sm:w-auto min-w-0">
                            <template x-for="(_, i) in (tutorialAtivo && tutorialAtivo.steps ? tutorialAtivo.steps : [])" :key="i">
                                <div class="h-1.5 rounded-full transition-all duration-200 w-3.5 sm:w-4 flex-none"
                                     :class="i < stepIndex ? 'bg-emerald-400' : (i === stepIndex ? 'w-5 sm:w-6 bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-slate-300')"></div>
                            </template>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 order-2 sm:order-3 min-w-0 flex-1 sm:flex-none sm:min-w-[150px] sm:justify-end w-full sm:w-auto">
                            <template x-if="tutorialAtivo && tutorialAtivo.steps && stepIndex >= tutorialAtivo.steps.length - 1">
                                <button type="button" @click="finishTutorial()"
                                    class="sm:w-auto w-full min-h-[42px] sm:min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-3.5 sm:px-4 py-2 sm:py-2.5 text-[13.5px] sm:text-sm font-bold bg-gradient-to-b from-emerald-500 to-emerald-600 text-white border border-emerald-500 shadow-sm shadow-emerald-900/20 active:scale-[0.98] transition-all shrink-0">
                                    <i class="fa-solid fa-check"></i>
                                    Concluir
                                </button>
                            </template>
                            <template x-if="tutorialAtivo && tutorialAtivo.steps && stepIndex < tutorialAtivo.steps.length - 1">
                                <button type="button" @click="nextStep()"
                                    class="sm:w-auto w-full min-h-[42px] sm:min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl px-3.5 sm:px-4 py-2 sm:py-2.5 text-[13.5px] sm:text-sm font-bold bg-gradient-to-b from-amber-600 to-amber-700 text-white border border-amber-600 shadow-sm shadow-amber-900/20 active:scale-[0.98] transition-all shrink-0">
                                    Próximo
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </template>
                        </div>
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
            descricao: 'Aprenda a lançar uma Leitoa, Matriz vazia ou Matriz gestante no Plantel Reprodutivo.',
            duracao: 60,
            concluido: false,
            progresso: null,
            steps: [
                {
                    titulo: 'Plantel Reprodutivo',
                    descricao: 'Este é o módulo central. Gerencia todas as fêmeas e machos reprodutores da granja.',
                    seletor: '[data-tour="plantel-reprodutivo"], main h2:has-text("Plantel Reprodutivo"), .rounded-xl > div > h2:has-text("Plantel Reprodutivo"), h2.text-2xl.font-bold, [data-page-title], .module-title, #menu-plantel, li:has(a[href*="plantel/femeas"]), [data-nav="plantel"]',
                    fallback: { x: '50%', y: '14%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-cow',
                    dica: 'Sempre comece pelo Plantel. Tudo (gestação, maternidade) gira em torno dele!'
                },
                {
                    titulo: 'Visão Geral do Plantel',
                    descricao: 'Matrizes ativas, machos, inconsistências e movimentações.',
                    seletor: '[data-tour="visao-geral-plantel"], nav.flex.justify-center, .dashboard-tabs, .module-tabs, nav > button:has-text("Visão Geral"), .rounded-xl.shadow-sm, .grid.grid-cols-1 .rounded-xl, h2:has-text("Visão geral")',
                    fallback: { x: '50%', y: '28%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-compass',
                    dica: 'As abas superiores organizam tudo: Visão Geral, Lançamentos, Acompanhamento, Análise e Relatórios.'
                },
                {
                    titulo: 'Aba Lançamentos',
                    descricao: 'Clique aqui para abrir os formulários de lançamento de fêmeas no plantel.',
                    seletor: '[data-tour="aba-lancamentos"], nav > button:has-text("lancamentos"), .dashboard-tabs button:has-text("Lançamentos"), nav button:has(@click, "lancamentos"), button[onclick*="tab = \'lancamentos\'"], button[x-on\\:click*="tab = \'lancamentos\'"]',
                    fallback: { x: '50%', y: '10%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-rocket',
                    actionLabel: 'Clique na aba Lançamentos para abrir os formulários.'
                },
                {
                    titulo: 'Lançamento de femeas:',
                    descricao: 'Os três botões Leitoa / Matriz vazia / Matriz gestante só aparecem quando você selecionou Fêmeas e Compra na seção Lançamentos acima.',
                    seletor: '[data-tour="femeas"], [data-tour="movimento-compra"], div.flex.justify-center.items-center.gap-2 button:has-text("Fêmeas"), div.bg-gray-100 button:has(.fa-piggy-bank):has-text("Fêmeas"), [@click*="item = \'femeas\'"]',
                    fallback: { x: '50%', y: '36%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-layer-group',
                    dica: 'Verifique também se a aba "Compra" está selecionada (logo abaixo dos itens Fêmeas/Machos/Sêmen).'
                },
                {
                    titulo: 'Leitoa',
                    descricao: 'Use este botão para cadastrar fêmeas jovens selecionadas para substituir matrizes mais velhas ou descartadas do rebanho.',
                    seletor: '[data-tour="leitoa"], button:has(> span > .bg-pink-50), button:has-text("leitoa"), [@click*="openNovoForm(\'leitoa\')"], button[onclick*="openNovoForm(\'leitoa\')"]',
                    fallback: { x: '28%', y: '40%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-piggy-bank',
                    dica: 'Leitoa = fêmea suína jovem, escolhida para renovar o plantel reprodutivo.',
                    actionLabel: 'Clique para abrir o lançamento de Leitoa.'
                },
                {
                    titulo: 'Lançar Matriz vazia',
                    descricao: 'Use este botão para cadastrar fêmeas adultas que se encontram no intervalo entre o desmame dos leitões e uma nova gestação.',
                    seletor: '[data-tour="matriz-vazia"], button:has(> span > .bg-sky-50), button:has-text("matriz_vazia"), [@click*="openNovoForm(\'matriz_vazia\')"], button[onclick*="openNovoForm(\'matriz_vazia\')"]',
                    fallback: { x: '50%', y: '40%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-piggy-bank',
                    dica: 'Matriz vazia = porca adulta que desmamou recentemente e está aguardando nova cobertura.',
                    actionLabel: 'Clique para abrir o lançamento de Matriz vazia.'
                },
                {
                    titulo: 'Lançar Matriz gestante',
                    descricao: 'Use este botão para cadastrar fêmeas adultas já gestantes — confirmadas após a cobertura.',
                    seletor: '[data-tour="matriz-gestante"], button:has(> span > .bg-violet-50), button:has-text("matriz_gestante"), [@click*="openNovoForm(\'matriz_gestante\')"], button[onclick*="openNovoForm(\'matriz_gestante\')"]',
                    fallback: { x: '72%', y: '40%' },
                    posicao: 'bottom',
                    icon: 'fa-solid fa-piggy-bank',
                    dica: 'Matriz gestante = porca adulta prenha, aguardando o parto.',
                    actionLabel: 'Clique para abrir o lançamento de Matriz gestante.'
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
        if (!stored) return { tutoriais: JSON.parse(JSON.stringify(DEFAULT_TUTORIAIS)), categoriasAbertas: ['plantel'] };
        const merged = JSON.parse(JSON.stringify(DEFAULT_TUTORIAIS));
        const byId = {};
        (stored.tutoriais || []).forEach(t => byId[t.id] = t);
        merged.forEach(t => {
            if (byId[t.id]) { t.concluido = !!byId[t.id].concluido; t.progresso = byId[t.id].progresso ?? null; }
        });
        return {
            tutoriais: merged,
            categoriasAbertas: Array.isArray(stored.categoriasAbertas) && stored.categoriasAbertas.length
                ? stored.categoriasAbertas : ['plantel']
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
        categoriasAbertas: ['plantel'],
        tutoriais: [],
        tutorialAtivo: null,
        stepIndex: -1,
        tooltipStyle: 'opacity:0;visibility:hidden;left:16px;top:16px;',
        containerStyleForTooltip: 'opacity:0;visibility:hidden;top:0;left:0;width:100%;',
        _updatePositionTimer: null,
        _tourPollTimer: null,
        _mutationObserver: null,
        _resizeObserver: null,
        _scrollStableTimer: null,
        _isScrolling: false,
        _isScrollingByEvent: false,
        _lastScrollX: 0,
        _lastScrollY: 0,
        _mutationDebounceTimer: null,
        _resizeDebounceTimer: null,
        _scrollContainersListeners: null,
        _remeasureTimer: null,
        _manejoInfo: null,

        init() {
            this._manejoInfo = detectarManejoAtual();
            const loaded = mergeComDefaults(loadStorage());
            this.tutoriais = loaded.tutoriais;
            this.categoriasAbertas = loaded.categoriasAbertas && loaded.categoriasAbertas.length
                ? loaded.categoriasAbertas
                : [this._manejoInfo.id];
            this._onScrollChangeCapture = this._onScrollChangeCapture || (() => {
                this._isScrollingByEvent = true;
                this._onScrollChange();
                clearTimeout(this._scrollByEventTimer);
                this._scrollByEventTimer = setTimeout(() => { this._isScrollingByEvent = false; }, 260);
            });
            this._throttledUpdateTourPosition = this._throttle(() => { this.updateTourPosition(); }, 40);

            window.addEventListener('resize', () => this._onWindowResize());
            window.addEventListener('orientationchange', () => this._onWindowResize());
            window.addEventListener('scroll', () => this._onScrollChangeCapture(), true);
            window.addEventListener('hashchange', () => { this._manejoInfo = detectarManejoAtual(); });

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

        _throttle(fn, wait) {
            let last = 0, pendingTimer = null, lastCtx = null, lastArgs = null;
            const exec = () => {
                last = Date.now();
                pendingTimer = null;
                fn.apply(lastCtx || null, lastArgs || []);
            };
            return function() {
                const now = Date.now();
                const remaining = wait - (now - last);
                lastCtx = this; lastArgs = arguments;
                if (remaining <= 0) {
                    if (pendingTimer) { clearTimeout(pendingTimer); pendingTimer = null; }
                    last = now;
                    fn.apply(lastCtx, lastArgs);
                } else if (!pendingTimer) {
                    pendingTimer = setTimeout(exec, remaining);
                }
            };
        },

        _onWindowResize() {
            if (this._resizeDebounceTimer) clearTimeout(this._resizeDebounceTimer);
            this._resizeDebounceTimer = setTimeout(() => {
                this._remeasureCount = 0;
                this.updateTourPosition();
            }, 60);
        },

        _onScrollChange() {
            this._isScrolling = true;
            if (this._scrollStableTimer) clearTimeout(this._scrollStableTimer);
            this._scrollStableTimer = setTimeout(() => {
                if (Math.abs(window.scrollX - this._lastScrollX) < 0.5 && Math.abs(window.scrollY - this._lastScrollY) < 0.5) {
                    this._isScrolling = false;
                    this._remeasureCount = 0;
                    this.updateTourPosition();
                }
            }, 100);
            this._lastScrollX = window.scrollX;
            this._lastScrollY = window.scrollY;
        },

        _startObservingDOM() {
            if (this._mutationObserver) return;
            const onMutationHit = () => {
                if (this._mutationDebounceTimer) clearTimeout(this._mutationDebounceTimer);
                this._mutationDebounceTimer = setTimeout(() => {
                    const tooltip = document.getElementById('tour-tooltip');
                    if (tooltip && this._resizeObserver) { try { this._resizeObserver.observe(tooltip); } catch(e){} }
                    const hl = document.getElementById('tour-highlight');
                    if (hl && this._resizeObserver) { try { this._resizeObserver.observe(hl); } catch(e){} }
                    this._remeasureCount = 0;
                    this.updateTourPosition();
                }, 40);
            };
            try {
                this._mutationObserver = new MutationObserver((mutations) => {
                    for (const m of mutations) {
                        if (m.type === 'childList' || m.type === 'attributes') { onMutationHit(); return; }
                    }
                });
                this._mutationObserver.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style', 'class', 'open', 'aria-hidden', 'aria-expanded', 'data-open', 'x-show', 'x-transition', 'x-data']
                });
            } catch (e) {}
            try {
                this._resizeObserver = new ResizeObserver(() => {
                    if (this._resizeDebounceTimer) clearTimeout(this._resizeDebounceTimer);
                    this._resizeDebounceTimer = setTimeout(() => {
                        this._remeasureCount = 0;
                        this.updateTourPosition();
                    }, 30);
                });
                this._resizeObserver.observe(document.body);
                const root = this.$el && this.$el.nodeType === 1 ? this.$el : document.querySelector('[x-data="tourCenter()"]');
                if (root) { try { this._resizeObserver.observe(root); } catch(e){} }
            } catch (e) {}
            this._installScrollContainersListeners();
        },

        _stopObservingDOM() {
            if (this._mutationObserver) { try { this._mutationObserver.disconnect(); } catch(e){} this._mutationObserver = null; }
            if (this._resizeObserver) { try { this._resizeObserver.disconnect(); } catch(e){} this._resizeObserver = null; }
            if (this._scrollContainersListeners) {
                this._scrollContainersListeners.forEach(({ el, fn }) => {
                    try { el.removeEventListener('scroll', fn, true); } catch(e){}
                });
                this._scrollContainersListeners = null;
            }
            if (this._mutationDebounceTimer) { clearTimeout(this._mutationDebounceTimer); this._mutationDebounceTimer = null; }
            if (this._resizeDebounceTimer) { clearTimeout(this._resizeDebounceTimer); this._resizeDebounceTimer = null; }
            if (this._scrollByEventTimer) { clearTimeout(this._scrollByEventTimer); this._scrollByEventTimer = null; }
            if (this._remeasureTimer) { clearTimeout(this._remeasureTimer); this._remeasureTimer = null; }
        },

        _installScrollContainersListeners() {
            if (this._scrollContainersListeners) return;
            this._scrollContainersListeners = [];
            try {
                const candidates = document.querySelectorAll('aside, nav, .modal, [role="dialog"], main, .scroll-y, [x-data] > div');
                for (const el of candidates) {
                    if (el.nodeType !== 1) continue;
                    try {
                        const s = window.getComputedStyle(el);
                        const ovY = (s.overflowY || s.overflow || '').toLowerCase();
                        if (/auto|scroll|overlay/.test(ovY) && el.scrollHeight > el.clientHeight + 8) {
                            const fn = () => this._onScrollChangeCapture();
                            el.addEventListener('scroll', fn, true);
                            this._scrollContainersListeners.push({ el, fn });
                        }
                    } catch(e){}
                }
            } catch(e){}
        },

        _isElementVisible(el) {
            if (!el) return false;
            if (!el.isConnected) return false;
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
            const r = el.getBoundingClientRect();
            if (r.width < 2 || r.height < 2) return false;
            const vw = window.innerWidth, vh = window.innerHeight;
            return !(r.right < 0 || r.bottom < 0 || r.left > vw || r.top > vh);
        },

        _waitForStablePosition(el, maxAttempts) {
            maxAttempts = maxAttempts || 18;
            return new Promise((resolve) => {
                let attempts = 0;
                let lastKey = null;
                let stableCount = 0;
                const check = () => {
                    attempts++;
                    const r = el && el.isConnected ? el.getBoundingClientRect() : null;
                    const key = r
                        ? `${r.top.toFixed(1)}|${r.left.toFixed(1)}|${r.width.toFixed(1)}|${r.height.toFixed(1)}|${window.scrollX.toFixed(1)}|${window.scrollY.toFixed(1)}`
                        : `null|${window.scrollX.toFixed(1)}|${window.scrollY.toFixed(1)}`;
                    if (lastKey === key && !this._isScrolling) {
                        stableCount++;
                    } else {
                        stableCount = 0;
                        lastKey = key;
                    }
                    if (stableCount >= 3 || attempts >= maxAttempts) {
                        resolve();
                    } else {
                        setTimeout(check, 24);
                    }
                };
                check();
            });
        },

        _getScrollContainers(el) {
            const list = [];
            if (!el) return list;
            let cur = el.nodeType === 1 ? el : el.parentElement;
            const seen = new Set();
            let guard = 0;
            while (cur && guard < 40) {
                guard++;
                if (seen.has(cur)) break;
                seen.add(cur);
                if (cur.nodeType === 1) {
                    try {
                        const s = window.getComputedStyle(cur);
                        const ovY = (s.overflowY || s.overflow || '').toLowerCase();
                        const ovX = (s.overflowX || s.overflow || '').toLowerCase();
                        const isScroll = /auto|scroll|overlay/.test(ovY) || /auto|scroll|overlay/.test(ovX);
                        if (isScroll && cur.scrollHeight > (cur.clientHeight + 4)) list.push(cur);
                    } catch(e){}
                }
                if (cur === document.body || cur === document.documentElement) break;
                cur = cur.parentElement;
            }
            return list;
        },

        _scrollIntoViewIfNeeded(el) {
            return new Promise((resolve) => {
                if (!el) { resolve(); return; }
                const isMobile = window.innerWidth < 640;
                let r = el.getBoundingClientRect();
                let block = 'center';
                if (isMobile) {
                    const topHalf = r.top + r.height / 2;
                    const vh = window.innerHeight;
                    if (topHalf < vh * 0.5) block = 'start'; else block = 'end';
                }
                const margin = isMobile ? 160 : 48;
                const isOutside = (
                    r.top < margin ||
                    r.left < 16 ||
                    r.right > window.innerWidth - 16 ||
                    r.bottom > window.innerHeight - margin
                );
                if (!isOutside && isMobile) {
                    r = el.getBoundingClientRect();
                    const topHalf2 = r.top + r.height / 2;
                    const vh = window.innerHeight;
                    const reserveTop = Math.max(360, Math.min(480, Math.floor(vh * 0.48)));
                    const reserveBottom = reserveTop;
                    if (r.top < reserveTop || (vh - r.bottom) < reserveBottom) {
                        block = topHalf2 < vh * 0.5 ? 'start' : 'end';
                    } else { resolve(); return; }
                } else if (!isOutside) { resolve(); return; }
                this._isScrolling = true;
                let resolved = false;
                const finish = () => {
                    if (resolved) return;
                    resolved = true;
                    setTimeout(() => {
                        this._isScrolling = false;
                        resolve();
                    }, 120);
                };
                const customContainers = this._getScrollContainers(el);
                customContainers.forEach(c => {
                    try { c.addEventListener('scroll', this._onScrollChangeCapture, true); } catch(e){}
                });
                try {
                    const opts = { behavior: 'smooth', block: block, inline: 'nearest' };
                    if (isMobile && (r.bottom - r.top) < 220) {
                        try {
                            const desiredGap = Math.max(380, Math.min(520, Math.floor(window.innerHeight * 0.5)));
                            const abs = el.getBoundingClientRect();
                            const elCenter = abs.top + abs.height / 2;
                            const vh = window.innerHeight;
                            const goTop = elCenter < vh * 0.5;
                            if (goTop) {
                                try { window.scrollBy({ top: Math.max(-800, -Math.max(0, abs.top) - 96), behavior: 'smooth' }); } catch(e){ el.scrollIntoView(opts); }
                            } else {
                                try { window.scrollBy({ top: Math.max(800, (vh - abs.bottom) + 96 - (vh - desiredGap)), behavior: 'smooth' }); } catch(e){ el.scrollIntoView(opts); }
                            }
                            setTimeout(() => { try { el.scrollIntoView({ behavior: 'smooth', block: (goTop ? 'start' : 'end'), inline: 'nearest' }); } catch(e){} }, 120);
                        } catch(e) {
                            el.scrollIntoView(opts);
                        }
                    } else {
                        el.scrollIntoView(opts);
                    }
                } catch(e) {
                    finish(); return;
                }
                let lastSx = window.scrollX, lastSy = window.scrollY;
                let stableFrames = 0;
                let iter = 0;
                const MAX_ITER = 120;
                const check = () => {
                    if (resolved) return;
                    iter++;
                    const sx = window.scrollX, sy = window.scrollY;
                    const customMoving = customContainers.some(c => {
                        try {
                            const k = c.__tsp = c.__tsp || { t: 0, l: 0 };
                            const ok = Math.abs((c.scrollTop || 0) - k.t) < 0.4 && Math.abs((c.scrollLeft || 0) - k.l) < 0.4;
                            k.t = c.scrollTop || 0;
                            k.l = c.scrollLeft || 0;
                            return !ok;
                        } catch(e) { return false; }
                    });
                    if (Math.abs(sx - lastSx) < 0.3 && Math.abs(sy - lastSy) < 0.3 && !customMoving && !this._isScrollingByEvent) {
                        stableFrames++;
                    } else {
                        stableFrames = 0;
                    }
                    lastSx = sx; lastSy = sy;
                    if (stableFrames >= 6 || iter >= MAX_ITER) {
                        customContainers.forEach(c => {
                            try { c.removeEventListener('scroll', this._onScrollChangeCapture, true); } catch(e){}
                        });
                        finish();
                    } else {
                        requestAnimationFrame(check);
                    }
                };
                setTimeout(check, 80);
                setTimeout(() => {
                    customContainers.forEach(c => {
                        try { c.removeEventListener('scroll', this._onScrollChangeCapture, true); } catch(e){}
                    });
                    finish();
                }, 2800);
            });
        },

        persist() {
            saveStorage({
                tutoriais: this.tutoriais.map(t => ({ id: t.id, concluido: t.concluido, progresso: t.progresso })),
                categoriasAbertas: this.categoriasAbertas
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
            const mid = this.manejoId;
            return todas.filter(c => c.id === mid);
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
            this._startObservingDOM();
            this.$nextTick(() => { this._prepareStep(); });
            if (this._tourPollTimer) clearInterval(this._tourPollTimer);
            this._tourPollTimer = setInterval(() => { this.updateTourPosition(); }, 300);
        },

        stopTutorial() {
            if (this._tourPollTimer) { clearInterval(this._tourPollTimer); this._tourPollTimer = null; }
            this._stopObservingDOM();
            if (this._updatePositionTimer) { clearTimeout(this._updatePositionTimer); this._updatePositionTimer = null; }
            if (this.tutorialAtivo) { this.tutorialAtivo.progresso = this.stepIndex + 1; this.persist(); }
            this.tutorialAtivo = null;
            this.stepIndex = -1;
            const hl = document.getElementById('tour-highlight');
            if (hl) hl.style.cssText = '';
            this.tooltipStyle = 'opacity:0;visibility:hidden;';
            this.containerStyleForTooltip = 'opacity:0;visibility:hidden;';
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
                this.$nextTick(() => { this._prepareStep(); });
            }
        },
        prevStep() {
            if (this.stepIndex > 0) {
                this.stepIndex--;
                this.$nextTick(() => { this._prepareStep(); });
            }
        },

        async _prepareStep() {
            const step = this.currentStep;
            let el = this.findTargetForStep(step);
            let tries = 0;
            while (!el && tries < 3) {
                tries++;
                await new Promise(res => setTimeout(res, 180 * tries));
                el = this.findTargetForStep(step);
            }
            this._applyHighlightPulse(el);
            await this._scrollIntoViewIfNeeded(el);
            await this._waitForStablePosition(el, 25);
            this.updateTourPosition();
        },

        _applyHighlightPulse(el) {
            if (!el) return;
            try {
                const orig = el.style.boxShadow;
                el.style.boxShadow = '0 0 0 4px rgba(251,191,36,0.55), 0 0 30px 4px rgba(251,191,36,0.35)';
                setTimeout(() => { try { el.style.boxShadow = orig; } catch(e){} }, 1200);
            } catch(e){}
        },

        _flashInvalidClick() {
            try {
                const hl = document.getElementById('tour-highlight');
                if (!hl) return;
                const origClass = hl.className || '';
                let cleared = false;
                const reset = () => {
                    if (cleared) return; cleared = true;
                    try { hl.className = origClass; hl.style.transform = ''; hl.style.transition = ''; } catch(e){}
                };
                hl.style.transition = 'transform 120ms ease-out, box-shadow 120ms ease-out';
                hl.style.boxShadow = '0 0 0 9999px rgba(220,38,38,0.52), 0 0 0 8px rgba(239,68,68,0.85)';
                hl.style.transform = 'translateX(0px)';
                let i = 0;
                const shakes = [6, -6, 4, -4, 2, -2, 0];
                const tick = () => {
                    try {
                        hl.style.transform = 'translateX(' + (shakes[i] || 0) + 'px)';
                    } catch(e){}
                    i++;
                    if (i < shakes.length) setTimeout(tick, 50); else setTimeout(reset, 160);
                };
                setTimeout(tick, 10);
                setTimeout(reset, 520);
            } catch(e){}
        },

        onTourMaskClick(e) {
            try {
                const cx = e.clientX, cy = e.clientY;
                const tooltipEl = document.getElementById('tour-tooltip');
                if (tooltipEl && tooltipEl.isConnected) {
                    const t = tooltipEl.getBoundingClientRect();
                    if (cx >= t.left && cx <= t.right && cy >= t.top && cy <= t.bottom) return;
                }
                const hl = document.getElementById('tour-highlight');
                let inside = false;
                if (hl) {
                    const r = hl.getBoundingClientRect();
                    inside = (cx >= r.left && cx <= r.right && cy >= r.top && cy <= r.bottom);
                    if (inside) {
                        try {
                            hl.style.pointerEvents = hl.style.pointerEvents;
                            hl.style.visibility = 'hidden';
                            const maskEl = document.getElementById('tour-mask');
                            if (maskEl) maskEl.style.visibility = 'hidden';
                            const real = document.elementFromPoint(cx, cy);
                            if (maskEl) maskEl.style.visibility = '';
                            hl.style.visibility = '';
                            if (real && !['#tour-mask','tour-tooltip','#tour-highlight'].includes(real.id || '')) {
                                e.preventDefault();
                                e.stopImmediatePropagation();
                                e.stopPropagation();
                                const origMaskPe = document.body.style.pointerEvents;
                                try {
                                    const toDis = [document.getElementById('tour-mask'), hl, tooltipEl].filter(Boolean);
                                    toDis.forEach(n => { n.style.pointerEvents = 'none'; });
                                    const under = document.elementFromPoint(cx, cy);
                                    toDis.forEach(n => { n.style.pointerEvents = ''; });
                                    if (under) {
                                        const evOpts = { bubbles: true, cancelable: true, composed: true, view: window, clientX: cx, clientY: cy, button: 0, buttons: 1 };
                                        try {
                                            const md = new MouseEvent('mousedown', evOpts); under.dispatchEvent(md);
                                        } catch(err){}
                                        try {
                                            const mu = new MouseEvent('mouseup', evOpts); under.dispatchEvent(mu);
                                        } catch(err){}
                                        try {
                                            const ce = new MouseEvent('click', Object.assign({}, evOpts, { buttons: 0 }));
                                            under.dispatchEvent(ce);
                                        } catch(err){}
                                        try { if (under.click && typeof under.click === 'function') under.click(); } catch(err){}
                                    }
                                } finally {
                                    document.body.style.pointerEvents = origMaskPe;
                                }
                                return false;
                            }
                        } catch(err){}
                    }
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();
                this._flashInvalidClick();
                if (window.navigator && window.navigator.vibrate) {
                    try { window.navigator.vibrate(18); } catch(err){}
                }
                return false;
            } catch(err){
                try {
                    e.preventDefault(); e.stopImmediatePropagation(); e.stopPropagation();
                    this._flashInvalidClick();
                } catch(ee){}
                return false;
            }
        },

        _onTourMaskKeydown(e) {
            if (!e) return;
            try {
                const k = (e.key || '').toString();
                if (k === 'Tab' || k === 'Enter' || k === ' ') {
                    const tooltipEl = document.getElementById('tour-tooltip');
                    if (tooltipEl && tooltipEl.contains && e.target && tooltipEl.contains(e.target)) return;
                }
                const isEsc = (k === 'Escape');
                const tooltipEl = document.getElementById('tour-tooltip');
                if (isEsc && tooltipEl) {
                    const btnX = tooltipEl.querySelector('button[title="Encerrar tour"], .tour-close-btn, [@click="stopTutorial()"]');
                    if (btnX && btnX.click) try { btnX.click(); return; } catch(err){}
                }
                if (k && ['Tab','Enter',' ','ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(k)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    e.stopPropagation();
                    this._flashInvalidClick();
                    return false;
                }
            } catch(e){}
        },

        findTargetForStep(step) {
            if (!step) return null;
            const sel = step.seletores && Array.isArray(step.seletores)
                ? step.seletores
                : (step.seletor || null);
            const r = this.findTarget(sel);
            if (!r && (step.fallback)) {
                try {
                    const rawList = Array.isArray(sel) ? sel : (sel ? sel.split(',').map(x=>x.trim()).filter(Boolean) : []);
                    const tit = (this.tutorialAtivo ? (this.tutorialAtivo.titulo+' / ') : '') + (step.titulo || '(sem título)');
                    console.warn('[Tour] Elemento não encontrado — usando fallback percentual.', {
                        etapa: tit,
                        seletoresTestados: rawList,
                        fallback: step.fallback,
                        passoIndice: this.stepIndex
                    });
                } catch(e){}
            }
            return r;
        },

        _pickBestInMatches(matches, hasUsefulContent, tooSmallOrJunk) {
            const vw = window.innerWidth || document.documentElement.clientWidth || 0;
            const vh = window.innerHeight || document.documentElement.clientHeight || 0;
            const finalistas = [];
            for (const m of matches) {
                try {
                    if (!m || !m.isConnected) continue;
                    if (!this._isElementVisible(m)) continue;
                    const r = m.getBoundingClientRect();
                    if (tooSmallOrJunk(r, m)) continue;
                    const overlapW = Math.max(0, Math.min(r.right, vw) - Math.max(r.left, 0));
                    const overlapH = Math.max(0, Math.min(r.bottom, vh) - Math.max(r.top, 0));
                    const areaOverlap = overlapW * overlapH;
                    if (areaOverlap < 28) continue;
                    const temConteudo = hasUsefulContent(m) ? 0 : 1e9;
                    finalistas.push({ el: m, r, score: temConteudo + (r.width*r.height) });
                } catch(e){}
            }
            if (!finalistas.length) return null;
            finalistas.sort((a,b) => a.score - b.score);
            return finalistas[0].el;
        },

        findTarget(sel) {
            if (!sel) return null;
            const hasUsefulContent = (el) => {
                try {
                    if (!el) return false;
                    if (el.nodeType !== 1) return false;
                    if (el.hasAttribute && el.hasAttribute('data-tour')) return true;
                    if (el.querySelector && (el.querySelector('img,svg,canvas,video,input,select,textarea,button,[data-tour]'))) return true;
                    const txt = (el.innerText || el.textContent || '').replace(/\s+/g,' ').trim();
                    if (txt.length >= 2) return true;
                    if (el.getAttribute && (el.getAttribute('aria-label') || '').trim().length >= 2) return true;
                    if (el.getAttribute && (el.getAttribute('title') || '').trim().length >= 2) return true;
                    return false;
                } catch(e) { return false; }
            };
            const tooSmallOrJunk = (r, el) => {
                if (r.width < 14 || r.height < 14) return true;
                if (r.width * r.height < 28) return true;
                if (r.top < 64 && r.left < 40 && (r.width * r.height) < 40000) {
                    if (!hasUsefulContent(el)) return true;
                }
                return false;
            };

            const lista = Array.isArray(sel)
                ? sel.map(s => (s||'').trim()).filter(Boolean)
                : (''+sel).split(',').map(s => s.trim()).filter(Boolean);
            if (!lista.length) return null;

            const prioridade = [];
            const resto = [];
            const vistosRaw = new Set();
            for (const s of lista) {
                if (!s || vistosRaw.has(s)) continue;
                vistosRaw.add(s);
                if (/\bdata-tour\b/.test(s)) prioridade.push(s); else resto.push(s);
            }
            const candidatosOrdenados = prioridade.concat(resto);
            const vistos = new Set();

            for (const s of candidatosOrdenados) {
                try {
                    const matches = document.querySelectorAll(s);
                    if (!matches.length) continue;
                    const arr = Array.prototype.slice.call(matches).filter(m => !vistos.has(m));
                    arr.forEach(m => vistos.add(m));
                    const melhor = this._pickBestInMatches(arr, hasUsefulContent, tooSmallOrJunk);
                    if (melhor) return melhor;
                } catch(e){}
            }

            for (const s of candidatosOrdenados) {
                try {
                    const matches = document.querySelectorAll(s);
                    if (!matches.length) continue;
                    for (const el of matches) {
                        let cur = el;
                        let depth = 0;
                        const ascendentes = [];
                        while (cur && depth < 12) {
                            if (cur && cur.nodeType === 1 && !vistos.has(cur)) {
                                vistos.add(cur);
                                ascendentes.push(cur);
                            }
                            if (!cur || !cur.parentElement) break;
                            cur = cur.parentElement;
                            depth++;
                        }
                        const melhor = this._pickBestInMatches(ascendentes, hasUsefulContent, tooSmallOrJunk);
                        if (melhor) return melhor;
                    }
                } catch(e){}
            }

            for (const s of candidatosOrdenados) {
                try {
                    const f = document.querySelector(s);
                    if (f && f.isConnected) {
                        const r = f.getBoundingClientRect();
                        if (r.width > 16 || r.height > 16) return f;
                    }
                } catch(e){}
            }

            const titleSel = 'h1, .page-title, [data-page-title], .module-title, [data-tour], h2.font-bold, h2.font-black, .dashboard-tabs > button, nav > button';
            try {
                const ts = document.querySelectorAll(titleSel);
                const arr = Array.prototype.slice.call(ts);
                const melhor = this._pickBestInMatches(arr, hasUsefulContent, tooSmallOrJunk);
                if (melhor) return melhor;
            } catch(e){}
            return null;
        },

        updateTourPosition() {
            if (this.stepIndex < 0 || !this.tutorialAtivo || !this.currentStep) {
                this.tooltipStyle = 'opacity:0;visibility:hidden;';
                this.containerStyleForTooltip = 'opacity:0;visibility:hidden;';
                const hl = document.getElementById('tour-highlight');
                if (hl) hl.style.cssText = '';
                return;
            }
            if (this._isScrolling) return;
            if (this._updatePositionTimer) { clearTimeout(this._updatePositionTimer); this._updatePositionTimer = null; }
            this._remeasureCount = 0;
            this._updatePositionTimer = setTimeout(() => { this._doUpdateTourPosition(); }, 0);
        },

        _doUpdateTourPosition() {
            const hl = document.getElementById('tour-highlight');
            const PADDING = 8;
            const MIN_WIDTH = 40;
            const MIN_HEIGHT = 28;
            const el = this.findTargetForStep(this.currentStep);
            let top, left, width, height, right, bottom;
            let sourceRect = null;
            let usingFallback = false;
            if (el) {
                sourceRect = el.getBoundingClientRect();
            }
            if (sourceRect && (sourceRect.width > 2 || sourceRect.height > 2)) {
                left = Math.round(sourceRect.left) - PADDING;
                top = Math.round(sourceRect.top) - PADDING;
                right = Math.round(sourceRect.left + sourceRect.width) + PADDING;
                bottom = Math.round(sourceRect.top + sourceRect.height) + PADDING;
            } else if (this.currentStep.fallback) {
                usingFallback = true;
                const fb = this.currentStep.fallback;
                const w = 280, h = 120;
                const parseV = (v, max) => typeof v === 'string' && v.includes('%') ? Math.round(max * parseFloat(v) / 100) : (typeof v === 'number' ? Math.round(v) : Math.round(max/2));
                const cx = parseV(fb.x, window.innerWidth);
                const cy = parseV(fb.y, window.innerHeight);
                left = cx - w/2; top = cy - h/2; right = cx + w/2; bottom = cy + h/2;
            } else {
                usingFallback = true;
                const w = 280, h = 140;
                left = Math.round(window.innerWidth/2) - w/2;
                top = Math.round(window.innerHeight/2) - h/2;
                right = left + w; bottom = top + h;
            }
            width = right - left;
            height = bottom - top;
            if (width < MIN_WIDTH) {
                const extra = MIN_WIDTH - width;
                left -= Math.ceil(extra/2); right += Math.floor(extra/2);
                width = right - left;
            }
            if (height < MIN_HEIGHT) {
                const extra = MIN_HEIGHT - height;
                top -= Math.ceil(extra/2); bottom += Math.floor(extra/2);
                height = bottom - top;
            }
            const vw = window.innerWidth, vh = window.innerHeight;
            if (left < 0) { right += (-left); left = 0; width = right - left; }
            if (top < 0) { bottom += (-top); top = 0; height = bottom - top; }
            if (right > vw) { left -= (right - vw); right = vw; left = Math.max(0, left); width = right - left; }
            if (bottom > vh) { top -= (bottom - vh); bottom = vh; top = Math.max(0, top); height = bottom - top; }
            if (hl) {
                const hlCss = [
                    `top:${top}px`,
                    `left:${left}px`,
                    `width:${width}px`,
                    `height:${height}px`
                ];
                if (sourceRect && sourceRect.width > 4 && sourceRect.height > 4) {
                    try {
                        let rounded = 12;
                        const cs = el && el.nodeType === 1 ? window.getComputedStyle(el) : null;
                        if (cs) {
                            const r = parseFloat(cs.borderRadius || '0');
                            if (!isNaN(r) && r > 0) rounded = Math.max(6, Math.min(32, r + PADDING));
                        }
                        hlCss.push(`border-radius:${rounded}px`);
                    } catch(e){}
                }
                hl.style.cssText = hlCss.join(';') + ';';
            }
            const isMobile = window.innerWidth < 640;
            const TOOLTIP_W = isMobile ? vw : Math.min(vw - 32, 420);
            let tooltipEl = null;
            try { tooltipEl = document.getElementById('tour-tooltip'); } catch(e){}
            let ttH = null;
            if (tooltipEl && tooltipEl.isConnected && tooltipEl.offsetParent !== null) {
                try {
                    const r = tooltipEl.getBoundingClientRect();
                    if (r && r.height > 120) {
                        ttH = Math.ceil(r.height) + 12;
                    }
                    const sh = tooltipEl.scrollHeight || 0;
                    if (sh > 180) ttH = Math.max(ttH || 0, sh + 12);
                } catch(e){}
            }
            const SAFE_BOTTOM = isMobile
                ? Math.max(10, 4 + Math.max(0, window.innerHeight - document.documentElement.clientHeight))
                : 28;
            const SAFE_TOP = isMobile ? Math.max(0, envTopPx() + 0) : 12;
            function envTopPx() { try { const s = getComputedStyle(document.documentElement).getPropertyValue('--sat') || '0'; return parseInt(s,10)||0; } catch(e) { return 0; } }
            const SAFE_LEFT = isMobile ? 0 : 12;
            const SAFE_RIGHT = isMobile ? 0 : 12;
            const MARGIN = isMobile ? 14 : 14;
            if (!ttH || ttH < 200) {
                ttH = isMobile
                    ? Math.min(480, Math.floor(vh * (vh < 700 ? 0.62 : 0.58)))
                    : Math.min(560, Math.floor(vh * (vh < 700 ? 0.90 : 0.86)));
            }
            if (isMobile) ttH = Math.min(ttH, Math.max(320, Math.floor(vh * 0.62)));
            const ttRect = { width: TOOLTIP_W, height: ttH };
            try {
                const root = document.documentElement;
                root.style.setProperty('--tt-max-h', Math.max(300, Math.min(480, Math.floor(vh * (isMobile ? 0.62 : 0.86)))) + 'px');
            } catch(e){}
            const pos = this.currentStep.posicao || 'auto';
            const s = { t: top, l: left, r: right, b: bottom, w: width, h: height };
            const reservedForTooltip = ttRect.height + MARGIN;
            const spaceAbove = s.t - SAFE_TOP;
            const spaceBelow = vh - s.b - SAFE_BOTTOM;
            let ttTop, ttLeft;
            let finalPos = pos;
            let containerCss = '';
            if (isMobile) {
                const fitsAbove = spaceAbove >= reservedForTooltip;
                const fitsBelow = spaceBelow >= reservedForTooltip;
                if (fitsBelow) finalPos = 'bottom';
                else if (fitsAbove) finalPos = 'top';
                else {
                    finalPos = (spaceBelow >= spaceAbove) ? 'bottom' : 'top';
                    ttH = Math.min(ttH, (finalPos === 'bottom' ? spaceBelow : spaceAbove));
                    ttRect.height = Math.max(260, ttH);
                }
                if (finalPos === 'bottom') {
                    ttTop = Math.max(s.b + MARGIN, vh - ttRect.height - SAFE_BOTTOM);
                    ttLeft = 0;
                    ttRect.width = vw;
                } else {
                    ttTop = Math.min(s.t - MARGIN - ttRect.height, SAFE_TOP);
                    ttLeft = 0;
                    ttRect.width = vw;
                }
                if (finalPos === 'bottom') {
                    const minGap = 14;
                    const actualGap = ttTop - s.b;
                    if (actualGap < minGap) {
                        const canShrinkTooltipBy = ttRect.height - 280;
                        const need = (minGap - actualGap);
                        if (canShrinkTooltipBy >= need) {
                            ttRect.height -= need;
                        } else {
                            ttTop = s.b + minGap;
                            ttRect.height = Math.max(260, vh - ttTop - SAFE_BOTTOM);
                        }
                    }
                } else if (finalPos === 'top') {
                    const minGap = 14;
                    const actualGap = s.t - (ttTop + ttRect.height);
                    if (actualGap < minGap) {
                        const canShrinkBy = ttRect.height - 280;
                        const need = (minGap - actualGap);
                        if (canShrinkBy >= need) {
                            ttRect.height -= need;
                            ttTop += need;
                        } else {
                            const newBottom = s.t - minGap;
                            ttTop = newBottom - ttRect.height;
                            if (ttTop < SAFE_TOP) { ttTop = SAFE_TOP; ttRect.height = Math.max(260, newBottom - ttTop); }
                        }
                    }
                }
                const sL = s.l, sR = s.r, sT = s.t, sB = s.b;
                const tL = ttLeft, tR = ttLeft + ttRect.width, tT = ttTop, tB = ttTop + ttRect.height;
                const ovH = !(tR < sL || tL > sR);
                const ovV = !(tB + 6 < sT || tT - 6 > sB);
                if (ovH && ovV) {
                    if (finalPos === 'bottom') {
                        const newTop = sB + 16;
                        if ((newTop + ttRect.height) > vh - SAFE_BOTTOM) {
                            ttRect.height = Math.max(260, vh - newTop - SAFE_BOTTOM);
                        }
                        ttTop = newTop;
                    } else {
                        const newBot = sT - 16;
                        const newTop = newBot - ttRect.height;
                        if (newTop < SAFE_TOP) {
                            ttRect.height = Math.max(260, newBot - SAFE_TOP);
                            ttTop = SAFE_TOP;
                        } else {
                            ttTop = newTop;
                        }
                    }
                }
                containerCss = [
                    'top:0',
                    'left:0',
                    'width:100%',
                    'height:100%',
                    'display:flex',
                    'flex-direction:column',
                    'justify-content:' + (finalPos === 'bottom' ? 'flex-end' : 'flex-start'),
                    'align-items:stretch',
                    'padding-top:' + Math.round(ttTop) + 'px',
                    'padding-bottom:' + Math.round(vh - (ttTop + ttRect.height)) + 'px',
                    'padding-left:0',
                    'padding-right:0',
                    'box-sizing:border-box',
                    'pointer-events:none',
                    'opacity:1',
                    'visibility:visible'
                ].join(';') + ';';
                this.containerStyleForTooltip = containerCss;
                this.tooltipStyle = 'width:100%;max-width:100%;';
            } else {
                const fitsBelow = (vh - s.b) >= ttRect.height + MARGIN + SAFE_BOTTOM;
                const fitsAbove = s.t >= ttRect.height + MARGIN + SAFE_TOP;
                const fitsLeft = s.l >= ttRect.width + MARGIN + SAFE_LEFT;
                const fitsRight = (vw - s.r) >= ttRect.width + MARGIN + SAFE_RIGHT;
                if (pos !== 'auto') {
                    if (pos === 'top' && !fitsAbove) finalPos = 'bottom';
                    else if (pos === 'bottom' && !fitsBelow) finalPos = 'top';
                    else if (pos === 'left' && !fitsLeft && !isMobile) finalPos = 'right';
                    else if (pos === 'right' && !fitsRight && !isMobile) finalPos = 'left';
                } else {
                    if (fitsBelow) finalPos = 'bottom';
                    else if (fitsAbove) finalPos = 'top';
                    else if (fitsRight && !isMobile) finalPos = 'right';
                    else if (fitsLeft && !isMobile) finalPos = 'left';
                    else finalPos = 'top';
                }
            const anchorCx = s.l + s.w / 2;
            const anchorCy = s.t + s.h / 2;
            let overlapAvoided = false;
            if (finalPos === 'bottom') {
                ttTop = s.b + MARGIN;
                ttLeft = Math.round(anchorCx - ttRect.width / 2);
                if (isMobile && (ttTop + ttRect.height) > (vh - SAFE_BOTTOM) && false) {
                    finalPos = 'top';
                    overlapAvoided = true;
                }
            }
            if (finalPos === 'top' || overlapAvoided) {
                ttTop = s.t - MARGIN - ttRect.height;
                ttLeft = Math.round(anchorCx - ttRect.width / 2);
            } else if (finalPos === 'left') {
                ttLeft = s.l - MARGIN - ttRect.width;
                ttTop = Math.round(anchorCy - ttRect.height / 2);
            } else if (finalPos === 'right') {
                ttLeft = s.r + MARGIN;
                ttTop = Math.round(anchorCy - ttRect.height / 2);
            }
            if (ttLeft < SAFE_LEFT) ttLeft = SAFE_LEFT;
            if (ttTop < SAFE_TOP) ttTop = SAFE_TOP;
            if (ttLeft + ttRect.width > vw - SAFE_RIGHT) ttLeft = Math.max(SAFE_LEFT, vw - ttRect.width - SAFE_RIGHT);
            if (ttTop + ttRect.height > vh - SAFE_BOTTOM) {
                if (finalPos !== 'top' && fitsAbove) {
                    ttTop = s.t - MARGIN - ttRect.height;
                } else {
                    ttTop = Math.max(SAFE_TOP, vh - ttRect.height - SAFE_BOTTOM);
                }
            }
            this.containerStyleForTooltip = [
                'top:' + Math.round(ttTop) + 'px',
                'left:' + Math.round(ttLeft) + 'px',
                'width:' + Math.round(ttRect.width) + 'px',
                'opacity:1',
                'visibility:visible',
                'pointer-events:none'
            ].join(';') + ';';
            this.tooltipStyle = [
                `top:0`,
                `left:0`,
                `width:100%`,
                `max-width:100%`
            ].join(';') + ';';
            }
            if (typeof this._remeasureCount === 'undefined') this._remeasureCount = 0;
            if (this._remeasureCount < 3) {
                this._remeasureCount++;
                if (!this._remeasureTimer) {
                    this._remeasureTimer = setTimeout(() => {
                        this._remeasureTimer = null;
                        if (this.stepIndex >= 0 && this.tutorialAtivo) {
                            this._doUpdateTourPosition();
                        }
                    }, 30);
                }
            } else {
                this._remeasureCount = 0;
            }
        }
    };
}
</script>
@else
    {{-- Tour Center desabilitado (em desenvolvimento). Altere $TOUR_CENTER_LIBERADO=true no topo deste arquivo para ativar. --}}
@endif
