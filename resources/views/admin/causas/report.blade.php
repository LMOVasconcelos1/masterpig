<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Causas - Sui Control</title>
    <style>
        * { font-family: Helvetica, Arial, sans-serif !important; }
        html, body { font-family: Helvetica, Arial, sans-serif !important; }
        @page { size: A4 landscape; margin: 10mm 8mm 12mm 8mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            font-size: 11px;
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
        }
        .doc-header .doc-meta strong {
            color: #0a0a0a;
            font-weight: 700;
        }

        .doc-title-wrap {
            width: 100%;
            margin-bottom: 14px;
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
            font-size: 10px;
            color: #2a2a2a;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .summary { width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 12px; }
        .summary td { border: 1px solid #0a0a0a; background: #fff; text-align: center; padding: 10px 6px 8px 6px; vertical-align: top; width: 25%; }
        .summary .num { font-size: 22px; font-weight: 800; color: #0a0a0a; line-height: 1; min-height: 26px; }
        .summary .lbl { font-size: 8.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #2a2a2a; margin-top: 5px; border-top: 1px solid #0a0a0a; padding-top: 5px; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #0a0a0a;
            font-size: 9.5px;
        }
        table.data-table thead th {
            background: #0a0a0a;
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 6px 6px;
            text-align: left;
            border-right: 1px solid #2a2a2a;
            white-space: nowrap;
            font-size: 8.5px;
        }
        table.data-table thead th:last-child { border-right: none; }
        table.data-table tbody td {
            padding: 5px 6px;
            border-right: 1px solid #0a0a0a;
            border-bottom: 1px solid #0a0a0a;
            vertical-align: middle;
            color: #0a0a0a;
        }
        table.data-table tbody td:last-child { border-right: none; }
        table.data-table tbody tr:last-child td { border-bottom: none; }
        table.data-table tbody tr:nth-child(even) td { background: #fafafa; }
        table.data-table .na { color: #4b5563; font-weight: 500; }

        .status-box { display:inline-block; padding:2px 8px; font-weight:700; font-size:8.5px; letter-spacing:0.06em; text-transform:uppercase; border:1px solid #0a0a0a; }
        .status-on { background:#ffffff; color:#0a0a0a; }
        .status-off { background:#fafafa; color:#2a2a2a; text-decoration: line-through; }

        .no-data {
            border:1px solid #0a0a0a; padding:16px 12px; text-align:center; font-style:italic;
            color:#2a2a2a; background:#fafafa; font-size:11px;
        }

        table.doc-footer {
            width: 100%;
            border-collapse: collapse;
            position: absolute;
            bottom: 10mm;
            left: 8mm;
            right: 8mm;
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
                    <div style="margin-top:3px;"><strong>Documento:</strong> Relatório de Causas</div>
                    <div style="margin-top:3px;"><strong>Total:</strong> {{ $causas->count() }} registros</div>
                </td>
            </tr>
        </table>

        <table class="doc-title-wrap" width="100%" align="center" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <h1>Relatório de Cadastro de Causas</h1>
                    <div class="doc-subtitle">Mortalidade · Descarte · Perdas Reprodutivas</div>
                </td>
            </tr>
        </table>

        @php
            $total = $causas->count();
            $ativos = $causas->where('situacao', true)->count();
            $inativos = $total - $ativos;
            $grupos = $causas->pluck('grupoCausa')->filter()->unique('id')->count();
        @endphp
        <table class="summary" cellspacing="8"><tbody><tr>
            <td><div class="num">{{ $total }}</div><div class="lbl">Total Causas</div></td>
            <td><div class="num">{{ $ativos }}</div><div class="lbl">Ativas</div></td>
            <td><div class="num">{{ $inativos }}</div><div class="lbl">Inativas</div></td>
            <td><div class="num">{{ $grupos }}</div><div class="lbl">Grupos</div></td>
        </tr></tbody></table>

        @if($total > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:14%;">Código</th>
                    <th style="width:38%;">Causa</th>
                    <th style="width:30%;">Grupo</th>
                    <th style="width:18%;">Situação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($causas as $c)
                <tr>
                    <td><strong>{{ $c->codigo }}</strong></td>
                    <td>{{ $c->nome }}</td>
                    <td class="{{ empty($c->grupoCausa->nome) ? 'na' : '' }}">{{ optional($c->grupoCausa)->nome ?? '-' }}</td>
                    <td>
                        @if($c->situacao)
                            <span class="status-box status-on">● Ativo</span>
                        @else
                            <span class="status-box status-off">● Inativo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div class="no-data">Nenhuma causa cadastrada no sistema.</div>
        @endif

        <table class="doc-footer" cellspacing="0" cellpadding="0">
            <tr>
                <td class="left">© {{ date('Y') }} <strong>Sui Control</strong> — MasterPig · Gestão Suinocultura</td>
                <td class="right">Página 1 / 1</td>
            </tr>
        </table>
    </div>
</body>
</html>
