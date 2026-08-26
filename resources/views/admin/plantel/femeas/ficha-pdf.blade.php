<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ficha da Fêmea - Sui Control</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 10mm 12mm 10mm; }
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-family: 'Helvetica', 'Arial', sans-serif !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
            color: #0a0a0a !important;
            background: #ffffff !important;
            -webkit-font-smoothing: antialiased !important;
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

        /* ============ HEADER ============ */
        table.doc-header {
            width: 100% !important;
            border-collapse: collapse !important;
            border-bottom: 2px solid #0a0a0a !important;
            margin-bottom: 12px !important;
            padding-bottom: 8px !important;
        }
        table.doc-header td {
            vertical-align: top !important;
            padding: 0 !important;
        }
        table.doc-header td.meta {
            text-align: right !important;
            max-width: 85mm !important;
            word-wrap: break-word !important;
            font-size: 7.5px !important;
            color: #2a2a2a !important;
            line-height: 1.45 !important;
        }
        table.doc-header td.meta strong {
            color: #0a0a0a !important;
            font-weight: 700 !important;
        }
        .brand-inner {
            display: inline-block !important;
        }
        .brand-inner img.brand-logo,
        .brand-inner .brand-logo {
            display: inline !important;
            vertical-align: middle !important;
            width: 40px !important;
            height: 40px !important;
            margin-right: 12px !important;
        }
        .brand-inner .brand-text {
            display: inline-block !important;
            vertical-align: middle !important;
        }
        .brand-name {
            font-size: 15px !important;
            font-weight: 800 !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
            color: #0a0a0a !important;
            line-height: 1.05 !important;
        }
        .brand-sub {
            font-size: 8.5px !important;
            color: #2a2a2a !important;
            margin-top: 2px !important;
            letter-spacing: 0.03em !important;
        }

        /* ============ TITLE ============ */
        .doc-title {
            width: 100% !important;
            text-align: center !important;
            margin-bottom: 10px !important;
            page-break-inside: avoid !important;
        }
        .doc-title table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .doc-title table td {
            text-align: center !important;
        }
        .doc-title h1 {
            font-size: 18px !important;
            font-weight: 800 !important;
            letter-spacing: 0.14em !important;
            text-transform: uppercase !important;
            color: #0a0a0a !important;
            margin-bottom: 4px !important;
        }
        .doc-title .doc-subtitle {
            font-size: 8.5px !important;
            color: #2a2a2a !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
        }

        /* ============ SECTIONS ============ */
        .section {
            width: 100% !important;
            margin-bottom: 7px !important;
            page-break-inside: avoid !important;
        }
        .section-title {
            width: 100% !important;
            background: #fafafa !important;
            padding: 4px 8px !important;
            border-bottom: 1px solid #0a0a0a !important;
        }
        .section-title table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .section-title td.bar {
            width: 14px !important;
        }
        .section-title td.bar div {
            width: 3px !important;
            height: 11px !important;
            background: #0a0a0a !important;
        }
        .section-title td.text {
            font-size: 9.5px !important;
            font-weight: 800 !important;
            letter-spacing: 0.1em !important;
            text-transform: uppercase !important;
            color: #0a0a0a !important;
            padding-left: 4px !important;
        }

        .box {
            width: 100% !important;
            border: 1px solid #0a0a0a !important;
            background: #ffffff !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* ============ GRID INFO (2 cols) ============ */
        .grid-info {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .grid-info tr td {
            padding: 3px 4px !important;
            vertical-align: top !important;
            border-bottom: 1px dotted #a0a0a0 !important;
        }
        .grid-info tr:last-child td { border-bottom: none !important; }
        .grid-info .label {
            font-size: 8.5px !important;
            font-weight: 700 !important;
            color: #2a2a2a !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
            width: 34% !important;
        }
        .grid-info .value {
            font-size: 10px !important;
            font-weight: 600 !important;
            color: #0a0a0a !important;
        }
        .grid-info .value.muted {
            color: #4b5563 !important;
            font-weight: 500 !important;
        }
        .grid-info .value.status-ativa {
            font-weight: 800 !important;
            color: #0a0a0a !important;
            text-transform: uppercase !important;
        }
        .grid-info .value.status-inativa {
            font-weight: 700 !important;
            color: #2a2a2a !important;
            text-transform: uppercase !important;
            font-style: italic !important;
            border-bottom: 1px solid #2a2a2a !important;
            padding-bottom: 0.5px !important;
        }

        /* ============ STATS ============ */
        .stats-grid, .summary {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 3px !important;
        }
        .stats-grid td, .summary td {
            border: 1px solid #0a0a0a !important;
            background: #fff !important;
            text-align: center !important;
            padding: 6px 4px 5px 4px !important;
            vertical-align: top !important;
            width: 25% !important;
        }
        .stats-grid .stat-num, .summary .num {
            font-size: 16px !important;
            font-weight: 800 !important;
            color: #0a0a0a !important;
            line-height: 1 !important;
            min-height: 20px !important;
        }
        .stats-grid .stat-num.dash {
            color: #4b5563 !important;
            font-weight: 600 !important;
        }
        .stats-grid .stat-lbl, .summary .lbl {
            font-size: 7.5px !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            color: #2a2a2a !important;
            margin-top: 5px !important;
            border-top: 1px solid #0a0a0a !important;
            padding-top: 5px !important;
        }
        .stat-card { border: none !important; padding: 0 !important; }

        /* ============ TABLES ============ */
        table.data-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            border: 1px solid #0a0a0a !important;
            font-size: 7.5px !important;
        }
        table.data-table thead th {
            background: #0a0a0a !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            letter-spacing: 0.06em !important;
            text-transform: uppercase !important;
            padding: 4px 2px !important;
            font-size: 7px !important;
            text-align: center !important;
            border-right: 1px solid #2a2a2a !important;
            white-space: nowrap !important;
        }
        table.data-table thead th:last-child { border-right: none !important; }
        table.data-table tbody td {
            padding: 3px 2px !important;
            border-right: 1px solid #0a0a0a !important;
            border-bottom: 1px solid #0a0a0a !important;
            text-align: center !important;
            vertical-align: middle !important;
            color: #0a0a0a !important;
            white-space: nowrap !important;
            font-size: 7.5px !important;
        }
        table.data-table tbody td:last-child { border-right: none !important; }
        table.data-table tbody tr:last-child td { border-bottom: none !important; }
        table.data-table tbody td.col-label {
            text-align: left !important;
            font-weight: 700 !important;
            padding-left: 8px !important;
            background: #fafafa !important;
        }
        table.data-table tbody tr:nth-child(even) td {
            background: #fafafa !important;
        }
        table.data-table .na {
            color: #4b5563 !important;
            font-weight: 500 !important;
        }

        .no-data {
            border: 1px solid #0a0a0a !important;
            padding: 16px 12px !important;
            text-align: center !important;
            font-style: italic !important;
            color: #2a2a2a !important;
            background: #fafafa !important;
            font-size: 11px !important;
        }

        /* ============ FOOTER ============ */
        .doc-footer {
            position: absolute !important;
            bottom: -2mm !important;
            left: 0 !important;
            right: 0 !important;
            padding-top: 6px !important;
            border-top: 1px solid #0a0a0a !important;
            font-size: 8px !important;
            color: #2a2a2a !important;
            letter-spacing: 0.03em !important;
        }
        .doc-footer table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .doc-footer td {
            vertical-align: middle !important;
        }
        .doc-footer td.right {
            text-align: right !important;
        }
        .doc-footer strong {
            color: #0a0a0a !important;
            font-weight: 700 !important;
        }
    </style>
</head>
<body>
    <div class="page">

        <!-- ========== HEADER ========== -->
        <table class="doc-header">
            <tr>
                <td class="brand-col">
                    <div class="brand-inner">
                        @if(!empty($logoDataUri))
                            <img class="brand-logo" src="{{ $logoDataUri }}" alt="Sui Control">
                        @else
                            <div class="brand-logo" style="display:inline-block;width:40px;height:40px;border:1.5px solid #0a0a0a;line-height:40px;text-align:center;font-weight:800;font-size:15px;color:#0a0a0a;background:#fff;vertical-align:middle;">SC</div>
                        @endif
                        <div class="brand-text" style="display:inline-block;vertical-align:middle;">
                            <div class="brand-name">Sui Control</div>
                            <div class="brand-sub">Sistema de Gestão de Suinocultura</div>
                        </div>
                    </div>
                </td>
                <td class="meta">
                    <div><strong>Emitido em:</strong> {{ $emitidoEm ?? now()->format('d/m/Y H:i') }}</div>
                    <div style="margin-top:3px;"><strong>Documento:</strong> Ficha da Fêmea</div>
                    <div style="margin-top:3px;"><strong>Ref.:</strong> {{ $femea->id_primaria ?: $femea->id_secundaria ?: $femea->id }}</div>
                </td>
            </tr>
        </table>

        <!-- ========== TÍTULO PRINCIPAL ========== -->
        <div class="doc-title">
            <table>
                <tr>
                    <td>
                        <h1>Ficha da Fêmea</h1>
                        <div class="doc-subtitle">Controle Reprodutivo e Produtivo — Matriz / Leitoa</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ========== IDENTIFICAÇÃO ========== -->
        <div class="section">
            <div class="box">
                <div class="section-title">
                    <table cellspacing="0" cellpadding="0"><tr>
                        <td class="bar"><div></div></td>
                        <td class="text">IDENTIFICAÇÃO</td>
                    </tr></table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">ID Primária</div></td>
                            <td><div class="value">{{ $femea->id_primaria ?: '-' }}</div></td>
                            <td><div class="label">ID Secundária</div></td>
                            <td><div class="value">{{ $femea->id_secundaria ?: '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Tipo</div></td>
                            <td><div class="value">{{ ucfirst($femea->tipo_compra ?? '-') }}</div></td>
                            <td><div class="label">Raça / Genética</div></td>
                            <td><div class="value">{{ $femea->raca_nome ?: '-' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Status</div></td>
                            <td>
                                <div class="value {{ str_contains($status ?? '', 'tiva') ? (str_contains(strtolower($status), 'ativa') ? 'status-ativa' : 'status-inativa') : '' }}">
                                    {{ $status ?: '-' }}
                                </div>
                            </td>
                            <td><div class="label">Fornecedor</div></td>
                            <td><div class="value">{{ $femea->fornecedor_nome ?: '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== DADOS CRONOLÓGICOS ========== -->
        <div class="section">
            <div class="box">
                <div class="section-title">
                    <table cellspacing="0" cellpadding="0"><tr>
                        <td class="bar"><div></div></td>
                        <td class="text">DADOS CRONOLÓGICOS</td>
                    </tr></table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Nascimento</div></td>
                            <td><div class="value">{{ \App\Services\PigCycleService::formatDisplayDate($femea->data_nascimento) }}</div></td>
                            <td><div class="label">Compra / Entrada</div></td>
                            <td><div class="value">{{ \App\Services\PigCycleService::formatDisplayDate($femea->data_compra) }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">Localização</div></td>
                            <td><div class="value">{{ $femea->localizacao ?: '-' }}</div></td>
                            <td><div class="label">Baia / Gaiola</div></td>
                            <td><div class="value">{{ $femea->baia ?: '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== CONTROLE DE PESO ========== -->
        <div class="section">
            <div class="box">
                <div class="section-title">
                    <table cellspacing="0" cellpadding="0"><tr>
                        <td class="bar"><div></div></td>
                        <td class="text">CONTROLE DE PESO</td>
                    </tr></table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="grid-info">
                        <tr>
                            <td><div class="label">Peso na Compra</div></td>
                            <td><div class="value">{{ $femea->peso_compra ? number_format($femea->peso_compra, 1, ',', '.') . ' kg' : '-' }}</div></td>
                            <td><div class="label">Peso Atual</div></td>
                            <td><div class="value">{{ $femea->peso_atual ? number_format($femea->peso_atual, 1, ',', '.') . ' kg' : '-' }}</div></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== ESTATÍSTICAS REPRODUTIVAS ========== -->
        <div class="section">
            <div class="box">
                <div class="section-title">
                    <table cellspacing="0" cellpadding="0"><tr>
                        <td class="bar"><div></div></td>
                        <td class="text">ESTATÍSTICAS REPRODUTIVAS</td>
                    </tr></table>
                </div>
                <div style="padding: 10px 12px;">
                    <table class="stats-grid" cellspacing="4" style="margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <td><div class="stat-num">{{ $total_ciclos ?? 0 }}</div><div class="stat-lbl">Partos</div></td>
                                <td><div class="stat-num {{ empty($media_dias_gestacao) ? 'dash' : '' }}">{{ $media_dias_gestacao ?: '-' }}</div><div class="stat-lbl">Gest. Média (dias)</div></td>
                                <td><div class="stat-num {{ empty($media_dias_lactacao) ? 'dash' : '' }}">{{ $media_dias_lactacao ?: '-' }}</div><div class="stat-lbl">Lact. Média (dias)</div></td>
                                <td><div class="stat-num">{{ $total_nascidos_vivos ?? 0 }}</div><div class="stat-lbl">Nascidos Vivos</div></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="stats-grid" cellspacing="4">
                        <tbody>
                            <tr>
                                <td><div class="stat-num">{{ $total_nascidos_totais ?? 0 }}</div><div class="stat-lbl">Total Nascidos</div></td>
                                <td><div class="stat-num">{{ $total_desmamados ?? 0 }}</div><div class="stat-lbl">Desmamados</div></td>
                                <td><div class="stat-num">{{ $total_mortalidade ?? 0 }}</div><div class="stat-lbl">Mortalidade</div></td>
                                <td>
                                    @php
                                        $sobrevivencia = '-';
                                        $nv = (int) ($total_nascidos_vivos ?? 0);
                                        $de = (int) ($total_desmamados ?? 0);
                                        if ($nv > 0) {
                                            $sobrevivencia = number_format(($de / $nv) * 100, 1, ',', '.') . '%';
                                        }
                                    @endphp
                                    <div class="stat-num {{ ($sobrevivencia === '-') ? 'dash' : '' }}">{{ $sobrevivencia }}</div><div class="stat-lbl">Sobrevivência</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== HISTÓRICO DE PARTOS ========== -->
        <div class="section">
            <div class="box">
                <div class="section-title">
                    <table cellspacing="0" cellpadding="0"><tr>
                        <td class="bar"><div></div></td>
                        <td class="text">HISTÓRICO DE PARTOS</td>
                    </tr></table>
                </div>
                @if(isset($ciclos) && count($ciclos) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Parto</th>
                                <th>Cobertura</th>
                                <th>Desmame</th>
                                <th>Gest.</th>
                                <th>Lact.</th>
                                <th>Total</th>
                                <th>Vivos</th>
                                <th>Desm.</th>
                                <th>Mort.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ciclos as $c)
                            <tr>
                                <td class="col-label">{{ $c['data_parto'] ?? '-' }}</td>
                                <td class="{{ empty($c['data_cobertura']) ? 'na' : '' }}">{{ $c['data_cobertura'] ?? '-' }}</td>
                                <td class="{{ empty($c['data_desmame']) ? 'na' : '' }}">{{ $c['data_desmame'] ?? '-' }}</td>
                                <td class="{{ empty($c['dias_gestacao']) ? 'na' : '' }}">{{ $c['dias_gestacao'] ?? '-' }}</td>
                                <td class="{{ empty($c['dias_lactacao']) ? 'na' : '' }}">{{ $c['dias_lactacao'] ?? '-' }}</td>
                                <td class="col-label" style="text-align:center;">{{ $c['nascidos_totais'] ?? '-' }}</td>
                                <td>{{ $c['nascidos_vivos'] ?? '-' }}</td>
                                <td>{{ $c['desmamados'] ?? '-' }}</td>
                                <td>{{ $c['mortalidade'] ?? 0 }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">
                        Nenhum histórico de partos registrado para esta fêmea.
                    </div>
                @endif
            </div>
        </div>

        <!-- ========== RODAPÉ ========== -->
        <div class="doc-footer">
            <table>
                <tr>
                    <td>© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                    <td class="right">Página 1 / 1</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
