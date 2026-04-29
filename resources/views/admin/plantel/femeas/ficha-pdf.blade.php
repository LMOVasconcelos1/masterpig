<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ficha da Fêmea - {{ $femea->id_primaria }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        
        .page {
            width: 190mm;
            margin: 0 auto;
            padding: 8mm;
            min-height: 297mm;
            position: relative;
            box-sizing: border-box;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 9px;
            font-style: italic;
        }
        
        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            padding: 4px 8px;
            background: #f5f5f5;
            border: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .info-value {
            color: #000;
            text-align: right;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .stat-box {
            text-align: center;
            padding: 8px 4px;
            border: 2px solid #000;
            background: #f9f9f9;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            display: block;
        }
        
        .stat-label {
            font-size: 8px;
            color: #333;
            text-transform: uppercase;
            margin-top: 2px;
            font-weight: bold;
        }
        
        .table-container {
            margin-bottom: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            font-size: 8px;
            table-layout: fixed;
        }
        
        th {
            background: #000;
            color: #fff;
            font-weight: bold;
            padding: 4px 2px;
            text-align: center;
            font-size: 7px;
            text-transform: uppercase;
            border: 1px solid #000;
            white-space: nowrap;
            overflow: hidden;
        }
        
        td {
            padding: 3px 2px;
            text-align: center;
            border: 1px solid #000;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            font-size: 8px;
        }
        
        td:first-child {
            text-align: left;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        
        .footer {
            position: absolute;
            bottom: 8mm;
            left: 8mm;
            right: 8mm;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        
        .status-active {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-inactive {
            font-weight: bold;
            text-transform: uppercase;
            font-style: italic;
        }
        
        .highlight-box {
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        
        .highlight-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 8px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                size: A4;
            }
            
            .page {
                margin: 0;
                padding: 8mm;
                width: 190mm;
                height: 281mm;
                overflow: hidden;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>Ficha da Fêmea</h1>
            <p>Sistema Masterpig - Controle Reprodutivo Suíno</p>
        </div>

        <!-- Informações Principais -->
        <div class="highlight-box">
            <div class="highlight-title">Identificação</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">ID Primária:</span>
                    <span class="info-value">{{ $femea->id_primaria }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Secundária:</span>
                    <span class="info-value">{{ $femea->id_secundaria ?: '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo:</span>
                    <span class="info-value">{{ ucfirst($femea->tipo_compra) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Raça:</span>
                    <span class="info-value">{{ $femea->raca_nome ?: '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value {{ str_contains($status, 'Ativa') ? 'status-active' : 'status-inactive' }}">
                        {{ $status }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fornecedor:</span>
                    <span class="info-value">{{ $femea->fornecedor_nome ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Dados Cronológicos -->
        <div class="section">
            <div class="section-title">Dados Cronológicos</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Nascimento:</span>
                    <span class="info-value">{{ $femea->data_nascimento ? \Carbon\Carbon::parse($femea->data_nascimento)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Compra:</span>
                    <span class="info-value">{{ $femea->data_compra ? \Carbon\Carbon::parse($femea->data_compra)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Localização:</span>
                    <span class="info-value">{{ $femea->localizacao ?: '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Baia:</span>
                    <span class="info-value">{{ $femea->baia ?: '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Dados de Peso -->
        <div class="section">
            <div class="section-title">Controle de Peso</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Peso Compra:</span>
                    <span class="info-value">{{ $femea->peso_compra ? number_format($femea->peso_compra, 1) . ' kg' : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Peso Atual:</span>
                    <span class="info-value">{{ $femea->peso_atual ? number_format($femea->peso_atual, 1) . ' kg' : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Estatísticas Reprodutivas -->
        <div class="section">
            <div class="section-title">Estatísticas Reprodutivas</div>
            <div class="stats-container">
                <div class="stat-box">
                    <span class="stat-value">{{ $total_ciclos }}</span>
                    <span class="stat-label">Partos</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $media_dias_gestacao ?: '-' }}</span>
                    <span class="stat-label">Gestação (dias)</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $media_dias_lactacao ?: '-' }}</span>
                    <span class="stat-label">Lactação (dias)</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $total_nascidos_vivos }}</span>
                    <span class="stat-label">Nascidos Vivos</span>
                </div>
            </div>
            
            <div class="stats-container">
                <div class="stat-box">
                    <span class="stat-value">{{ $total_nascidos_totais }}</span>
                    <span class="stat-label">Total Nascidos</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $total_desmamados }}</span>
                    <span class="stat-label">Desmamados</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $total_mortalidade }}</span>
                    <span class="stat-label">Mortalidade</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value">{{ $total_nascidos_vivos > 0 ? number_format(($total_desmamados / $total_nascidos_vivos) * 100, 1) . '%' : '-' }}</span>
                    <span class="stat-label">Sobrevivência</span>
                </div>
            </div>
        </div>

        <!-- Histórico de Partos -->
        <div class="section">
            <div class="section-title">Histórico de Partos</div>
            @if(count($ciclos) > 0)
            <div class="table-container">
                <table>
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
                        @foreach($ciclos as $ciclo)
                        <tr>
                            <td>{{ $ciclo['data_parto'] }}</td>
                            <td>{{ $ciclo['data_cobertura'] }}</td>
                            <td>{{ $ciclo['data_desmame'] }}</td>
                            <td>{{ $ciclo['dias_gestacao'] ?: '-' }}</td>
                            <td>{{ $ciclo['dias_lactacao'] ?: '-' }}</td>
                            <td>{{ $ciclo['nascidos_totais'] }}</td>
                            <td>{{ $ciclo['nascidos_vivos'] }}</td>
                            <td>{{ $ciclo['desmamados'] }}</td>
                            <td>{{ $ciclo['mortalidade'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="no-data">
                Nenhum histórico de partos registrado para esta fêmea.
            </div>
            @endif
        </div>

        <!-- Rodapé -->
        <div class="footer">
            Ficha gerada em: {{ $data_geracao }} | Sistema Masterpig v1.0 | Página 1/1
        </div>
    </div>
</body>
</html>
