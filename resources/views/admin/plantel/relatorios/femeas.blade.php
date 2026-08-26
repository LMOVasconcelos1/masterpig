<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Fêmeas - Sui Control</title>
    @if (empty($isPdf))
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif
    <style>
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        @if (!empty($isPdf))
            @page { size: A4 landscape; margin: 10mm 8mm 12mm 8mm; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            html, body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                font-size: 10px;
                line-height: 1.4;
                color: #0a0a0a;
                background: #ffffff;
            }
            .page {
                width: 297mm;
                min-height: 210mm;
                margin: 0 auto;
                padding: 8mm 8mm 14mm 8mm;
                position: relative;
                box-sizing: border-box;
            }
            table.doc-header {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 2px solid #0a0a0a;
                margin-bottom: 14px;
                padding-bottom: 10px;
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
                width: 100%;
                margin-bottom: 16px;
                page-break-inside: avoid;
            }
            .doc-title-wrap h1 {
                font-size: 20px;
                font-weight: 800;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #0a0a0a;
                margin-bottom: 4px;
            }
            .doc-title-wrap .doc-subtitle {
                font-size: 9.5px;
                color: #2a2a2a;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .stats-grid, .summary {
                width: 100%;
                border-collapse: separate;
                border-spacing: 8px;
                margin-bottom: 14px;
            }
            .stats-grid td, .summary td {
                border: 1px solid #0a0a0a;
                background: #fff;
                text-align: center;
                padding: 10px 6px 8px 6px;
                vertical-align: top;
                width: 25%;
            }
            .stats-grid .stat-num, .summary .num {
                font-size: 20px;
                font-weight: 800;
                color: #0a0a0a;
                line-height: 1;
                min-height: 24px;
            }
            .stats-grid .stat-lbl, .summary .lbl {
                font-size: 8px;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #2a2a2a;
                margin-top: 5px;
                border-top: 1px solid #0a0a0a;
                padding-top: 5px;
            }
            .stat-card { border: none; padding: 0; }
            table.data-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                border: 1px solid #0a0a0a;
                font-size: 8.5px;
            }
            table.data-table thead th {
                background: #0a0a0a;
                color: #ffffff;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                padding: 5px 3px;
                text-align: center;
                border-right: 1px solid #2a2a2a;
                white-space: nowrap;
                font-size: 7.5px;
            }
            table.data-table thead th:last-child { border-right: none; }
            table.data-table tbody td {
                padding: 4px 3px;
                border-right: 1px solid #0a0a0a;
                border-bottom: 1px solid #0a0a0a;
                vertical-align: middle;
                color: #0a0a0a;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            table.data-table tbody td:last-child { border-right: none; }
            table.data-table tbody tr:last-child td { border-bottom: none; }
            table.data-table tbody tr:nth-child(even) td {
                background: #fafafa;
            }
            td.c-id { width: 5.5%; font-weight: 700; text-align: left; }
            td.c-id2 { width: 5.5%; color: #2a2a2a; text-align: left; }
            td.c-tipo { width: 5.5%; text-transform: capitalize; font-weight: 600; text-align: left; }
            td.c-raca { width: 8%; text-align: left; }
            td.c-local { width: 8%; color: #2a2a2a; text-align: left; }
            td.c-baia { width: 4.5%; text-align: center; font-weight: 700; }
            td.c-ciclo { width: 4.5%; text-align: center; font-weight: 800; color: #0a0a0a; }
            td.c-estado { width: 10%; font-size: 7.5px; font-weight: 600; text-align: left; }
            td.c-peso { width: 6%; text-align: right; font-weight: 700; }
            td.c-idade { width: 5%; text-align: right; color: #2a2a2a; }
            td.c-data { width: 7%; color: #2a2a2a; text-align: left; }
            td.c-ultop { width: 14%; font-size: 7.3px; color: #2a2a2a; text-align: left; }
            td.c-status { width: 6.5%; text-align: center; }
            th.c-id, th.c-id2, th.c-tipo, th.c-raca, th.c-local, th.c-estado, th.c-data, th.c-ultop { text-align: left !important; }
            th.c-baia, th.c-ciclo { text-align: center !important; }
            th.c-peso, th.c-idade { text-align: right !important; }
            .chip {
                display: inline;
                padding: 1px 5px;
                border-radius: 2px;
                font-size: 7px;
                font-weight: 700;
                border: 1px solid #0a0a0a;
                line-height: 1.3;
                background: #ffffff;
                color: #0a0a0a;
            }
            .badge-ok, .badge-bad {
                display: inline;
                padding: 1px 5px;
                border-radius: 2px;
                font-size: 7px;
                font-weight: 700;
                border: 1px solid #0a0a0a;
                background: #ffffff;
                color: #0a0a0a;
            }
            .badge-bad { font-style: italic; }
            .empty {
                width: 100%;
                text-align: center;
                padding: 14mm 4mm;
                color: #2a2a2a;
                border: 1px solid #0a0a0a;
                background: #fafafa;
            }
            .empty .ico { font-size: 22px; color: #2a2a2a; margin-bottom: 2mm; }
            .empty strong { display: block; color: #0a0a0a; font-size: 11px; margin-bottom: 1mm; }
            table.doc-footer {
                width: 100%;
                border-collapse: collapse;
                position: absolute;
                bottom: 10mm;
                left: 8mm;
                right: 8mm;
                border-top: 1px solid #0a0a0a;
                padding-top: 6px;
                font-size: 8.5px;
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
        @else
            /* =========================================================
               CSS PARA HTML (browser)
               ========================================================= */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color: #0f172a; font-size: 12px; background: #ffffff; }

        .page { width: 100%; padding: 28px 32px; }

        .header { text-align: left; margin-bottom: 26px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; }
        .header-left h1 { margin: 0; color: #7c2d12; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; }
        .header-left p { margin: 6px 0 0; color: #64748b; font-size: 12.5px; font-weight: 500; }
        .header-left p strong { color: #334155; }
        .header-meta { text-align: right; font-size: 11px; color: #475569; line-height: 1.6; }
        .header-meta .tag { display: inline-block; padding: 4px 10px; border-radius: 9999px; background: #7c2d12; color: #fff; font-weight: 700; font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px; }

        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 22px; }
        .summary .card { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #c2ffce; border-radius: 10px; padding: 12px 14px; }
        .summary .card .label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
        .summary .card .value { font-size: 19px; font-weight: 800; color: #0f172a; margin-top: 4px; }

        .table-wrap { width: 100%; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead th {
            background: #78350f !important;
            color: #ffffff !important;
            font-weight: 700;
            text-align: left;
            padding: 11px 10px;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
            border-bottom: 2px solid #78350f;
        }
        tbody td {
            padding: 9px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #1e293b;
            font-size: 11.5px;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        tbody tr:nth-child(even) td { background: #fafafa; }
        tbody tr:hover td { background: #fffbeb; }
        tbody tr:last-child td { border-bottom: none; }

        td.c-id { font-weight: 800; color: #0f172a; width: 6%; }
        td.c-id2 { width: 6%; color: #475569; }
        td.c-tipo { width: 6%; text-transform: capitalize; font-weight: 600; }
        td.c-raca { width: 9%; }
        td.c-local { width: 8%; color: #475569; }
        td.c-baia { width: 5%; text-align: center; font-weight: 700; }
        td.c-ciclo { width: 5%; text-align: center; font-weight: 800; color: #7c2d12; }
        td.c-estado { width: 11%; font-size: 10.5px; font-weight: 600; }
        td.c-peso { width: 7%; text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; }
        td.c-idade { width: 6%; text-align: right; font-variant-numeric: tabular-nums; color: #475569; }
        td.c-data { width: 8%; color: #475569; }
        td.c-ultop { width: 14%; font-size: 10.5px; color: #334155; }
        td.c-status { width: 9%; }

        th.c-baia, th.c-ciclo, th.c-peso, th.c-idade { text-align: center; }
        th.c-peso, th.c-idade { text-align: right; }

        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 9999px; font-size: 10px; font-weight: 700; letter-spacing: 0.01em; white-space: nowrap; }
        .badge-active { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-inactive { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .estado-chip { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }
        .estado-gestante { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .estado-lactante { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .estado-vazia { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 44px; color: #cbd5e1; margin-bottom: 12px; display: block; }
        .empty-state strong { display: block; color: #334155; font-size: 15px; margin-bottom: 4px; }

        .footer { margin-top: 30px; padding-top: 16px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10.5px; display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; }
        .footer .pager { font-weight: 700; color: #64748b; }

        .toolbar { position: sticky; top: 0; z-index: 20; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); border-bottom: 1px solid #e2e8f0; padding: 14px 32px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin: -28px -32px 24px -32px; }
        .toolbar .t-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .toolbar .t-right { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; border: 1px solid #e2e8f0; background: #ffffff; color: #0f172a; cursor: pointer; transition: all 0.18s ease; }
        .btn:hover { border-color: #cbd5e1; background: #f8fafc; transform: translateY(-1px); box-shadow: 0 4px 10px -6px rgba(15,23,42,0.15); }
        .btn-primary { background: #78350f; border-color: #78350f; color: #ffffff; }
        .btn-primary:hover { background: #7c2d12; border-color: #7c2d12; color: #ffffff; }
        .btn-pdf { color: #991b1b; }
        .btn-pdf i { color: #ef4444; }
        .btn-csv { color: #065f46; }
        .btn-csv i { color: #10b981; }

        @media print {
            .toolbar { display: none !important; }
            .page { padding: 10mm 8mm !important; }
            .header { margin-bottom: 16px; padding-bottom: 12px; }
            .summary { margin-bottom: 14px; }
            table { font-size: 10px; }
            thead th { padding: 8px 6px; font-size: 9.5px; }
            tbody td { padding: 6px 6px; font-size: 10px; }
            .footer { margin-top: 14px; padding-top: 10px; }
        }
        @media (max-width: 768px) {
            .page { padding: 18px 14px; }
            .header { grid-template-columns: 1fr; }
            .header-meta { text-align: left; }
            .summary { grid-template-columns: repeat(2, 1fr); }
            .toolbar { padding: 12px 14px; margin: -18px -14px 18px -14px; }
            .btn { padding: 8px 10px; font-size: 11px; }
            .btn span.lbl { display: none; }
        }
        @endif
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
@if (!empty($isPdf))
    {{-- =============== LAYOUT EXCLUSIVO PARA PDF (PADRÃO P&B) =============== --}}
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
                    <div><strong>Emitido em:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                    <div style="margin-top:3px;"><strong>Documento:</strong> Relatório de Plantel</div>
                    <div style="margin-top:3px;"><strong>Segmento:</strong> Fêmeas</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <h1>Relatório de Plantel — Fêmeas</h1>
                    <div class="doc-subtitle">Controle Geral de Matrizes e Leitoas</div>
                </td>
            </tr>
        </table>

        @php
            $total = $items->count();
            $ativas = $items->filter(fn($r) => !str_contains(strtolower($r['status'] ?? ''), 'inativo'))->count();
            $inativas = $total - $ativas;
            $gestantes = $items->filter(fn($r) => str_starts_with(mb_strtolower($r['estado'] ?? ''), 'gest'))->count();
        @endphp

        <table class="stats-grid" cellspacing="8">
            <tbody>
                <tr>
                    <td><div class="stat-num">{{ $total }}</div><div class="stat-lbl">Total de Fêmeas</div></td>
                    <td><div class="stat-num">{{ $ativas }}</div><div class="stat-lbl">Ativas</div></td>
                    <td><div class="stat-num">{{ $inativas }}</div><div class="stat-lbl">Inativas</div></td>
                    <td><div class="stat-num">{{ $gestantes }}</div><div class="stat-lbl">Gestantes</div></td>
                </tr>
            </tbody>
        </table>

        <table class="data-table" cellpadding="0" cellspacing="0">
            <colgroup>
                <col style="width:5.5%">
                <col style="width:5.5%">
                <col style="width:5.5%">
                <col style="width:8%">
                <col style="width:8%">
                <col style="width:4.5%">
                <col style="width:4.5%">
                <col style="width:10%">
                <col style="width:6%">
                <col style="width:5%">
                <col style="width:7%">
                <col style="width:14%">
                <col style="width:6.5%">
            </colgroup>
            <thead>
                <tr>
                    <th class="c-id">ID</th>
                    <th class="c-id2">ID 2</th>
                    <th class="c-tipo">Tipo</th>
                    <th class="c-raca">Raça</th>
                    <th class="c-local">Local</th>
                    <th class="c-baia">Baia</th>
                    <th class="c-ciclo">Cic</th>
                    <th class="c-estado">Estado</th>
                    <th class="c-peso">Peso</th>
                    <th class="c-idade">Idade</th>
                    <th class="c-data">Compra</th>
                    <th class="c-ultop">Última Operação</th>
                    <th class="c-status">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $row)
                @php
                    $estadoLower = mb_strtolower($row['estado'] ?? '');
                    if (str_starts_with($estadoLower, 'gest')) { $chip = 'chip-gest'; }
                    elseif (str_starts_with($estadoLower, 'lact')) { $chip = 'chip-lact'; }
                    else { $chip = 'chip-vazia'; }
                    $badge = str_contains(strtolower($row['status'] ?? ''), 'inativo') ? 'badge-bad' : 'badge-ok';
                @endphp
                <tr>
                    <td class="c-id">{{ $row['id_primaria'] }}</td>
                    <td class="c-id2">{{ $row['id_secundaria'] ?? '-' }}</td>
                    <td class="c-tipo">{{ $row['tipo'] ?? '-' }}</td>
                    <td class="c-raca">{{ $row['raca'] ?? '-' }}</td>
                    <td class="c-local">{{ $row['localizacao'] ?? '-' }}</td>
                    <td class="c-baia">{{ $row['baia'] ?? '-' }}</td>
                    <td class="c-ciclo">{{ $row['ciclo'] }}</td>
                    <td class="c-estado"><span class="chip {{ $chip }}">{{ $row['estado'] }}</span></td>
                    <td class="c-peso">{{ $row['peso'] }}</td>
                    <td class="c-idade">{{ $row['idade'] }}</td>
                    <td class="c-data">{{ $row['data_compra'] ?? '-' }}</td>
                    <td class="c-ultop">{{ $row['ultima_operacao'] ?? '-' }}</td>
                    <td class="c-status"><span class="{{ $badge }}">{{ $row['status'] ?? '-' }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="13">
                        <div class="empty">
                            <div class="ico"><i class="fa-regular fa-folder-open"></i></div>
                            <strong>Nenhum registro encontrado</strong>
                            Tente ajustar os filtros utilizados para visualizar mais resultados.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                <td class="right">Página 1 / 1</td>
            </tr>
        </table>
    </div>
@else
    {{-- =============== LAYOUT PARA NAVEGADOR / IMPRESSÃO DIRETA =============== --}}
    <div class="page">
        <div class="toolbar">
            <div class="t-left">
                <a href="{{ route('admin.relatorios.plantel.femeas.filter') }}" class="btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span class="lbl">Alterar filtros</span>
                </a>
                <div class="text-xs text-slate-500 font-semibold">
                    <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                    Use o botão <strong class="text-slate-700">Imprimir</strong> do navegador para gerar PDF se preferir.
                </div>
            </div>
            <div class="t-right">
                <a class="btn btn-pdf" href="{{ route('admin.relatorios.plantel.femeas', array_merge(request()->all(), ['format' => 'pdf']), false) }}">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="lbl">PDF</span>
                </a>
                <a class="btn btn-csv" href="{{ route('admin.relatorios.plantel.femeas', array_merge(request()->all(), ['format' => 'csv']), false) }}">
                    <i class="fa-solid fa-file-csv"></i>
                    <span class="lbl">CSV</span>
                </a>
                <button type="button" onclick="window.print()" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i>
                    <span class="lbl">Imprimir</span>
                </button>
            </div>
        </div>

        <header class="header">
            <div class="header-left">
                <h1><i class="fa-solid fa-venus mr-2 text-amber-700"></i>Relatório de Plantel — Fêmeas</h1>
                <p>Emitido em <strong>{{ $data_emissao }}</strong></p>
            </div>
            <div class="header-meta">
                <span class="tag">Sui Control</span>
                <div>MasterPig — Gestão de Suinocultura</div>
            </div>
        </header>

        @php
            $total = $items->count();
            $ativas = $items->filter(fn($r) => !str_contains(strtolower($r['status'] ?? ''), 'inativo'))->count();
            $inativas = $total - $ativas;
            $gestantes = $items->filter(fn($r) => str_starts_with(mb_strtolower($r['estado'] ?? ''), 'gest'))->count();
        @endphp

        <section class="summary">
            <div class="card" style="border-left-color: #78350f;">
                <div class="label">Total de Fêmeas</div>
                <div class="value">{{ $total }}</div>
            </div>
            <div class="card" style="border-left-color: #16a34a;">
                <div class="label">Ativas</div>
                <div class="value" style="color: #166534;">{{ $ativas }}</div>
            </div>
            <div class="card" style="border-left-color: #dc2626;">
                <div class="label">Inativas</div>
                <div class="value" style="color: #991b1b;">{{ $inativas }}</div>
            </div>
            <div class="card" style="border-left-color: #ca8a04;">
                <div class="label">Gestantes</div>
                <div class="value" style="color: #92400e;">{{ $gestantes }}</div>
            </div>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="c-id">ID Prim.</th>
                        <th class="c-id2">ID Sec.</th>
                        <th class="c-tipo">Tipo</th>
                        <th class="c-raca">Raça</th>
                        <th class="c-local">Localização</th>
                        <th class="c-baia">Baia</th>
                        <th class="c-ciclo">Ciclo</th>
                        <th class="c-estado">Estado</th>
                        <th class="c-peso">Peso</th>
                        <th class="c-idade">Idade</th>
                        <th class="c-data">Compra</th>
                        <th class="c-ultop">Última Operação</th>
                        <th class="c-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $row)
                    @php
                        $estadoLower = mb_strtolower($row['estado'] ?? '');
                        if (str_starts_with($estadoLower, 'gest')) { $estadoClass = 'estado-gestante'; }
                        elseif (str_starts_with($estadoLower, 'lact')) { $estadoClass = 'estado-lactante'; }
                        else { $estadoClass = 'estado-vazia'; }
                    @endphp
                    <tr>
                        <td class="c-id">{{ $row['id_primaria'] }}</td>
                        <td class="c-id2">{{ $row['id_secundaria'] ?? '-' }}</td>
                        <td class="c-tipo">{{ $row['tipo'] ?? '-' }}</td>
                        <td class="c-raca">{{ $row['raca'] ?? '-' }}</td>
                        <td class="c-local">{{ $row['localizacao'] ?? '-' }}</td>
                        <td class="c-baia">{{ $row['baia'] ?? '-' }}</td>
                        <td class="c-ciclo">{{ $row['ciclo'] }}</td>
                        <td class="c-estado"><span class="estado-chip {{ $estadoClass }}">{{ $row['estado'] }}</span></td>
                        <td class="c-peso">{{ $row['peso'] }}</td>
                        <td class="c-idade">{{ $row['idade'] }}</td>
                        <td class="c-data">{{ $row['data_compra'] ?? '-' }}</td>
                        <td class="c-ultop">{{ $row['ultima_operacao'] ?? '-' }}</td>
                        <td class="c-status">
                            <span class="badge {{ str_contains(strtolower($row['status'] ?? ''), 'inativo') ? 'badge-inactive' : 'badge-active' }}">
                                {{ $row['status'] ?? '-' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13">
                            <div class="empty-state">
                                <i class="fa-regular fa-folder-open"></i>
                                <strong>Nenhum registro encontrado</strong>
                                Tente ajustar os filtros utilizados para visualizar mais resultados.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="footer">
            <div>© {{ date('Y') }} Sui Control — MasterPig. Documento processado em {{ $data_emissao }}.</div>
            <div class="pager">Página 1</div>
        </footer>
    </div>
@endif
</body>
</html>
