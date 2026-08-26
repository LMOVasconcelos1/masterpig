<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ficha da Ração - Sui Control</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 10mm 12mm 10mm; }
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-size: 11.5px;
            line-height: 1.4;
            color: #0a0a0a;
            background: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        .page {
            width: 100% !important;
            max-width: 190mm !important;
            min-height: 275mm !important;
            margin: 0 auto !important;
            padding: 0 !important;
            position: relative !important;
            box-sizing: border-box !important;
        }

        table.doc-header {
            width: 100% !important;
            border-collapse: collapse;
            border-bottom: 2px solid #0a0a0a;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        table.doc-header td.brand-col {
            vertical-align: top;
            width: 60%;
            padding: 0;
        }
        table.doc-header td.meta {
            vertical-align: top;
            text-align: right;
            max-width: 85mm;
            word-wrap: break-word;
            font-size: 8.5px;
            color: #2a2a2a;
            line-height: 1.45;
            padding: 0;
        }
        .brand-inner { line-height: 0; }
        .brand-inner img.brand-logo, .brand-inner .brand-logo {
            display: inline-block;
            vertical-align: middle;
            width: 40px;
            height: 40px;
            margin-right: 12px;
            object-fit: contain;
            opacity: 0.95;
        }
        .brand-inner .brand-logo-fallback {
            display: inline-block;
            vertical-align: middle;
            width: 40px;
            height: 40px;
            border: 1.5px solid #0a0a0a;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.05em;
            color: #0a0a0a;
            background: #ffffff;
            text-align: center;
            line-height: 37px;
            margin-right: 12px;
        }
        .brand-text {
            display: inline-block;
            vertical-align: middle;
        }
        .doc-header .brand-text .brand-name {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0a0a0a;
            line-height: 1.05;
        }
        .doc-header .brand-text .brand-sub {
            font-size: 9.5px;
            color: #2a2a2a;
            margin-top: 2px;
            letter-spacing: 0.03em;
        }
        .doc-header .doc-meta strong {
            color: #0a0a0a;
            font-weight: 700;
        }

        .doc-title-wrap {
            width: 100% !important;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .doc-title-wrap h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #0a0a0a;
            margin-bottom: 4px;
        }
        .doc-title-wrap .doc-subtitle {
            font-size: 10px;
            color: #2a2a2a;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .section {
            width: 100% !important;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .section-title-wrap {
            width: 100% !important;
            background: #fafafa;
            border-bottom: 1px solid #0a0a0a;
            padding: 6px 10px;
        }
        table.section-title {
            width: 100% !important;
            border-collapse: collapse;
        }
        table.section-title td.bar {
            width: 14px;
            padding: 0;
            vertical-align: middle;
        }
        table.section-title td.bar div {
            width: 3px;
            height: 11px;
            background: #0a0a0a;
        }
        table.section-title td.text {
            padding: 0 0 0 8px;
            vertical-align: middle;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #0a0a0a;
        }
        .box {
            width: 100% !important;
            border: 1px solid #0a0a0a;
            background: #ffffff;
            padding: 0;
            overflow: hidden;
        }

        .grid-info {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-info tr td {
            padding: 5px 6px;
            vertical-align: top;
            border-bottom: 1px dotted #a0a0a0;
        }
        .grid-info tr:last-child td { border-bottom: none; }
        .grid-info .label {
            font-size: 9.5px;
            font-weight: 700;
            color: #2a2a2a;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
            width: 34%;
        }
        .grid-info .value {
            font-size: 11.5px;
            font-weight: 600;
            color: #0a0a0a;
        }
        .grid-info .value.muted {
            color: #4b5563;
            font-weight: 500;
        }

        table.doc-footer {
            width: 100% !important;
            border-collapse: collapse;
            position: absolute !important;
            bottom: -2mm !important;
            left: 0 !important;
            right: 0 !important;
            border-top: 1px solid #0a0a0a;
            padding-top: 6px;
            font-size: 9px;
            color: #2a2a2a;
        }
        table.doc-footer td.left {
            text-align: left;
            padding: 6px 0 0 0;
            letter-spacing: 0.03em;
        }
        table.doc-footer td.right {
            text-align: right;
            padding: 6px 0 0 0;
            letter-spacing: 0.03em;
        }
        table.doc-footer strong {
            color: #0a0a0a;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="page">

        <table class="doc-header" cellspacing="0" cellpadding="0">
            <tr>
                <td class="brand-col">
                    <div class="brand-inner">
                        @if(!empty($logoDataUri))
                            <img class="brand-logo" src="{{ $logoDataUri }}" alt="Sui Control">
                        @else
                            <div class="brand-logo-fallback">SC</div>
                        @endif
                        <div class="brand-text">
                            <div class="brand-name">Sui Control</div>
                            <div class="brand-sub">Sistema de Gestão de Suinocultura</div>
                        </div>
                    </div>
                </td>
                <td class="meta">
                    <div><strong>Emitido em:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                    <div style="margin-top:3px;"><strong>Documento:</strong> Ficha Técnica da Ração</div>
                    <div style="margin-top:3px;"><strong>Ref.:</strong> {{ $racao->codigo }}</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <h1>Ficha Técnica da Ração</h1>
                    <div class="doc-subtitle">Formulação · Nutrição · Informações Comerciais</div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="box">
                <div class="section-title-wrap">
                    <table class="section-title" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="bar"><div></div></td>
                            <td class="text">Dados Gerais</td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Código</div></td>
                            <td><div class="value">{{ $racao->codigo }}</div></td>
                            <td><div class="label">Nome</div></td>
                            <td><div class="value">{{ $racao->nome }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Classificação</div></td>
                            <td><div class="value">{{ $racao->classificacao ?? '-' }}</div></td>
                            <td><div class="label">Tipo de Ração</div></td>
                            <td><div class="value">{{ optional($racao->tipoRacao)->nome ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Fase do Animal</div></td>
                            <td><div class="value">{{ $racao->fase_animal ?? '-' }}</div></td>
                            <td><div class="label">Observações</div></td>
                            <td><div class="value muted">{{ $racao->descricao ?? '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="box">
                <div class="section-title-wrap">
                    <table class="section-title" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="bar"><div></div></td>
                            <td class="text">Informações Nutricionais</td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Proteína Bruta (%)</div></td>
                            <td><div class="value">{{ $racao->proteina_bruta ?? '-' }}</div></td>
                            <td><div class="label">Energia Metabolizável</div></td>
                            <td><div class="value">{{ $racao->energia_metabolizavel ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Fibra</div></td>
                            <td><div class="value">{{ $racao->fibra ?? '-' }}</div></td>
                            <td><div class="label">Lisina</div></td>
                            <td><div class="value">{{ $racao->lisina ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Cálcio</div></td>
                            <td><div class="value">{{ $racao->calcio ?? '-' }}</div></td>
                            <td><div class="label">Fósforo</div></td>
                            <td><div class="value">{{ $racao->fosforo ?? '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="box">
                <div class="section-title-wrap">
                    <table class="section-title" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="bar"><div></div></td>
                            <td class="text">Informações Comerciais</td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Fornecedor</div></td>
                            <td><div class="value">{{ optional($racao->fornecedor)->nome ?? '-' }}</div></td>
                            <td><div class="label">Marca</div></td>
                            <td><div class="value">{{ $racao->marca ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Custo por Kg</div></td>
                            <td><div class="value">{{ $racao->custo_por_kg ?? '-' }}</div></td>
                            <td><div class="label">Unidade de Compra</div></td>
                            <td><div class="value">{{ $racao->unidade_compra ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Peso Embalagem</div></td>
                            <td><div class="value">{{ $racao->peso_embalagem ?? '-' }}</div></td>
                            <td><div class="label">Estoque (Kg)</div></td>
                            <td><div class="value">{{ $racao->estoque ?? '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                <td class="right">Página 1 / 1</td>
            </tr>
        </table>
    </div>
</body>
</html>
