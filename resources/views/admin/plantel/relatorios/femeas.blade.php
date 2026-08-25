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
        @if (!empty($isPdf))
            /* =========================================================
               CSS ESPECÍFICO PARA PDF (A4 Landscape, DomPDF safe)
               - Nenhum flex/grid, tudo tabela ou bloco
               - Larguras fixas em mm
               - Fontes padrão (helvetica)
               ========================================================= */
            @page { size: A4 landscape; margin: 10mm 8mm 10mm 8mm; }
            * { box-sizing: border-box; }
            html, body {
                margin: 0;
                padding: 0;
                font-family: helvetica, arial, sans-serif;
                color: #0f172a;
                font-size: 10px;
                background: #ffffff;
                width: 100%;
            }
            .pdf-header {
                width: 100%;
                margin-bottom: 6mm;
                padding-bottom: 3mm;
                border-bottom: 1.2px solid #cbd5e1;
            }
            .pdf-header table { width: 100%; border-collapse: collapse; }
            .pdf-header td { vertical-align: middle; }
            .pdf-header .brand {
                font-size: 16px;
                font-weight: 700;
                color: #7c2d12;
                letter-spacing: -0.3px;
            }
            .pdf-header .brand small {
                display: block;
                margin-top: 1mm;
                font-size: 9px;
                color: #475569;
                font-weight: normal;
                letter-spacing: 0;
            }
            .pdf-header .meta-right { text-align: right; font-size: 9px; color: #334155; }
            .pdf-header .meta-right .pill {
                display: inline-block;
                margin-bottom: 1mm;
                padding: 1.2mm 3mm;
                background: #7c2d12;
                color: #ffffff;
                font-size: 8px;
                font-weight: 700;
                letter-spacing: 0.4px;
                text-transform: uppercase;
                border-radius: 3mm;
            }

            .pdf-summary { width: 100%; border-collapse: separate; border-spacing: 2mm; margin-bottom: 5mm; }
            .pdf-summary td {
                width: 25%;
                padding: 3mm;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-left: 1.5mm solid #c2ffce;
                border-radius: 2mm;
                vertical-align: top;
            }
            .pdf-summary td.c1 { border-left-color: #78350f; }
            .pdf-summary td.c2 { border-left-color: #16a34a; }
            .pdf-summary td.c3 { border-left-color: #dc2626; }
            .pdf-summary td.c4 { border-left-color: #ca8a04; }
            .pdf-summary .lbl { font-size: 7.5px; text-transform: uppercase; color: #64748b; letter-spacing: 0.3px; font-weight: 700; }
            .pdf-summary .val { font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 0.6mm; }

            table.data {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                font-size: 8.8px;
            }
            table.data thead th {
                background: #78350f !important;
                color: #ffffff !important;
                font-weight: 700;
                text-align: left;
                padding: 2mm 1.5mm;
                font-size: 8px;
                text-transform: uppercase;
                letter-spacing: 0.15px;
                border: 1px solid #78350f;
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }
            table.data tbody td {
                padding: 1.8mm 1.5mm;
                border-bottom: 1px solid #f1f5f9;
                border-left: 1px solid #f8fafc;
                border-right: 1px solid #f8fafc;
                vertical-align: middle;
                color: #1e293b;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            table.data tbody tr:nth-child(even) td { background: #fafafa; }
            table.data tbody tr:last-child td { border-bottom: 1px solid #e2e8f0; }

            td.c-id { width: 5.5%; font-weight: 700; }
            td.c-id2 { width: 5.5%; color: #475569; }
            td.c-tipo { width: 5.5%; text-transform: capitalize; font-weight: 600; }
            td.c-raca { width: 8%; }
            td.c-local { width: 8%; color: #475569; }
            td.c-baia { width: 4.5%; text-align: center; font-weight: 700; }
            td.c-ciclo { width: 4.5%; text-align: center; font-weight: 800; color: #7c2d12; }
            td.c-estado { width: 10%; font-size: 8px; font-weight: 600; }
            td.c-peso { width: 6%; text-align: right; font-weight: 700; }
            td.c-idade { width: 5%; text-align: right; color: #475569; }
            td.c-data { width: 7%; color: #475569; }
            td.c-ultop { width: 14%; font-size: 7.8px; color: #334155; }
            td.c-status { width: 6.5%; }

            th.c-baia, th.c-ciclo, th.c-peso, th.c-idade { text-align: center; }
            th.c-peso, th.c-idade { text-align: right; }

            .chip {
                display: inline;
                padding: 0.6mm 2mm;
                border-radius: 2mm;
                font-size: 7.2px;
                font-weight: 700;
                border: 1px solid transparent;
                line-height: 1.4;
            }
            .chip-gest { background: #fef3c7; color: #92400e; border-color: #fde68a; }
            .chip-lact { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
            .chip-vazia { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
            .badge-ok { display: inline; padding: 0.6mm 2mm; border-radius: 2mm; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-size: 7.2px; font-weight: 700; }
            .badge-bad { display: inline; padding: 0.6mm 2mm; border-radius: 2mm; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 7.2px; font-weight: 700; }

            .empty { width: 100%; text-align: center; padding: 14mm 4mm; color: #64748b; }
            .empty .ico { font-size: 24px; color: #cbd5e1; margin-bottom: 2mm; }
            .empty strong { display: block; color: #334155; font-size: 11px; margin-bottom: 1mm; }

            .pdf-footer {
                width: 100%;
                margin-top: 5mm;
                padding-top: 2mm;
                border-top: 1px solid #e2e8f0;
                color: #94a3b8;
                font-size: 8px;
            }
            .pdf-footer td { vertical-align: middle; }
            .pdf-footer .right { text-align: right; color: #64748b; font-weight: 700; }
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
    {{-- =============== LAYOUT EXCLUSIVO PARA PDF =============== --}}
    <table class="pdf-header" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="brand">
                    <i class="fa-solid fa-venus"></i>&nbsp; Relatório de Plantel — Fêmeas
                    <small>Emitido em <strong>{{ $data_emissao }}</strong></small>
                </div>
            </td>
            <td class="meta-right">
                <span class="pill">Sui Control</span><br>
                MasterPig — Gestão de Suinocultura
            </td>
        </tr>
    </table>

    @php
        $total = $items->count();
        $ativas = $items->filter(fn($r) => !str_contains(strtolower($r['status'] ?? ''), 'inativo'))->count();
        $inativas = $total - $ativas;
        $gestantes = $items->filter(fn($r) => str_starts_with(mb_strtolower($r['estado'] ?? ''), 'gest'))->count();
    @endphp

    <table class="pdf-summary" cellpadding="0" cellspacing="0">
        <tr>
            <td class="c1"><div class="lbl">Total de Fêmeas</div><div class="val">{{ $total }}</div></td>
            <td class="c2"><div class="lbl">Ativas</div><div class="val" style="color:#166534;">{{ $ativas }}</div></td>
            <td class="c3"><div class="lbl">Inativas</div><div class="val" style="color:#991b1b;">{{ $inativas }}</div></td>
            <td class="c4"><div class="lbl">Gestantes</div><div class="val" style="color:#92400e;">{{ $gestantes }}</div></td>
        </tr>
    </table>

    <table class="data" cellpadding="0" cellspacing="0">
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

    <table class="pdf-footer" cellpadding="0" cellspacing="0">
        <tr>
            <td>© {{ date('Y') }} Sui Control — MasterPig. Processado em {{ $data_emissao }}.</td>
            <td class="right">Página 1</td>
        </tr>
    </table>
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
