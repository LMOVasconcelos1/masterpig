<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Cobertura</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 10mm 10mm 10mm; }
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-size: 11px;
            line-height: 1.4;
            color: #0a0a0a;
            background: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        .page {
            width: 100% !important;
            max-width: 277mm !important;
            min-height: 192mm !important;
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
            page-break-inside: avoid;
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
            overflow-wrap: break-word;
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
            width: 25%;
        }
        .grid-info .value {
            font-size: 11px;
            font-weight: 600;
            color: #0a0a0a;
        }
        table.data-table {
            width: 100% !important;
            border-collapse: collapse;
            border: 1px solid #0a0a0a;
            font-size: 10px;
        }
        table.data-table thead th {
            background: #0a0a0a;
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 7px 4px;
            text-align: center;
            border-right: 1px solid #2a2a2a;
            white-space: nowrap;
            font-size: 9px;
        }
        table.data-table thead th:last-child { border-right: none; }
        table.data-table tbody td {
            padding: 6px 4px;
            border-right: 1px solid #0a0a0a;
            border-bottom: 1px solid #0a0a0a;
            text-align: center;
            vertical-align: middle;
            color: #0a0a0a;
            white-space: nowrap;
            height: 26px;
        }
        table.data-table tbody td:last-child { border-right: none; }
        table.data-table tbody tr:last-child td { border-bottom: none; }
        table.data-table tbody td.col-label {
            text-align: left;
            font-weight: 700;
            padding-left: 8px;
            background: #fafafa;
        }
        table.data-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        table.signature-area {
            width: 100% !important;
            border-collapse: collapse;
            margin-top: 40px;
        }
        table.signature-area td.sign-box {
            width: 33.3%;
            text-align: center;
            padding: 0 10px;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #0a0a0a;
            margin-bottom: 5px;
            height: 30px;
        }
        .signature-label {
            font-size: 10px;
            color: #2a2a2a;
            font-weight: 600;
            letter-spacing: 0.04em;
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
        @media screen {
            body { padding: 16px; background: #f3f4f6; }
            .page { max-width: 1400px; margin: 0 auto; background: #fff; padding: 16px; border: 1px solid #e5e7eb; min-height: auto; width: 100%; position: relative; }
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
                            <div class="brand-logo" style="display:inline-block;vertical-align:middle;width:40px;height:40px;border:1.5px solid #0a0a0a;font-weight:800;font-size:15px;letter-spacing:0.05em;color:#0a0a0a;background:#ffffff;text-align:center;line-height:37px;margin-right:12px;">SC</div>
                        @endif
                        <div class="brand-text">
                            <div class="brand-name">Sui Control</div>
                            <div class="brand-sub">Sistema de Gestão de Suinocultura</div>
                        </div>
                    </div>
                </td>
                <td class="meta">
                    <div><strong>Documento:</strong> Formulário</div>
                    <div style="margin-top:3px;"><strong>Área:</strong> Gestação / Cobertura</div>
                    <div style="margin-top:3px;"><strong>Data:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <h1>Formulário de Cobertura</h1>
                    <div class="doc-subtitle">Controle de Coberturas — Matrizes e Leitoas</div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="box">
                <div class="section-title-wrap">
                    <table class="section-title" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="bar"><div></div></td>
                            <td class="text">Filtros Aplicados</td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Tipo</div></td>
                            <td><div class="value">{{ $tipo ?? 'Todos' }}</div></td>
                            <td><div class="label">Ordenar por</div></td>
                            <td><div class="value">{{ ucfirst($ordenar ?? 'Matriz') }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Matriz</div></td>
                            <td><div class="value">{{ $matriz ?? 'Todas' }}</div></td>
                            <td><div class="label">Leitoa</div></td>
                            <td><div class="value">{{ $leitoa ?? 'Todas' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Dias Vazias (Início)</div></td>
                            <td><div class="value">{{ $dias_vazias_inicio ?? '-' }}</div></td>
                            <td><div class="label">Dias Vazias (Fim)</div></td>
                            <td><div class="value">{{ $dias_vazias_fim ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Idade (Início)</div></td>
                            <td><div class="value">{{ $idade_inicio ?? '-' }}</div></td>
                            <td><div class="label">Idade (Fim)</div></td>
                            <td><div class="value">{{ $idade_fim ?? '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Quantidade de Linhas</div></td>
                            <td><div class="value">{{ $quantidade ?? 10 }}</div></td>
                            <td><div class="label">Granja</div></td>
                            <td><div class="value">{{ \App\Models\Configuracao::getGranjaAtual() }}</div></td>
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
                            <td class="text">Registro de Coberturas</td>
                        </tr>
                    </table>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Nº</th>
                            <th style="width: 14%;">Matriz</th>
                            <th style="width: 18%;">Data</th>
                            <th style="width: 16%;">Macho</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= ($quantidade ?? 10); $i++)
                        <tr>
                            <td class="col-label" style="text-align:center;">{{ $i }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <table class="signature-area" cellspacing="0" cellpadding="0">
            <tr>
                <td class="sign-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Responsável Técnico</div>
                </td>
                <td class="sign-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Data: ____/____/______</div>
                </td>
                <td class="sign-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Assinatura</div>
                </td>
            </tr>
        </table>

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                <td class="right">Formulário gerado em {{ date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>


</body>
</html>
