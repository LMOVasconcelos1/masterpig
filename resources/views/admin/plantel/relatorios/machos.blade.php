<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Machos - Sui Control</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 22px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .header h1 { color: #1e40af; margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 14px 0 18px; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; }
        .actions-left { font-size: 11px; color: #6b7280; }
        .actions-right { display: flex; gap: 10px; }
        .btn { display: inline-block; padding: 8px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; border: 1px solid #e5e7eb; color: #111827; background: #fff; }
        .btn-primary { background: #1e40af; border-color: #1e40af; color: #fff; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f3f4f6; color: #374151; font-weight: bold; text-align: left; padding: 10px; border-bottom: 1px solid #d1d5db; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sui Control</h1>
        <p>Relatório de Plantel - Machos</p>
        <p>Emitido em: {{ $data_emissao }}</p>
    </div>

    @if (empty($isPdf))
    <div class="actions">
        <div class="actions-left">Escolha o formato para exportação.</div>
        <div class="actions-right">
            <a class="btn btn-primary" href="{{ route('admin.relatorios.plantel.machos', ['format' => 'pdf'], false) }}">Gerar PDF</a>
            <a class="btn" href="{{ route('admin.relatorios.plantel.machos', ['format' => 'csv'], false) }}">Baixar CSV</a>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">ID primária</th>
                <th style="width: 14%;">ID secundária</th>
                <th>Raça</th>
                <th style="width: 18%;">Localização</th>
                <th style="width: 10%;">Baia</th>
                <th style="width: 12%;">Data compra</th>
                <th style="width: 18%;">Última operação</th>
                <th style="width: 14%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
            <tr>
                <td>{{ $row['id_primaria'] }}</td>
                <td>{{ $row['id_secundaria'] ?? '-' }}</td>
                <td>{{ $row['raca'] ?? '-' }}</td>
                <td>{{ $row['localizacao'] ?? '-' }}</td>
                <td>{{ $row['baia'] ?? '-' }}</td>
                <td>{{ $row['data_compra'] ?? '-' }}</td>
                <td>{{ $row['ultima_operacao'] ?? '-' }}</td>
                <td>{{ $row['status'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8">Nenhum registro encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Sui Control - Sistema de Gestão de Suinocultura
    </div>
</body>
</html>
