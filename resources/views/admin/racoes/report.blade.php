<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Rações - MasterPig</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 22px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .header h1 { color: #1e40af; margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f3f4f6; color: #374151; font-weight: bold; text-align: left; padding: 10px; border-bottom: 1px solid #d1d5db; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MasterPig</h1>
        <p>Relatório de Cadastro de Rações</p>
        <p>Emitido em: {{ $data_emissao }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Código</th>
                <th>Nome</th>
            </tr>
        </thead>
        <tbody>
            @foreach($racoes as $racao)
            <tr>
                <td>{{ $racao->codigo }}</td>
                <td>{{ $racao->nome }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} MasterPig - Sistema de Gestão de Suinocultura
    </div>
</body>
</html>

