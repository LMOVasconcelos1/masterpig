<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ficha da Ração - Sui Control</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111827; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 18px; }
        .brand { font-size: 18px; font-weight: bold; color: #1e40af; }
        .subtitle { color: #6b7280; margin-top: 4px; }
        .meta { margin-top: 6px; color: #6b7280; font-size: 10px; }
        .section-title { margin-top: 16px; font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.08em; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 8px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 6px 8px; vertical-align: top; }
        .label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }
        .value { font-size: 12px; color: #111827; font-weight: 600; margin-top: 2px; }
        .muted { color: #9ca3af; font-weight: 400; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Sui Control</div>
        <div class="subtitle">Ficha Técnica da Ração</div>
        <div class="meta">
            Emitido em: {{ $data_emissao }} | Código: {{ $racao->codigo }}
        </div>
    </div>

    <div class="section-title">Dados Gerais</div>
    <div class="box">
        <table class="grid">
            <tr>
                <td width="25%">
                    <div class="label">Código</div>
                    <div class="value">{{ $racao->codigo }}</div>
                </td>
                <td width="75%">
                    <div class="label">Nome</div>
                    <div class="value">{{ $racao->nome }}</div>
                </td>
            </tr>
            <tr>
                <td width="33%">
                    <div class="label">Classificação</div>
                    <div class="value">{{ $racao->classificacao }}</div>
                </td>
                <td width="33%">
                    <div class="label">Tipo de ração</div>
                    <div class="value">{{ optional($racao->tipoRacao)->nome ?? '-' }}</div>
                </td>
                <td width="34%">
                    <div class="label">Fase do animal</div>
                    <div class="value">{{ $racao->fase_animal }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Informações Nutricionais</div>
    <div class="box">
        <table class="grid">
            <tr>
                <td width="33%">
                    <div class="label">Proteína bruta (%)</div>
                    <div class="value">{{ $racao->proteina_bruta ?? '-' }}</div>
                </td>
                <td width="33%">
                    <div class="label">Energia metabolizável</div>
                    <div class="value">{{ $racao->energia_metabolizavel ?? '-' }}</div>
                </td>
                <td width="34%">
                    <div class="label">Fibra</div>
                    <div class="value">{{ $racao->fibra ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td width="33%">
                    <div class="label">Lisina</div>
                    <div class="value">{{ $racao->lisina ?? '-' }}</div>
                </td>
                <td width="33%">
                    <div class="label">Cálcio</div>
                    <div class="value">{{ $racao->calcio ?? '-' }}</div>
                </td>
                <td width="34%">
                    <div class="label">Fósforo</div>
                    <div class="value">{{ $racao->fosforo ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Informações Comerciais</div>
    <div class="box">
        <table class="grid">
            <tr>
                <td width="45%">
                    <div class="label">Fornecedor</div>
                    <div class="value">{{ optional($racao->fornecedor)->nome ?? '-' }}</div>
                </td>
                <td width="25%">
                    <div class="label">Marca</div>
                    <div class="value">{{ $racao->marca ?? '-' }}</div>
                </td>
                <td width="30%">
                    <div class="label">Custo por kg</div>
                    <div class="value">{{ $racao->custo_por_kg ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td width="33%">
                    <div class="label">Unidade de compra</div>
                    <div class="value">{{ $racao->unidade_compra ?? '-' }}</div>
                </td>
                <td width="33%">
                    <div class="label">Peso da embalagem</div>
                    <div class="value">{{ $racao->peso_embalagem ?? '-' }}</div>
                </td>
                <td width="34%">
                    <div class="label">Estoque (kg)</div>
                    <div class="value">{{ $racao->estoque ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        © {{ date('Y') }} Sui Control - Sistema de Gestão de Suinocultura
    </div>
</body>
</html>
