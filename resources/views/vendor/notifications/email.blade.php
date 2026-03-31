@component('mail::message')
@if (!empty($logoCid))
<div style="text-align:center; margin: 8px 0 18px;">
    <img src="{{ $logoCid }}" alt="Sui Control" style="height: 64px; max-height: 64px; width: auto;">
</div>
@endif

# Olá!

Clique no botão abaixo para verificar seu endereço de e-mail.

@component('mail::button', ['url' => $actionUrl])
Verificar e-mail
@endcomponent

Se você não criou uma conta, basta ignorar este e-mail.

Atenciosamente,
Sui Control

@slot('subcopy')
Se estiver com dificuldade para clicar no botão, copie e cole este link no seu navegador:
<br>
<span style="word-break: break-all;">{{ $actionUrl }}</span>
@endslot
@endcomponent
