// Script para verificar se Alpine.js está carregado corretamente
(function() {
    console.log('Verificando Alpine.js...');
    
    // Verifica se Alpine.js está carregado
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js não está carregado!');
        
        // Tenta carregar Alpine.js manualmente
        console.log('Tentando carregar Alpine.js manualmente...');
        
        // Carrega Alpine.js de CDN como fallback
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
        script.defer = true;
        script.onload = function() {
            console.log('Alpine.js carregado com sucesso do CDN!');
        };
        script.onerror = function() {
            console.error('Falha ao carregar Alpine.js do CDN!');
        };
        document.head.appendChild(script);
    } else {
        console.log('Alpine.js já está carregado:', Alpine.version);
    }
    
    // Verifica se as funções globais estão disponíveis
    if (typeof toPigDay === 'undefined') {
        console.error('Função toPigDay não está disponível!');
    } else {
        console.log('Função toPigDay está disponível');
    }
})();
