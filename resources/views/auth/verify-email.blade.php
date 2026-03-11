<x-guest-layout>
    <div class="sm:max-w-lg w-full mx-auto">
        <div class="text-center mb-6">
            @if (file_exists(public_path('logo.png')))
                <img src="/logoSemPalavra.png" alt="MasterPig" class="mx-auto w-32 h-32 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @else
                <img src="/favicon.ico" alt="MasterPig" class="mx-auto w-24 h-24 object-contain transition-transform duration-300 hover:scale-105 drop-shadow" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
            @endif
            <div class="mt-3">
                <h1 class="text-xl font-bold text-gray-800">Verifique seu e-mail</h1>
                <p class="text-sm text-gray-500">Clique no link que enviamos para sua caixa de entrada.</p>
            </div>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 font-medium text-sm text-green-600 text-center">
                Um novo link de verificação foi enviado para o seu e-mail.
            </div>
        @endif

        <div class="bg-white/90 backdrop-blur rounded-3xl shadow-lg border border-gray-100 p-6 transition-all duration-300 hover:shadow-xl hover:border-primary-200">
            <div class="text-sm text-gray-600 mb-4">
                Obrigado por se inscrever! Antes de começar, verifique seu e-mail clicando no link que te enviamos. Se não receber, você pode reenviar abaixo.
            </div>

            <div class="mt-2 flex items-center justify-between">
                <form method="POST" action="{{ route('verification.send', absolute: false) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
                        Reenviar link de verificação
                    </button>
                </form>

                <form method="POST" action="{{ route('logout', absolute: false) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-primary-200 shadow-sm px-4 py-2 bg-white text-sm font-semibold text-primary-700 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-transform duration-200 hover:scale-[1.02]">
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
