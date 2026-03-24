<div x-data="calendarToggle" class="relative">
    <!-- Botão de Alternância -->
    <button 
        @click="toggleCalendar()"
        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition-all" 
        :class="calendarType === '1000_dias' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
    >
        <i class="fa-solid" :class="calendarType === '1000_dias' ? 'fa-calendar-check text-amber-600' : 'fa-calendar-days text-gray-500'"></i>
        <span x-text="calendarType === '1000_dias' ? '1000 dias' : 'Gregoriano'"></span>
    </button>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('calendarToggle', () => ({
        calendarType: localStorage.getItem('masterpig_calendar_type') || '1000_dias',
        
        toggleCalendar() {
            const newType = this.calendarType === 'gregoriano' ? '1000_dias' : 'gregoriano';
            const currentType = this.calendarType === 'gregoriano' ? 'Gregoriano' : '1000 dias';
            const targetType = newType === 'gregoriano' ? 'Gregoriano' : '1000 dias';
            
            if (confirm(`Deseja alterar o calendário de ${currentType} para ${targetType}?\\n\\nEsta alteração será aplicada em todo o sistema e a página será recarregada.`)) {
                this.calendarType = newType;
                localStorage.setItem('masterpig_calendar_type', newType);
                
                // Mostra overlay de carregamento
                this.showLoadingOverlay();
                
                fetch('{{ route('admin.criterios.store', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                    },
                    body: JSON.stringify({ criterio_calendario_tipo: newType })
                }).then((response) => {
                    if (!response.ok) {
                        throw new Error('Falha na resposta do servidor');
                    }
                    return response.json();
                }).then((data) => {
                    // Atualiza o localStorage novamente após sucesso da API
                    localStorage.setItem('masterpig_calendar_type', newType);
                    // Recarrega a página para aplicar as mudanças
                    window.location.reload();
                }).catch((error) => {
                    // Remove overlay em caso de erro
                    this.hideLoadingOverlay();
                    alert('Erro ao alterar calendário. Por favor, tente novamente.');
                    console.error('Erro ao alterar calendário:', error);
                });
            }
        },
        
        showLoadingOverlay() {
            // Cria overlay de blur
            const overlay = document.createElement('div');
            overlay.id = 'calendar-loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                transition: opacity 0.3s ease;
                opacity: 0;
            `;
            
            // Cria conteúdo do overlay
            const content = document.createElement('div');
            content.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    text-align: center;
                    border: 1px solid #e5e7eb;
                ">
                    <div style="
                        width: 40px;
                        height: 40px;
                        border: 4px solid #e5e7eb;
                        border-top: 4px solid #3b82f6;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 1rem;
                    "></div>
                    <h3 style="
                        font-size: 1.1rem;
                        font-weight: 600;
                        color: #1f2937;
                        margin: 0 0 0.5rem 0;
                    ">Atualizando calendário</h3>
                    <p style="
                        color: #6b7280;
                        margin: 0;
                        font-size: 0.9rem;
                    ">Aguarde enquanto aplicamos as alterações...</p>
                </div>
            `;
            
            overlay.appendChild(content);
            document.body.appendChild(overlay);
            
            // Animação de fade-in
            setTimeout(() => {
                overlay.style.opacity = '1';
            }, 10);
            
            // Adiciona animação de spin
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        },
        
        hideLoadingOverlay() {
            const overlay = document.getElementById('calendar-loading-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 300);
            }
        }
    }));
});
</script>
