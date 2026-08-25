@extends('layouts.dashboard')

@section('title', 'Usuários')

@php
use App\Services\PermissaoService;
$perfis = PermissaoService::perfisDisponiveis();
$arvore = PermissaoService::arvorePermissoes();
@endphp

@section('content')
<div>
    <div class="rounded-xl shadow-sm p-6" style="border-color: #78350f;">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-white mb-2">Usuários</h2>
            <p class="text-sm text-white">Cadastro, gestão de perfis e <strong>controle de acesso</strong> por classe.</p>
            <div class="mt-3 inline-flex flex-wrap justify-center gap-2 text-[11px] font-extrabold uppercase tracking-wider">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 bg-rose-500/90 text-white border border-white/10 shadow-sm">
                    <i class="fa-solid fa-crown"></i>
                    Administrador
                    <span class="text-[9px] text-white/80">· Tudo liberado, inclusive Controle de Acesso</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 bg-amber-500/95 text-white border border-white/10 shadow-sm">
                    <i class="fa-solid fa-user-gear"></i>
                    Operador
                    <span class="text-[9px] text-white/80">· Default: tudo liberado. Pode bloquear por módulo.</span>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 bg-sky-500/95 text-white border border-white/10 shadow-sm">
                    <i class="fa-solid fa-user-check"></i>
                    Leitor
                    <span class="text-[9px] text-white/80">· Default: só leitura. Pode liberar escrita por módulo.</span>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="space-y-6 mt-6" x-data="usuariosRBAC()">
    {{-- =======================================================
         CSS GARANTIDO — 100% independe de classes Tailwind/build
         (resolução do bug: container vazios na versão web)
         ======================================================= --}}
    <style>
        /* DESKTOP: >= 768px → MOSTRA tabela, OCULTA cards */
        @media (min-width: 768px) {
            [data-usuarios-ui="desktop-table"] {
                display: block !important;
                width: 100% !important;
                min-height: 500px !important;
                overflow-x: auto !important;
            }
            [data-usuarios-ui="mobile-cards"] {
                display: none !important;
            }
        }
        /* MOBILE: < 768px → MOSTRA cards, OCULTA tabela */
        @media (max-width: 767.98px) {
            [data-usuarios-ui="mobile-cards"] {
                display: block !important;
                margin-top: 0.75rem !important;
                margin-bottom: 0.75rem !important;
            }
            [data-usuarios-ui="desktop-table"] {
                display: none !important;
            }
        }
    </style>

    {{-- ============= ALERTS: SUCESSO / ERRO / VALIDAÇÃO ============= --}}
    @if(session()->has('success'))
        <div role="alert" class="relative w-full rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 shadow-sm flex items-start gap-3 animate-[fadeIn_150ms_ease-out]">
            <div class="shrink-0 mt-0.5"><i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i></div>
            <div class="min-w-0 flex-1 text-sm font-semibold leading-snug">{!! session('success') !!}</div>
            <button type="button" onclick="this.parentElement.remove()" class="shrink-0 -mr-1 -mt-1 p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-100 transition-colors" aria-label="Fechar alerta">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
    @if(session()->has('error'))
        <div role="alert" class="relative w-full rounded-2xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 shadow-sm flex items-start gap-3 animate-[fadeIn_150ms_ease-out]">
            <div class="shrink-0 mt-0.5"><i class="fa-solid fa-circle-exclamation text-rose-500 text-lg"></i></div>
            <div class="min-w-0 flex-1 text-sm font-semibold leading-snug whitespace-pre-wrap break-words">{!! session('error') !!}</div>
            <button type="button" onclick="this.parentElement.remove()" class="shrink-0 -mr-1 -mt-1 p-1.5 rounded-lg text-rose-600 hover:bg-rose-100 transition-colors" aria-label="Fechar alerta">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
    @if(!empty($errorMessage))
        <div role="alert" class="relative w-full rounded-2xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 shadow-sm flex items-start gap-3">
            <div class="shrink-0 mt-0.5"><i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg"></i></div>
            <div class="min-w-0 flex-1 text-sm font-semibold leading-snug">{{ $errorMessage }}</div>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="relative w-full rounded-2xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 shadow-sm space-y-2 animate-[fadeIn_150ms_ease-out]">
            <div class="flex items-start gap-3">
                <div class="shrink-0 mt-0.5"><i class="fa-solid fa-circle-xmark text-red-500 text-lg"></i></div>
                <div class="min-w-0 flex-1 text-sm font-black uppercase tracking-wide">Corrija os erros antes de continuar:</div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="shrink-0 -mr-1 -mt-1 p-1.5 rounded-lg text-red-600 hover:bg-red-100 transition-colors" aria-label="Fechar alerta">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <ul class="text-[12.5px] font-semibold text-red-800 pl-7 list-disc space-y-0.5 marker:text-red-400">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- ============================================================= --}}

    <div class="flex items-center justify-between">
        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 inline-flex items-center gap-2">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Somente usuários com perfil <strong>Administrador</strong> podem acessar esta tela.</span>
        </div>
        <button type="button" @click="abrirCriar()" class="group relative inline-flex items-center justify-center w-12 h-12 bg-primary-600 text-white rounded-xl shadow-lg hover:bg-primary-700 hover:scale-110 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" title="Adicionar Usuário">
            <i class="fa-solid fa-user-plus text-xl"></i>
        </button>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
            <h6 class="font-bold text-primary-700 dark:text-primary-400 uppercase text-xs tracking-wider">Lista de Usuários ({{ $usuarios->count() }})</h6>
        </div>
        <div class="p-4 sm:p-6">
            {{-- ########## MOBILE (resolução < 768px) — controlado POR MEDIA QUERY CSS INLINE (acima) ########## --}}
            <div data-usuarios-ui="mobile-cards" aria-label="Lista de usuários - Modo Mobile (<768px)" class="space-y-3">
                @forelse($usuarios as $u)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ $u->nome }}</div>
                                    {!! \App\Services\PermissaoService::PERFIL_ADMIN === (string) ($u->perfil ?? '')
                                        ? '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9.5px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200"><i class="fa-solid fa-crown text-[9px]"></i> Admin</span>'
                                        : (\App\Services\PermissaoService::PERFIL_LEITOR === (string) ($u->perfil ?? '')
                                            ? '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9.5px] font-black uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200"><i class="fa-solid fa-book text-[9px]"></i> Leitor</span>'
                                            : '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9.5px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200"><i class="fa-solid fa-user-gear text-[9px]"></i> Operador</span>')
                                    !!}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">Usuário: <code class="font-mono">{{ $u->usuario }}</code></div>
                                @php
                                    $perms = \App\Services\PermissaoService::listaPermissoesDoUsuario($u);
                                    $perfil = (string) ($u->perfil ?? 'operador');
                                @endphp
                                <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    @if ($perfil === 'administrador')
                                        <i class="fa-solid fa-check-double text-emerald-500"></i>
                                        Acesso TOTAL — nenhuma restrição.
                                    @elseif (count($perms) === 0)
                                        <i class="fa-solid fa-circle-check text-amber-500"></i>
                                        Nenhum filtro configurado — padrão do perfil.
                                    @else
                                        <i class="fa-solid fa-list-check text-primary-500"></i>
                                        {{ count($perms) }} regra(s) de permissão aplicada(s).
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button"
                                        @click="abrirControleAcesso({ id: {{ $u->id }}, nome: @js($u->nome), usuario: @js($u->usuario), perfil: @js($u->perfil ?? 'operador'), permissoes: @js(\App\Services\PermissaoService::listaPermissoesDoUsuario($u)) })"
                                        class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100 transition-colors" title="Controle de Acesso">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </button>
                                <button type="button" @click="abrirEditar({ id: {{ $u->id }}, usuario: @js($u->usuario), perfil: @js($u->perfil ?? 'operador') })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors" title="Editar senha e perfil">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" @click="abrirExcluir({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Excluir">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-100 bg-white p-4 text-sm text-gray-500 text-center italic">
                        Nenhum usuário cadastrado.
                    </div>
                @endforelse
            </div>

            {{-- ########## DESKTOP (resolução >= 768px) — controlado POR MEDIA QUERY CSS INLINE (acima) ########## --}}
            <div data-usuarios-ui="desktop-table" data-debug="usuarios-desktop-table" aria-label="Tabela de usuários - Modo Desktop (>=768px)" class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 w-full">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuário</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Classe (Perfil)</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Permissões</th>
                        <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($usuarios as $u)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $u->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $u->usuario }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ((string) ($u->perfil ?? '') === PermissaoService::PERFIL_ADMIN)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-crown text-[10px]"></i> Administrador
                                    </span>
                                @elseif ((string) ($u->perfil ?? '') === PermissaoService::PERFIL_LEITOR)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200">
                                        <i class="fa-solid fa-book text-[10px]"></i> Leitor
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-user-gear text-[10px]"></i> Operador
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @php
                                    $perms = PermissaoService::listaPermissoesDoUsuario($u);
                                    $perfil = (string) ($u->perfil ?? 'operador');
                                @endphp
                                @if ($perfil === PermissaoService::PERFIL_ADMIN)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <i class="fa-solid fa-check-double text-[10px]"></i> Tudo liberado
                                    </span>
                                @elseif (count($perms) === 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold text-gray-500 bg-gray-50 border border-gray-100">
                                        <i class="fa-solid fa-circle-dot text-[10px]"></i> Padrão da classe
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold bg-primary-50 text-primary-700 border border-primary-100">
                                        <i class="fa-solid fa-list-check text-[10px]"></i> {{ count($perms) }} regra(s)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button type="button"
                                            @click="abrirControleAcesso({ id: {{ $u->id }}, nome: @js($u->nome), usuario: @js($u->usuario), perfil: @js($u->perfil ?? 'operador'), permissoes: @js(PermissaoService::listaPermissoesDoUsuario($u)) })"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-black uppercase tracking-wider text-white bg-gradient-to-b from-primary-500 to-primary-700 border border-primary-500 hover:from-primary-400 hover:to-primary-600 shadow-sm shadow-primary-900/20 transition-all">
                                        <i class="fa-solid fa-shield-halved text-white"></i>
                                        <span class="text-white whitespace-nowrap">Controle de Acesso</span>
                                    </button>
                                    <button type="button" @click="abrirEditar({ id: {{ $u->id }}, usuario: @js($u->usuario), perfil: @js($u->perfil ?? 'operador') })" class="p-2 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Editar perfil / senha">
                                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                                    </button>
                                    <button type="button" @click="abrirExcluir({ id: {{ $u->id }}, usuario: @js($u->usuario) })" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                        <i class="fa-solid fa-trash-can text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">Nenhum usuário cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- ############ MODAL: CRIAR USUÁRIO ############ --}}
    <div x-show="modoCriar" class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-title-create" x-cloak>
        <div x-show="modoCriar" @click="modoCriar = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900/55 dark:bg-black/65 transition-opacity backdrop-blur-[1px]" aria-hidden="true"></div>
        <div x-show="modoCriar" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative z-[75] w-full bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
             style="max-width: min(720px, 96vw); max-height: min(92vh, 820px); display: flex; flex-direction: column;">
                <form accept-charset="UTF-8" action="{{ route('admin.usuarios.store', [], false) }}" method="POST" class="flex flex-col h-full w-full min-h-0 max-w-full">
                    @csrf
                    <div class="px-5 pt-5 pb-3 sm:px-6 sm:pt-6 sm:pb-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-gradient-to-r from-primary-50 via-white to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 text-white flex items-center justify-center shadow-md shadow-primary-900/20">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 leading-tight">Novo Usuário</h3>
                                <p class="text-[11.5px] text-gray-500 mt-0.5">Cadastre usuário, senha, perfil e opcionalmente abra o controle de permissões.</p>
                            </div>
                        </div>
                        <button type="button" @click="modoCriar=false" class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-gray-700 flex items-center justify-center">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="p-5 sm:px-6 sm:pt-5 sm:pb-6 space-y-5 flex-1 min-h-0 overflow-y-auto">
                        <div>
                            <label class="block text-[12px] font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-1.5">Nome / Usuário de login</label>
                            <input type="text" x-model="criar.usuario" name="usuario" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500" placeholder="ex: joao.silva" required>
                        </div>
                        <div>
                            <label class="block text-[12px] font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-1.5">Senha inicial</label>
                            <div class="relative">
                                <input type="password" x-model="criar.senha" name="senha" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 pr-11 text-sm font-medium text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500" placeholder="Mínimo 6 caracteres" required>
                                <button type="button" @click="$event.preventDefault(); criarSenhaVisivel = !criarSenhaVisivel; $nextTick(() => { criarSenhaVisivel ? $el.parentElement.querySelector('input').setAttribute('type','text') : $el.parentElement.querySelector('input').setAttribute('type','password'); })" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg text-gray-500 hover:text-primary-700 hover:bg-primary-50 flex items-center justify-center">
                                    <i class="fa-solid" :class="criarSenhaVisivel ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 space-y-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-shield text-xs"></i></div>
                                <div>
                                    <div class="text-[12.5px] font-black uppercase tracking-wider text-amber-800">Classe de permissão</div>
                                    <div class="text-[11.5px] text-amber-700/90 mt-0.5">Define a regra padrão. Você pode detalhar no botão Controle de Acesso.</div>
                                </div>
                            </div>
                            <select x-model="criar.perfil" name="perfil" class="w-full rounded-xl border-amber-200 bg-white px-3.5 py-2.5 text-sm font-black text-gray-900 focus:ring-amber-500 focus:border-amber-500">
                                @foreach($perfis as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="text-[11px] text-amber-800/90 leading-snug" x-show="criar.perfil === 'administrador'" x-cloak>
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Administrador tem TUDO liberado — inclusive alterar permissões e este controle de acesso. Não pode ser bloqueado.
                            </div>
                            <div class="text-[11px] text-amber-800/90 leading-snug" x-show="criar.perfil === 'operador'" x-cloak>
                                <i class="fa-solid fa-circle-info"></i> Operador = lista vazia = TUDO liberado. Ao marcar itens abaixo → você estará BLOQUEANDO (Deny list) módulos/açōes.
                            </div>
                            <div class="text-[11px] text-amber-800/90 leading-snug" x-show="criar.perfil === 'leitor'" x-cloak>
                                <i class="fa-solid fa-circle-info"></i> Leitor = lista vazia = SÓ pode LER. Ao marcar itens → você estará LIBERANDO (Allow list) ações de escrita.
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                            <button type="button" @click="abrirControleAcesso({ id: '__criar__', nome: criar.usuario || 'Novo usuário', usuario: criar.usuario, perfil: criar.perfil, permissoes: criar.permissoes, fromCriar: true })" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-black uppercase tracking-wider text-white bg-gradient-to-b from-primary-500 to-primary-700 border border-primary-500 hover:from-primary-400 hover:to-primary-600 shadow-sm shadow-primary-900/20 transition-all min-h-[44px]">
                                <i class="fa-solid fa-shield-halved"></i>
                                Controle de Acesso
                            </button>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3.5 sm:px-6 sm:flex sm:flex-row-reverse sm:items-center gap-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-3 text-sm font-black text-white bg-gradient-to-b from-primary-500 to-primary-700 hover:from-primary-400 hover:to-primary-600 border border-primary-500 shadow-sm shadow-primary-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto transition-all min-h-[44px]">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar usuário
                        </button>
                        <button type="button" @click="modoCriar=false" class="mt-2 sm:mt-0 w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-3 text-sm font-bold border border-gray-300 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:w-auto transition-all min-h-[44px]">
                            Cancelar
                        </button>
                    </div>
                </form>
        </div>
    </div>

    {{-- ############ MODAL: EDITAR USUÁRIO (senha/perfil) — LAYOUT FLEXBOX PURO ############ --}}
    <div x-show="modoEditar" class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-title-edit" x-cloak>
        <div x-show="modoEditar" @click="modoEditar = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900/55 dark:bg-black/65 transition-opacity backdrop-blur-[1px]" aria-hidden="true"></div>
        <div x-show="modoEditar" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative z-[75] w-full bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
             style="max-width: min(720px, 96vw); max-height: min(92vh, 820px); display: flex; flex-direction: column;">
            <form accept-charset="UTF-8" :action="`/admin/usuarios/${editar.id}`" method="POST" class="flex flex-col h-full w-full min-h-0 max-w-full">
                @csrf
                @method('PATCH')
                <div class="px-5 pt-5 pb-3 sm:px-6 sm:pt-6 sm:pb-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-r from-primary-50 via-white to-white flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-white flex items-center justify-center shadow-md shadow-amber-900/20">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 leading-tight">Editar Usuário</h3>
                            <p class="text-[11.5px] text-gray-500 mt-0.5">Altere perfil ou defina uma nova senha.</p>
                        </div>
                    </div>
                    <button type="button" @click="modoEditar=false" class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-gray-700 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-5 sm:px-6 sm:pt-5 sm:pb-6 space-y-5 flex-1 min-h-0 overflow-y-auto">
                    <div>
                        <label class="block text-[12px] font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-1.5">Usuário (login)</label>
                        <input type="text" x-model="editar.usuario" disabled class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2.5 text-sm font-semibold text-gray-600 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-1.5">Nova senha (deixe vazio para não alterar)</label>
                        <input type="password" x-model="editar.senha" name="senha" class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm font-medium text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 space-y-3">
                        <label class="block text-[12px] font-bold uppercase tracking-wide text-amber-800">Classe de permissão</label>
                        <select x-model="editar.perfil" name="perfil" class="w-full rounded-xl border-amber-200 bg-white px-3.5 py-2.5 text-sm font-black text-gray-900 focus:ring-amber-500 focus:border-amber-500">
                            @foreach($perfis as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3.5 sm:px-6 sm:flex sm:flex-row-reverse sm:items-center gap-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                    <button type="submit" class="w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-3 text-sm font-black text-white bg-gradient-to-b from-primary-500 to-primary-700 hover:from-primary-400 hover:to-primary-600 border border-primary-500 shadow-sm shadow-primary-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto transition-all min-h-[44px]">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar
                    </button>
                    <button type="button" @click="modoEditar=false" class="mt-2 sm:mt-0 w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-3 text-sm font-bold border border-gray-300 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:w-auto transition-all min-h-[44px]">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ############ MODAL: EXCLUIR USUÁRIO — LAYOUT FLEXBOX PURO ############ --}}
    <div x-show="modoExcluir" class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-title-delete" x-cloak>
        <div x-show="modoExcluir" @click="modoExcluir = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900/55 dark:bg-black/65 transition-opacity backdrop-blur-[1px]" aria-hidden="true"></div>
        <div x-show="modoExcluir" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative z-[75] w-full bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
             style="max-width: min(520px, 96vw); max-height: min(92vh, 620px); display: flex; flex-direction: column;">
            <form accept-charset="UTF-8" :action="`/admin/usuarios/${excluir.id}`" method="POST" class="flex flex-col h-full w-full min-h-0 max-w-full">
                @csrf
                @method('DELETE')
                <div class="px-5 pt-5 pb-4 sm:p-6 sm:pb-4 flex-1 min-h-0 overflow-y-auto">
                    <h3 class="text-lg leading-6 font-black text-gray-900 dark:text-gray-100">Excluir Usuário</h3>
                    <div class="mt-3 rounded-xl bg-rose-50 border border-rose-100 p-3 flex items-start gap-2.5 text-sm text-rose-800">
                        <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-triangle-exclamation text-xs"></i></div>
                        <div>
                            <div>Ação irreversível. Confirma excluir usuário <strong x-text="excluir.usuario"></strong>?</div>
                            <div class="text-[11.5px] text-rose-700/80 mt-0.5">O próprio usuário logado não pode se autoexcluir (proteção).</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t border-gray-100 dark:border-gray-700 shrink-0">
                    <button type="submit" class="w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-black text-white bg-gradient-to-b from-red-500 to-red-700 hover:from-red-400 hover:to-red-600 border border-red-500 shadow-sm shadow-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto transition-all min-h-[44px]">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                    <button type="button" @click="modoExcluir=false" class="mt-2 sm:mt-0 w-full inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold border border-gray-300 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:w-auto transition-all min-h-[44px]">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ############ MODAL: CONTROLE DE ACESSO — ÁRVORE ############ --}}
    <div x-show="modoPermissoes"
         class="fixed inset-0 z-[80] flex items-center justify-center p-2 sm:p-4"
         role="dialog" aria-modal="true" aria-labelledby="modal-title-permissoes"
         x-cloak>
        {{-- Backdrop (clique fecha) --}}
        <div x-show="modoPermissoes"
             @click="modoPermissoes = false"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-gray-900/65 dark:bg-black/75 transition-opacity backdrop-blur-[2px]"
             aria-hidden="true"></div>

        {{-- Modal container (largura máxima 1100px, altura 92vh, layout FLEX COLUNA — nunca some) --}}
        <div x-show="modoPermissoes"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative z-[85] w-full bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden"
             style="max-width: min(1100px, 96vw); height: min(92vh, 920px); display: flex; flex-direction: column;">

                <form accept-charset="UTF-8"
                      :action="permissoes.fromCriar ? '/admin/usuarios' : `/admin/usuarios/${permissoes.id}/permissoes`"
                      method="POST"
                      @submit.prevent="submeterPermissoes($event)"
                      class="flex flex-col h-full w-full min-h-0 max-w-full">
                    @csrf
                    <input type="hidden" name="_form" value="permissoes">
                    <input type="hidden" name="perfil" x-model="permissoes.perfil">
                    <input type="hidden" name="_permissoes_enviadas" value="1">

                    <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-r from-primary-50 via-white to-white flex items-start justify-between gap-3 shrink-0">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 via-primary-600 to-primary-800 text-white flex items-center justify-center shadow-lg shadow-primary-900/25 shrink-0">
                                <i class="fa-solid fa-shield-halved text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xl font-black text-gray-900 dark:text-gray-100 leading-tight truncate">
                                    Controle de Acesso
                                </h3>
                                <div class="text-[12px] text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2">
                                    <i class="fa-solid fa-user text-primary-500"></i>
                                    <span class="font-bold text-gray-700 dark:text-gray-200 truncate max-w-[280px]" x-text="permissoes.nome"></span>
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <span class="text-[11px] font-mono text-gray-500 truncate" x-text="'login: '+permissoes.usuario"></span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="modoPermissoes = false" class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:text-gray-100 dark:hover:bg-gray-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="px-5 sm:px-6 pt-4 pb-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/50 shrink-0 space-y-3">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[11.5px] font-black uppercase tracking-wide text-primary-700 dark:text-primary-400">Classe (perfil) do usuário</label>
                                <select x-model="permissoes.perfil"
                                        @change="onMudarPerfilControleAcesso()"
                                        class="w-full rounded-xl border-primary-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-black text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500">
                                    @foreach($perfis as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[11.5px] font-black uppercase tracking-wide text-gray-600 dark:text-gray-400">Ações rápidas</label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="marcarTodasPermissoes(true)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold bg-emerald-500 text-white hover:bg-emerald-400 border border-emerald-600 shadow-sm shadow-emerald-900/10 transition-all min-h-[40px]">
                                        <i class="fa-solid fa-check-double"></i>
                                        <span x-text="permissoes.perfil === 'operador' ? 'Bloquear Tudo' : 'Liberar Tudo'"></span>
                                    </button>
                                    <button type="button" @click="marcarTodasPermissoes(false)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 shadow-sm transition-all min-h-[40px]">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        <span x-text="permissoes.perfil === 'operador' ? 'Voltar ao Padrão (Liberar Tudo)' : 'Voltar ao Padrão (Só Leitura)'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl p-3.5 text-[12px] leading-snug flex items-start gap-3"
                             :class="permissoes.perfil === 'administrador' ? 'bg-rose-50 border border-rose-100 text-rose-800' : (permissoes.perfil === 'operador' ? 'bg-amber-50 border border-amber-100 text-amber-800' : 'bg-sky-50 border border-sky-100 text-sky-800')">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                                 :class="permissoes.perfil === 'administrador' ? 'bg-rose-500 text-white' : (permissoes.perfil === 'operador' ? 'bg-amber-500 text-white' : 'bg-sky-500 text-white')">
                                <i class="fa-solid"
                                   :class="permissoes.perfil === 'administrador' ? 'fa-crown text-xs' : (permissoes.perfil === 'operador' ? 'fa-user-gear text-xs' : 'fa-book text-xs')"></i>
                            </div>
                            <div class="flex-1">
                                <strong class="font-black uppercase tracking-wider text-[11px]" x-show="permissoes.perfil === 'administrador'" x-cloak>
                                    Administrador — nenhum filtro se aplica.
                                </strong>
                                <div x-show="permissoes.perfil === 'administrador'" x-cloak>Lista abaixo é <strong>desabilitada</strong> (admin tem acesso TOTAL a tudo). Proteção ativa: <em>não pode ser bloqueado.</em></div>

                                <strong class="font-black uppercase tracking-wider text-[11px]" x-show="permissoes.perfil === 'operador'" x-cloak>
                                    Operador — Lista de NEGAR (Deny list).
                                </strong>
                                <div x-show="permissoes.perfil === 'operador'" x-cloak>Marcar um item abaixo = <strong>BLOQUEAR</strong> aquele módulo/ação. Padrão (nenhum marcado) = Tudo liberado.</div>

                                <strong class="font-black uppercase tracking-wider text-[11px]" x-show="permissoes.perfil === 'leitor'" x-cloak>
                                    Leitor — Lista de PERMITIR (Allow list).
                                </strong>
                                <div x-show="permissoes.perfil === 'leitor'" x-cloak>Marcar um item abaixo = <strong>LIBERAR ESCRITA</strong> naquele módulo/ação. Padrão (nenhum marcado) = Apenas leitura.</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto overflow-x-hidden px-5 sm:px-6 py-4 pb-6 min-h-0 overscroll-contain">
                        {{-- ÁRVORE: renderizada recursivamente por Blade (manejos → filhos → netos) --}}
                        <div class="space-y-4">
                            @foreach($arvore as $raiz)
                                @include('admin.usuarios._permissoes-node', ['node' => $raiz, 'nivel' => 0])
                            @endforeach
                        </div>
                    </div>

                    <div class="px-5 sm:px-6 py-3.5 bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:flex-row-reverse sm:items-center gap-3 shrink-0 sticky bottom-0 z-[2] shadow-[0_-6px_16px_-8px_rgba(0,0,0,0.1)]">
                        <button type="submit"
                                class="w-full sm:w-auto inline-flex justify-center items-center gap-1.5 rounded-xl px-6 py-3 text-sm font-black uppercase tracking-wider text-white bg-gradient-to-b from-primary-500 to-primary-700 hover:from-primary-400 hover:to-primary-600 border border-primary-500 shadow-sm shadow-primary-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all min-h-[46px]">
                            <i class="fa-solid fa-floppy-disk text-white"></i>
                            <span class="text-white whitespace-nowrap" x-text="permissoes.fromCriar ? 'Salvar tudo (criar usuário + permissões)' : 'Salvar Controle de Acesso'"></span>
                        </button>
                        <button type="button" @click="modoPermissoes = false" class="w-full sm:w-auto sm:mr-auto inline-flex justify-center items-center gap-1.5 rounded-xl px-5 py-3 text-sm font-bold border border-gray-300 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all min-h-[46px]">
                            Cancelar
                        </button>
                    </div>
                </form>
        </div>
    </div>

<script>
function usuariosRBAC() {
    const ARVORE_PERMISSOES = @json($arvore);

    // Coleta TODAS as chaves folha e pais (para cascatear)
    function chavesDoNode(nodes) {
        const list = [];
        function walk(n) {
            list.push(n.id);
            if (Array.isArray(n.children)) n.children.forEach(walk);
        }
        nodes.forEach(walk);
        return list;
    }
    function chavesFilhas(node) {
        const res = [];
        if (!node || !Array.isArray(node.children)) return res;
        function walk(n) {
            res.push(n.id);
            if (Array.isArray(n.children)) n.children.forEach(walk);
        }
        node.children.forEach(walk);
        return res;
    }
    function nodeById(id) {
        let found = null;
        function walk(arr) {
            for (const n of arr) {
                if (n.id === id) { found = n; return true; }
                if (Array.isArray(n.children) && walk(n.children)) return true;
            }
            return false;
        }
        walk(ARVORE_PERMISSOES);
        return found;
    }
    // IDs de checagens HUMANAS por string: Sistema.usuarios e Sistema.metas → sempre travados pra não-admin.
    const SOMENTE_ADMIN_LOCK = ['sistema.usuarios', 'sistema.metas'];

    return {
        // --- Flags abertura modais ---
        modoCriar: false,
        modoEditar: false,
        modoExcluir: false,
        modoPermissoes: false,

        // --- Estado Criar ---
        criarSenhaVisivel: false,
        criar: { usuario: '', senha: '', perfil: 'operador', permissoes: [] },

        // --- Estado Editar ---
        editar: { id: '', usuario: '', perfil: 'operador', senha: '' },

        // --- Estado Excluir ---
        excluir: { id: '', usuario: '' },

        // --- Estado Permissões (grande modal) ---
        permissoes: {
            id: '',
            nome: '',
            usuario: '',
            perfil: 'operador',
            permissoes: [],
            fromCriar: false
        },

        // ====== Helpers ======
        resetCriar() {
            this.criar = { usuario: '', senha: '', perfil: 'operador', permissoes: [] };
            this.criarSenhaVisivel = false;
        },

        abrirCriar() {
            this.resetCriar();
            this.modoCriar = true;
        },

        abrirEditar(user) {
            this.editar = {
                id: String(user.id),
                usuario: user.usuario || '',
                perfil: user.perfil || 'operador',
                senha: ''
            };
            this.modoEditar = true;
        },

        abrirExcluir(user) {
            this.excluir = { id: String(user.id), usuario: user.usuario || '' };
            this.modoExcluir = true;
        },

        abrirControleAcesso(user) {
            // Se vier do modal de CRIAR (fromCriar), temos que levar os dados de criar sincronizados com este.
            const fromCriar = !!user.fromCriar;
            const arr = Array.isArray(user.permissoes) ? user.permissoes.slice() : [];

            this.permissoes = {
                id: String(user.id ?? ''),
                nome: user.nome || '',
                usuario: user.usuario || '',
                perfil: user.perfil || 'operador',
                permissoes: arr,
                fromCriar: fromCriar
            };
            this.modoPermissoes = true;
            this.$nextTick(() => this.sincronizarCheckboxesFromState());
        },

        // --- Controle de acesso: ações ---
        onMudarPerfilControleAcesso() {
            // Ao trocar perfil → pergunta se quer limpar (pois a semântica inverte)
            this.permissoes.permissoes = [];
            this.$nextTick(() => this.sincronizarCheckboxesFromState());
        },

        temPermissao(chave) {
            return this.permissoes.permissoes.indexOf(chave) !== -1;
        },
        setPermissao(chave, valor) {
            const i = this.permissoes.permissoes.indexOf(chave);
            if (valor) {
                if (i === -1) this.permissoes.permissoes.push(chave);
            } else {
                if (i !== -1) this.permissoes.permissoes.splice(i, 1);
            }
        },
        isPermissaoLocked(chave) {
            if (this.permissoes.perfil === 'administrador') return true;
            return SOMENTE_ADMIN_LOCK.indexOf(chave) !== -1;
        },

        onToggleCheckbox(chave, $eventOuCheckbox) {
            if (this.isPermissaoLocked(chave)) return;
            // $eventOuCheckbox pode ser: Event / HTMLInputElement / undefined (fallback)
            let cb = null;
            if ($eventOuCheckbox && $eventOuCheckbox instanceof HTMLInputElement) {
                cb = $eventOuCheckbox;
            } else if ($eventOuCheckbox && $eventOuCheckbox.target && $eventOuCheckbox.target instanceof HTMLInputElement) {
                cb = $eventOuCheckbox.target;
            }
            // Fallback: buscar via data-perm-id (garante funcionar mesmo se Alpine passar undefined)
            if (!cb) {
                const sel = `[data-perm-id="${String(chave).replace(/"/g,'\\"')}"]`;
                const achado = document.querySelector(sel);
                if (achado instanceof HTMLInputElement) cb = achado;
            }
            // Se ainda assim não achar checkbox, INVERTE o estado atual como último recurso
            let valor;
            if (cb) {
                valor = !!cb.checked;
            } else {
                valor = !this.temPermissao(chave);
            }

            // Marcar chave direta
            this.setPermissao(chave, valor);

            // Cascata: se nó PAI tiver filhos → marca todos filhos
            const node = nodeById(chave);
            if (node && Array.isArray(node.children) && node.children.length > 0) {
                const filhas = chavesFilhas(node);
                filhas.forEach(f => this.setPermissao(f, valor));
            }
            // Cascata reversa: se todos filhos marcados → marca PAI (e vice versa)
            this.cascataPais();
            this.$nextTick(() => this.sincronizarCheckboxesFromState());
        },

        cascataPais() {
            // Passa por todos os nós e: se TODOS filhos estão marcados, marca pai; se NENHUM, desmarca pai
            function walk(childrenArr) {
                let algumaMarcada = false;
                let todasMarcadas = childrenArr.length > 0;
                for (const filho of childrenArr) {
                    if (Array.isArray(filho.children) && filho.children.length > 0) {
                        walk(filho.children);
                    }
                    const marc = (window.__permsAtuais ?? []).indexOf(filho.id) !== -1;
                    if (marc) algumaMarcada = true; else todasMarcadas = false;
                }
                return { algumaMarcada, todasMarcadas };
            }
            window.__permsAtuais = this.permissoes.permissoes.slice();
            for (const r of ARVORE_PERMISSOES) {
                if (Array.isArray(r.children)) walk(r.children);
            }
        },

        // Sincroniza checkbox DOM com estado permissoes.permissoes
        sincronizarCheckboxesFromState() {
            const marcadas = new Set(this.permissoes.permissoes);
            // Atualiza filhas dos pais (caso pai tenha marcado, filhas devem ser marcadas)
            document.querySelectorAll('[data-perm-id]').forEach(el => {
                const id = el.getAttribute('data-perm-id');
                if (!id) return;
                el.checked = marcadas.has(id);
            });
            // Atualiza pais: se todas filhas marcadas → pai marcado
            this.atualizaPaisIndeterminados();
        },

        atualizaPaisIndeterminados() {
            const marcadas = new Set(this.permissoes.permissoes);
            function nodeFilhosIds(node) {
                if (!node || !Array.isArray(node.children)) return [];
                return chavesFilhas(node);
            }
            function walk(arr) {
                for (const n of arr) {
                    if (Array.isArray(n.children) && n.children.length > 0) {
                        walk(n.children);
                        const filhos = chavesFilhas(n);
                        let marcadasCount = 0;
                        for (const f of filhos) if (marcadas.has(f)) marcadasCount++;
                        const cb = document.querySelector(`[data-perm-id="${n.id}"]`);
                        if (cb) {
                            if (marcadasCount === 0) {
                                cb.checked = false; cb.indeterminate = false;
                            } else if (marcadasCount === filhos.length) {
                                cb.checked = true; cb.indeterminate = false;
                            } else {
                                cb.checked = false; cb.indeterminate = true;
                            }
                        }
                    }
                }
            }
            walk(ARVORE_PERMISSOES);
        },

        marcarTodasPermissoes(marcar) {
            if (marcar) {
                // Todas as CHAVES exceto as lockadas (sistema.usuarios / sistema.metas)
                const all = chavesDoNode(ARVORE_PERMISSOES);
                const filtered = all.filter(id => !SOMENTE_ADMIN_LOCK.includes(id) && !['manejos','plantel','gestacao','maternidade','producao','analises','sistema'].includes(id));
                this.permissoes.permissoes = filtered;
            } else {
                this.permissoes.permissoes = [];
            }
            this.$nextTick(() => this.sincronizarCheckboxesFromState());
        },

        // --- Submit controle de acesso ---
        submeterPermissoes(e) {
            const form = e.target;
            // Limpa inputs antigos de permissões
            form.querySelectorAll('input[name="permissoes[]"]').forEach(i => i.remove());
            if (this.permissoes.fromCriar) {
                // Vamos submeter VIA MODAL CRIAR (usuário novo): precisamos criar inputs hidden e adicionar ao form CRIAR
                // Mas para simplicidade, sincronizamos os dados com this.criar e fechamos este modal.
                this.criar.perfil = this.permissoes.perfil;
                this.criar.permissoes = this.permissoes.permissoes.slice();
                this.modoPermissoes = false;
                // Não há submit aqui; o usuário clica em "Salvar usuário" no modal criar.
                return;
            }
            // Caso contrário, é um usuário existente — adiciona ao form e submete POST para a rota /usuarios/{id}/permissoes
            for (const chave of this.permissoes.permissoes) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'permissoes[]';
                inp.value = String(chave);
                form.appendChild(inp);
            }
            // 🔒 GARANTE envio de pelo menos 1 input permissoes[] MESMO VAZIO.
            //    Sem isto: quando nenhuma permissão está marcada (voltar padrão),
            //    o backend recebe permissoes=null e NÃO ATUALIZA (bug).
            if (this.permissoes.permissoes.length === 0) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'permissoes[]';
                inp.value = '';
                form.appendChild(inp);
            }
            form.submit();
        }
    };
}
</script>
@endsection
