<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Parto - Maternidade</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 10mm 12mm 10mm; }
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-size: 10px !important;
            line-height: 1.3 !important;
            color: #0a0a0a;
            background: #ffffff;
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
            margin-bottom: 10px;
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
            font-size: 7.5px !important;
            color: #2a2a2a;
            line-height: 1.45;
            padding: 0;
        }
        .brand-inner { line-height: 0; }
        .brand-inner img.brand-logo, .brand-inner .brand-logo {
            display: inline-block;
            vertical-align: middle;
            width: 34px !important;
            height: 34px !important;
            margin-right: 12px;
            object-fit: contain;
            opacity: 0.95;
        }
        .brand-text {
            display: inline-block;
            vertical-align: middle;
        }
        .doc-header .brand-text .brand-name {
            font-size: 15px !important;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #0a0a0a;
            line-height: 1.05;
        }
        .doc-header .brand-text .brand-sub {
            font-size: 8.5px !important;
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
            margin-bottom: 8px !important;
            page-break-inside: avoid;
        }
        .doc-title-wrap h1 {
            font-size: 17px !important;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #0a0a0a;
            margin-bottom: 4px;
        }
        .doc-title-wrap .doc-subtitle {
            font-size: 8.5px !important;
            color: #2a2a2a;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .granja-header {
            width: 100%;
            text-align: right;
            font-size: 11px;
            font-weight: 800;
            color: #0a0a0a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .localizacao-label {
            font-size: 9px;
            color: #2a2a2a;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .localizacao-label strong { color: #0a0a0a; }
        table.data-table {
            width: 100% !important;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #0a0a0a;
            font-size: 8px !important;
        }
        table.data-table thead th {
            background: #f2f2f2;
            color: #0a0a0a;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 3px 1px !important;
            text-align: center;
            border-right: 1px solid #0a0a0a;
            border-bottom: 1px solid #0a0a0a;
            white-space: normal;
            word-break: break-word;
            font-size: 7.5px !important;
            line-height: 1.15;
            vertical-align: middle;
        }
        table.data-table thead th:last-child { border-right: none; }
        table.data-table tbody td {
            padding: 1px 1px !important;
            border-right: 1px solid #0a0a0a;
            border-bottom: 1px solid #0a0a0a;
            height: 8.4mm;
            vertical-align: middle;
            text-align: center;
            color: #0a0a0a;
        }
        table.data-table tbody td:last-child { border-right: none; }
        table.data-table tbody tr:last-child td { border-bottom: none; }
        .intervencao {
            margin-top: 10px;
            font-size: 8.5px !important;
            color: #0a0a0a;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .intervencao strong { text-transform: uppercase; letter-spacing: 0.05em; }
        table.doc-footer {
            width: 100% !important;
            border-collapse: collapse;
            position: absolute !important;
            bottom: -2mm !important;
            left: 0 !important;
            right: 0 !important;
            border-top: 1px solid #0a0a0a;
            padding-top: 6px;
            font-size: 7.5px !important;
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
            .page { max-width: 980px; margin: 0 auto; background: #fff; padding: 16px; border: 1px solid #e5e7eb; min-height: auto; width: 100%; }
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
                    <div><strong>Documento:</strong> Formulário Operacional</div>
                    <div style="margin-top:3px;"><strong>Área:</strong> Maternidade</div>
                    <div style="margin-top:3px;"><strong>Módulo:</strong> Partos</div>
                    <div style="margin-top:3px;"><strong>Emitido:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="left" style="vertical-align: middle;">
                    <h1>Formulário Parto</h1>
                    <div class="doc-subtitle">Registro diário de partos — maternidade</div>
                </td>
                <td align="right" style="vertical-align: top;">
                    <div class="granja-header">{{ $granja }}</div>
                </td>
            </tr>
        </table>

        <div class="localizacao-label">
            <strong>Localização:</strong>
            <span style="text-decoration: underline; padding: 0 80px 0 4px;">&nbsp;</span>
        </div>

        <table class="data-table" cellspacing="0" cellpadding="0">
            <colgroup>
                <col style="width: 14%;">
                <col style="width: 3.5%;">
                <col style="width: 7.5%;">
                <col style="width: 5.5%;">
                <col style="width: 5%;">
                <col style="width: 5%;">
                <col style="width: 4.5%;">
                <col style="width: 5%;">
                <col style="width: 4.5%;">
                <col style="width: 6%;">
                <col style="width: 6%;">
                <col style="width: 8.5%;">
                <col style="width: 9%;">
                <col style="width: 20%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Identificação<br>primária</th>
                    <th>TI*</th>
                    <th>Data</th>
                    <th>H.<br>Início</th>
                    <th>H.<br>Fim</th>
                    <th>Vivos</th>
                    <th>Nat.</th>
                    <th>Mum.</th>
                    <th>B.V.</th>
                    <th>P.<br>Total</th>
                    <th>P.<br>Matriz</th>
                    <th>Escore<br>corporal</th>
                    <th>Funcionário</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $linhas = $linhas ?? 25;
                @endphp
                @for($i = 1; $i <= $linhas; $i++)
                    <tr>
                        <td></td><td></td><td></td><td></td><td></td>
                        <td></td><td></td><td></td><td></td><td></td>
                        <td></td><td></td><td></td><td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="intervencao">
            <strong>Tipo de Intervenção:</strong> 1 - Induzido &nbsp;&nbsp; 2 - Distócico
        </div>

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">
                    <strong>{{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</strong>
                </td>
                <td class="right">
                    <span style="font-size: 7px; letter-spacing: 0.05em; text-transform: uppercase; font-weight: 700;">1 / 1</span>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
