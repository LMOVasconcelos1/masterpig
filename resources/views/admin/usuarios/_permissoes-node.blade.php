@php
/**
 * Partial RECURSIVO para renderizar um nó da árvore de permissões.
 *
 * Variáveis recebidas:
 *   - $node  : array com estrutura ['id' => 'chave.permissao', 'titulo' => 'Label', 'icone' => 'fa-solid fa-xxx', 'children' => [...]]
 *   - $nivel : int (0 = raiz, 1 = filho, 2 = neto, 3 = bisneto)
 */
if (! isset($nivel)) $nivel = 0;
$paddingLeft = match(true) {
    $nivel <= 0 => 'pl-0',
    $nivel === 1 => 'pl-5',
    $nivel === 2 => 'pl-10',
    default     => 'pl-14',
};

$isRoot = ($nivel === 0);
$temFilhos = ! empty($node['children']);

// Cores por nível / se é travado / se é pai com filhos
$tituloCorBase = match(true) {
    $isRoot => 'text-gray-900 dark:text-gray-100 text-[15px] font-black uppercase tracking-wider',
    $nivel === 1 => 'text-primary-800 dark:text-primary-300 text-sm font-bold',
    $nivel === 2 => 'text-primary-700 dark:text-primary-400 text-[13.5px] font-semibold',
    default     => 'text-gray-700 dark:text-gray-300 text-[13px] font-medium',
};
$rowBg = match(true) {
    $isRoot     => 'bg-gradient-to-r from-gray-50 via-white to-white dark:from-gray-800 dark:via-gray-900 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 mb-3 shadow-sm',
    $temFilhos  => 'border-l-2 border-primary-200 dark:border-primary-700 pl-3 py-1.5 mt-2 mb-2',
    default     => 'py-1.5',
};
$iconeSize = $isRoot ? 'text-base' : 'text-xs';
@endphp

<div class="{{ $paddingLeft }} {{ $rowBg }}" data-perm-parent="{{ $node['id'] }}">
    <label class="flex items-start gap-3 cursor-pointer select-none group">
        {{-- Checkbox --}}
        <div class="flex-shrink-0 pt-0.5">
            <input type="checkbox"
                   class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 focus:ring-offset-0 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
                   data-perm-id="{{ $node['id'] }}"
                   x-on:change="onToggleCheckbox('{{ $node['id'] }}', $event)"
                   :disabled="isPermissaoLocked('{{ $node['id'] }}')"
                   :checked="temPermissao('{{ $node['id'] }}')">
        </div>

        {{-- Ícone + Título --}}
        <div class="min-w-0 flex-1 flex items-start gap-2.5">
            @if(!empty($node['icone']))
                <div class="flex-shrink-0 mt-0.5
                            {{ $isRoot
                                ? 'w-8 h-8 rounded-xl bg-gradient-to-br from-primary-500 via-primary-600 to-primary-800 text-white flex items-center justify-center shadow shadow-primary-900/20'
                                : 'w-5 h-5 rounded-lg bg-primary-50 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center border border-primary-100 dark:border-primary-800' }}">
                    <i class="fa-solid {{ $node['icone'] }} {{ $iconeSize }}"></i>
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="{{ $tituloCorBase }} truncate">
                    {{ $node['titulo'] }}
                </div>
                @if(!empty($node['descricao']))
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">
                        {{ $node['descricao'] }}
                    </div>
                @endif
                @if(!empty($node['lock']) || in_array($node['id'], ['sistema.usuarios','sistema.metas']))
                    <div class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9.5px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                        <i class="fa-solid fa-lock text-[9px]"></i>
                        Somente Administrador
                    </div>
                @endif
            </div>
            @if($temFilhos)
                <div class="flex-shrink-0 pt-1 text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">
                    {{ count($node['children']) }} {{ count($node['children']) === 1 ? 'item' : 'itens' }}
                </div>
            @endif
        </div>
    </label>

    {{-- FILHOS (recursivo) --}}
    @if($temFilhos)
        <div class="mt-2 space-y-1 {{ $isRoot ? 'pl-1 sm:pl-3' : '' }}">
            @foreach($node['children'] as $filho)
                @include('admin.usuarios._permissoes-node', [
                    'node'  => $filho,
                    'nivel' => $nivel + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
