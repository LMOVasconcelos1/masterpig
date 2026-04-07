<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Cobertura</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            color: #666;
        }
        
        .form-info {
            margin-bottom: 15px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .form-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 11px;
        }
        
        .form-table td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
        }
        
        .form-table .number-col {
            width: 50px;
            background: #f9f9f9;
        }
        
        .form-table .matriz-col {
            width: 80px;
        }
        
        .form-table .data-col {
            width: 120px;
        }
        
        .form-table .macho-col {
            width: 100px;
        }
        
        .form-table .obs-col {
            width: 150px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 30px;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                margin: 10px;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FORMULÁRIO DE COBERTURA</h1>
        <p>{{ \App\Models\Configuracao::getGranjaAtual() }} - Sui Control</p>
    </div>
    
    <div class="form-info">
        <strong>Data:</strong> {{ date('d/m/Y') }} | 
        <strong>Tipo:</strong> {{ $tipo ?? 'Em branco' }} | 
        <strong>Ordenar por:</strong> {{ ucfirst($ordenar ?? 'Matriz') }} | 
        <strong>Quantidade:</strong> {{ $quantidade ?? 10 }} linhas
    </div>
    
    <table class="form-table">
        <thead>
            <tr>
                <th class="number-col">Nº</th>
                <th class="matriz-col">Matriz</th>
                <th class="data-col">Data</th>
                <th class="macho-col">Macho</th>
                <th class="obs-col">Observações</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= ($quantidade ?? 10); $i++)
            <tr>
                <td class="number-col">{{ $i }}</td>
                <td class="matriz-col"></td>
                <td class="data-col"></td>
                <td class="macho-col"></td>
                <td class="obs-col"></td>
            </tr>
            @endfor
        </tbody>
    </table>
    
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Responsável Técnico</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Data: ____/____/______</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Assinatura</div>
        </div>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} {{ \App\Models\Configuracao::getGranjaAtual() }} - Sistema de Gestão de Suinocultura</p>
        <p>Formulário gerado em {{ date('d/m/Y H:i') }}</p>
    </div>
    
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center space-x-2">
            <i class="fa-solid fa-print"></i>
            <span>Imprimir</span>
        </button>
        <button onclick="window.close()" class="ml-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors duration-200 flex items-center space-x-2">
            <i class="fa-solid fa-times"></i>
            <span>Fechar</span>
        </button>
    </div>
    
    <script>
        // Auto-imprimir quando carregar
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Fechar janela após impressão
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>
