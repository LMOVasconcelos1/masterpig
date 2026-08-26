<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Cio de Leitoas</title>
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
            margin-bottom: 10px !important;
            page-break-inside: avoid;
        }
        .doc-title-wrap h1 {
            font-size: 18px !important;
            font-weight: 800;
            letter-spacing: 0.14em;
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
        table.data-table {
            width: 100% !important;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #0a0a0a;
            font-size: 8px !important;
        }
        table.data-table thead th {
            background: #0a0a0a;
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 4px 2px !important;
            text-align: center;
            border-right: 1px solid #2a2a2a;
            white-space: normal;
            word-break: break-word;
            font-size: 8px !important;
            line-height: 1.15;
        }
        table.data-table thead th:last-child { border-right: none; }
        table.data-table tbody td {
            padding: 2px 2px !important;
            border-right: 1px solid #0a0a0a;
            border-bottom: 1px solid #0a0a0a;
            height: {{ $rowHeightMm ?? 6.5 }}mm;
            vertical-align: middle;
            text-align: center;
            color: #0a0a0a;
        }
        table.data-table tbody td:last-child { border-right: none; }
        table.data-table tbody tr:last-child td { border-bottom: none; }
        table.data-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        .col-leitoa { width: 18%; }
        .col-data { width: 13%; }
        .col-vacina { width: 15%; }
        table.doc-footer {
            width: 100% !important;
            border-collapse: collapse;
            position: absolute !important;
            bottom: -2mm !important;
            left: 0 !important;
            right: 0 !important;
            border-top: 1px solid #0a0a0a;
            padding-top: 6px;
            font-size: 8px !important;
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
                    <div><strong>Documento:</strong> Formulário</div>
                    <div style="margin-top:3px;"><strong>Área:</strong> Plantel / Análises</div>
                    <div style="margin-top:3px;"><strong>Data:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <h1>Formulário de Cio de Leitoas</h1>
                    <div class="doc-subtitle">Controle de Cios e Vacinação</div>
                </td>
            </tr>
        </table>

        <table class="data-table" cellpadding="0" cellspacing="0">
            <colgroup>
                <col style="width: 18%;">
                <col style="width: 13%;">
                <col style="width: 13%;">
                <col style="width: 13%;">
                <col style="width: 13%;">
                <col style="width: 15%;">
                <col style="width: 15%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-leitoa">Leitoa</th>
                    <th class="col-data">Data 1º cio</th>
                    <th class="col-data">Data 2º cio</th>
                    <th class="col-data">Data 3º cio</th>
                    <th class="col-data">Data 4º cio</th>
                    <th class="col-vacina">1ª Dose Vacina</th>
                    <th class="col-vacina">2ª Dose Vacina</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < ($linhas ?? 24); $i++)
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                <td class="right">Página 1 / 1</td>
            </tr>
        </table>
    </div>
</body>
</html>
