<div x-data="calendarMenu" class="relative">
    <!-- Botão do Menu -->
    <button 
        @click="open = !open"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white hover:text-amber-100 hover:bg-amber-700 rounded-xl transition-colors"
        title="Calendário de 1000 dias"
    >
        <i class="fa-solid fa-calendar-alt text-white"></i>
        <span>Calendário</span>
        <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden"
        x-cloak
    >
        <!-- Cabeçalho -->
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Calendário</div>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="prevMonth()"
                        class="p-1 rounded-lg hover:bg-gray-200"
                        title="Mês anterior"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="text-sm font-medium text-gray-700">
                        <span x-text="monthNames[currentMonth]"></span> <span x-text="currentYear"></span>
                    </div>
                    <button 
                        @click="nextMonth()"
                        class="p-1 rounded-lg hover:bg-gray-200"
                        title="Próximo mês"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>


        <!-- Calendário -->
        <div class="p-3">
            <div class="grid grid-cols-7 gap-1 text-xs text-center">
                <!-- Dias da semana -->
                <template x-for="day in weekDays" :key="day">
                    <div class="font-medium text-gray-500 py-1" x-text="day"></div>
                </template>

                <!-- Dias do mês -->
                <template x-for="(day, index) in daysInMonth" :key="index">
                    <div 
                        class="aspect-square flex flex-col items-center justify-center text-sm border border-gray-100 rounded-lg"
                        :class="{
                            'opacity-50': !day.isCurrentMonth,
                            'font-semibold': day.isToday
                        }"
                        :title="day.tooltip"
                    >
                        <div class="text-sm font-medium" x-text="day.day"></div>
                        <div class="text-xs text-gray-600" x-show="day.isCurrentMonth && day.calendar1000Day" x-text="day.calendar1000Day"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/50">
            <div class="flex items-center justify-end text-sm">
                <div class="flex items-center gap-2">
                    <button 
                        type="button"
                        @click="open = false"
                        class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 rounded-lg hover:bg-gray-200"
                    >
                        Fechar
                    </button>
                    <button 
                        type="button"
                        @click="today()"
                        class="px-3 py-1.5 text-sm bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        Hoje
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir constantes e funções globais para o componente
    window.PIG_BASE_DATE = window.PIG_BASE_DATE || '1968-12-31';
    var PIG_BASE_DATE = window.PIG_BASE_DATE;

    function toPigDay(date) {
        if (!date) return null;
        const start = new Date(PIG_BASE_DATE + 'T00:00:00');
        const end = new Date(date);
        end.setHours(0, 0, 0, 0);
        const diff = Math.floor((end.getTime() - start.getTime()) / 86400000);
        
        // Dia PIG Absoluto = quantidade de dias desde 31/12/1968
        const absoluteDay = diff;
        
        // Dia PIG = últimos 3 dígitos do Dia PIG Absoluto
        return absoluteDay % 1000;
    }

    document.addEventListener('alpine:init', () => {
    Alpine.data('calendarMenu', () => ({
        open: false,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedDate: null,
        
        get monthNames() {
            return [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
        },

        get weekDays() {
            return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        },

        get daysInMonth() {
            const year = this.currentYear;
            const month = this.currentMonth;
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            const days = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            for (let i = 0; i < 42; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                
                const isCurrentMonth = date.getMonth() === month && date.getFullYear() === year;
                const isToday = date.toDateString() === today.toDateString();
                const isSelected = this.selectedDate && date.toDateString() === this.selectedDate.toDateString();
                
                // Calcular dia PIG usando a função corrigida
                const calendar1000Day = toPigDay(date);
                let range = '';
                let tooltip = '';
                
                if (isCurrentMonth) {
                    if (calendar1000Day >= 0 && calendar1000Day <= 100) {
                        range = '0-100';
                    } else if (calendar1000Day >= 101 && calendar1000Day <= 200) {
                        range = '101-200';
                    } else if (calendar1000Day >= 201 && calendar1000Day <= 300) {
                        range = '201-300';
                    } else if (calendar1000Day >= 301 && calendar1000Day <= 1000) {
                        range = '301-1000';
                    }
                    
                    tooltip = `Dia ${calendar1000Day} (${range})`;
                }

                days.push({
                    date: date,
                    day: date.getDate(),
                    isCurrentMonth: isCurrentMonth,
                    isToday: isToday,
                    isSelected: isSelected,
                    range: range,
                    calendar1000Day: calendar1000Day,
                    tooltip: tooltip || (isCurrentMonth ? `Dia ${date.getDate()}` : '')
                });
            }
            
            return days;
        },

        get selectedDateFormatted() {
            if (!this.selectedDate) return null;
            const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
            return this.selectedDate.toLocaleDateString('pt-BR', options);
        },

        get currentDayFormatted() {
            // Usar o dia PIG atual (924)
            const today = new Date();
            const currentPigDay = toPigDay(today.toISOString().split('T')[0]);
            let range = '';
            
            if (currentPigDay >= 0 && currentPigDay <= 100) {
                range = '0-100';
            } else if (currentPigDay >= 101 && currentPigDay <= 200) {
                range = '101-200';
            } else if (currentPigDay >= 201 && currentPigDay <= 300) {
                range = '201-300';
            } else if (currentPigDay >= 301 && currentPigDay <= 1000) {
                range = '301-1000';
            }
            
            return `Dia ${currentPigDay} (${range})`;
        },

        get todayRange() {
            // Usar o dia PIG atual (924)
            const today = new Date();
            const currentPigDay = toPigDay(today.toISOString().split('T')[0]);
            let range = '';
            
            if (currentPigDay >= 0 && currentPigDay <= 100) {
                range = '0-100';
            } else if (currentPigDay >= 101 && currentPigDay <= 200) {
                range = '101-200';
            } else if (currentPigDay >= 201 && currentPigDay <= 300) {
                range = '201-300';
            } else if (currentPigDay >= 301 && currentPigDay <= 1000) {
                range = '301-1000';
            }
            
            return range;
        },

        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },

        selectDay(date) {
            this.selectedDate = date;
            this.open = false;
            
            // Disparar evento para outros componentes
            window.dispatchEvent(new CustomEvent('calendar-day-selected', {
                detail: {
                    date: date,
                    formatted: this.selectedDateFormatted
                }
            }));
        },

        today() {
            this.selectedDate = new Date();
            this.selectedDate.setHours(0, 0, 0, 0);
            this.currentMonth = this.selectedDate.getMonth();
            this.currentYear = this.selectedDate.getFullYear();
        }
    }));
});
</script>