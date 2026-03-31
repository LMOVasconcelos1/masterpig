<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (!empty($logoCid))
                <img src="{{ $logoCid }}" alt="Sui Control" style="height: 48px; max-height: 48px; width: auto;">
            @else
                {{ $slot }}
            @endif
        </a>
    </td>
</tr>
