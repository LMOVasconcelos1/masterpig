import re

with open('temp_dashboard_original.php', 'r', encoding='utf-8') as f:
    content = f.read()

# PASSO 2: Remover variáveis do x-data (toastOpen, toastMessage, toastType)
old_vars = """        tab: (function(){ const t = (new URLSearchParams(window.location.search).get('tab') || 'visao'); if (t === 'relatorios') return 'analise'; return ['visao','lancamentos','acompanhamento','analise'].includes(t) ? t : 'visao'; })(), 
        toastOpen: false, 
        toastMessage: '', 
        toastType: 'success',
        calendarType: localStorage.getItem('masterpig_calendar_type') || '1000_dias',"""

new_vars = """        tab: (function(){ const t = (new URLSearchParams(window.location.search).get('tab') || 'visao'); if (t === 'relatorios') return 'analise'; return ['visao','lancamentos','acompanhamento','analise'].includes(t) ? t : 'visao'; })(), 
        calendarType: localStorage.getItem('masterpig_calendar_type') || '1000_dias',"""

content = content.replace(old_vars, new_vars)

# PASSO 2: Remover listener x-init e bloco HTML do toast
old_block = r'''    }"
     x-init="
        window.addEventListener('toast', (e) => { toastMessage = e.detail.message; toastType = e.detail.type || 'success'; toastOpen = true; setTimeout(() => toastOpen = false, 4000); });
     "
     class="space-y-6">
<div 
    x-show="toastOpen" 
    x-transition:enter="transform ease-out duration-500 transition"
    x-transition:enter-start="translate-y-[-100%] opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
    class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white dark:bg-gray-800 shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4"
    :class="toastType === 'success' ? 'border-green-500' : 'border-red-500'"
    x-cloak
>
    <div class="p-4">

        <div class="flex items-start">
            <div class="flex-shrink-0">
                <template x-if="toastType === 'success'">
                    <i class="fa-solid fa-circle-check text-green-400 text-xl"></i>
                </template>
                <template x-if="toastType === 'error'">
                    <i class="fa-solid fa-circle-xmark text-red-400 text-xl"></i>
                </template>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 " x-text="toastMessage"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="toastOpen = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <span class="sr-only">Fechar</span>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Header & Topbar -->'''

new_block = r'''    }"
     class="space-y-6">
<!-- Header & Topbar -->'''

content = content.replace(old_block, new_block)

# PASSO 4: Substituir todas as chamadas window.dispatchEvent(... toast ...) por toast()
pattern = r"window\.dispatchEvent\(new CustomEvent\('toast',\s*\{\s*detail:\s*\{\s*message:\s*(.*?),\s*type:\s*'([^']*)'\s*\}\s*\}\)\);"

def replace_match(m):
    msg = m.group(1)
    typ = m.group(2)
    return f"toast({msg}, '{typ}');"

content = re.sub(pattern, replace_match, content, flags=re.DOTALL)

with open('resources/views/dashboard.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print('Feito!')
