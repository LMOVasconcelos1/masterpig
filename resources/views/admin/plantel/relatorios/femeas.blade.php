<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Fêmeas - Sui Control</title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 13px; color: #1f2937; margin: 0; padding: 20px; background-color: #fff; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3b82f6; padding-bottom: 20px; }
        .header h1 { color: #1e3a8a; margin: 0; font-size: 28px; font-weight: 800; letter-spacing: -0.025em; }
        .header p { margin: 8px 0 0; color: #6b7280; font-size: 14px; }
        
        .filter-container { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); }
        .filter-title { font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 16px; text-uppercase: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
        
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 11px; font-weight: 600; color: #4b5563; }
        .form-group input { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; transition: all 0.2s; background: #f9fafb; }
        .form-group input:focus { outline: none; border-color: #3b82f6; ring: 2px; ring-color: #3b82f6; background: #fff; }
        
        .actions-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f3f4f6; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid #e5e7eb; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
        .btn-secondary { background: #fff; color: #374151; }
        .btn-secondary:hover { background: #f9fafb; border-color: #d1d5db; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 30px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-align: left; padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f8fafc; }
        
        .badge { display: inline-flex; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 500; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        
        .footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 11px; color: #9ca3af; padding: 15px 0; background: #fff; opacity: 0.8; }
        @media print { .filter-container, .footer { display: none; } body { padding: 0; } table { border: none; border-radius: 0; } th { background-color: #eee !important; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <h1>Sui Control</h1>
        <p>Relatório de Plantel - Fêmeas</p>
        <p>Emitido em: {{ $data_emissao }}</p>
    </div>

    @if (empty($isPdf))
    <div class="filter-container">
        <div class="filter-title">
            <i class="fa-solid fa-filter text-blue-600"></i>
            FILTRAR PLANTEL
        </div>
        <form method="GET" action="{{ route('admin.relatorios.plantel.femeas') }}">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Peso Mínimo (kg)</label>
                    <input type="number" step="0.01" name="peso_min" value="{{ request('peso_min') }}" placeholder="Ex: 150.00">
                </div>
                <div class="form-group">
                    <label>Peso Máximo (kg)</label>
                    <input type="number" step="0.01" name="peso_max" value="{{ request('peso_max') }}" placeholder="Ex: 300.00">
                </div>
                <div class="form-group">
                    <label>Idade Mínima (dias)</label>
                    <input type="number" name="idade_min" value="{{ request('idade_min') }}" placeholder="Ex: 180">
                </div>
                <div class="form-group">
                    <label>Idade Máxima (dias)</label>
                    <input type="number" name="idade_max" value="{{ request('idade_max') }}" placeholder="Ex: 500">
                </div>
                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-magnifying-glass" style="margin-right: 8px;"></i>
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>

        <div class="actions-bar">
            <div style="font-size: 13px; color: #6b7280; font-weight: 500;">
                <i class="fa-solid fa-file-export" style="margin-right: 6px;"></i>
                Exportar resultados:
            </div>
            <div class="actions-right">
                <a class="btn btn-secondary" href="{{ route('admin.relatorios.plantel.femeas', array_merge(request()->all(), ['format' => 'pdf']), false) }}">
                    <i class="fa-solid fa-file-pdf" style="margin-right: 8px; color: #ef4444;"></i>
                    Documento PDF
                </a>
                <a class="btn btn-secondary" href="{{ route('admin.relatorios.plantel.femeas', array_merge(request()->all(), ['format' => 'csv']), false) }}">
                    <i class="fa-solid fa-file-csv" style="margin-right: 8px; color: #10b981;"></i>
                    Planilha CSV
                </a>
            </div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">ID primária</th>
                <th style="width: 12%;">ID secundária</th>
                <th style="width: 12%;">Tipo</th>
                <th>Raça</th>
                <th style="width: 14%;">Localização</th>
                <th style="width: 8%;">Baia</th>
                <th style="width: 9%;">Peso (kg)</th>
                <th style="width: 9%;">Idade (d)</th>
                <th style="width: 11%;">Data compra</th>
                <th style="width: 15%;">Última operação</th>
                <th style="width: 11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
            <tr>
                <td>{{ $row['id_primaria'] }}</td>
                <td>{{ $row['id_secundaria'] ?? '-' }}</td>
                <td>{{ $row['tipo'] ?? '-' }}</td>
                <td>{{ $row['raca'] ?? '-' }}</td>
                <td>{{ $row['localizacao'] ?? '-' }}</td>
                <td>{{ $row['baia'] ?? '-' }}</td>
                <td style="font-weight: 600;">{{ $row['peso'] }}</td>
                <td style="color: #64748b;">{{ $row['idade'] }}</td>
                <td>{{ $row['data_compra'] ?? '-' }}</td>
                <td style="font-size: 11px;">{{ $row['ultima_operacao'] ?? '-' }}</td>
                <td>
                    <span class="badge {{ str_contains(strtolower($row['status']), 'inativo') ? 'badge-inactive' : 'badge-active' }}">
                        {{ $row['status'] ?? '-' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11">Nenhum registro encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Sui Control - Sistema de Gestão de Suinocultura
    </div>
</body>
</html>
