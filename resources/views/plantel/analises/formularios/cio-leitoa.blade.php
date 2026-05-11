<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cio de leitoa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 10mm 12mm 12mm 12mm; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.25; color: #111; background: #fff; }
        .page { width: 95%; margin: 0 auto; padding: 0; }

        .header { margin-bottom: 5mm; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: bottom; }
        .header-left { text-align: left; font-size: 13px; font-weight: 800; }
        .header-right { text-align: right; font-size: 10px; font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; }
        .rule { border-top: 1px solid #555; margin-top: 6px; }

        .table-wrap { width: 100%; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #555; }

        thead th {
            background: #d9d9d9;
            color: #111;
            font-weight: 800;
            text-align: center;
            padding: 6px 4px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            border: 1px solid #555;
            white-space: normal;
            word-break: break-word;
            line-height: 1.15;
        }

        tbody td {
            border: 1px solid #555;
            padding: 0 3px;
            font-size: 9px;
            height: {{ $rowHeightMm ?? 7.0 }}mm;
            vertical-align: middle;
        }

        .col-leitoa { width: 18%; }
        .col-data { width: 13%; }
        .col-vacina { width: 15%; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 0 12mm 2mm; text-align: right; font-size: 10px; color: #111; }
        @media screen {
            body { padding: 16px; background: #f3f4f6; }
            .page { max-width: 980px; margin: 0 auto; background: #fff; padding: 16px; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-left">Cio de leitoa</td>
                    <td class="header-right">SuiControl</td>
                </tr>
            </table>
            <div class="rule"></div>
        </div>

        <div class="table-wrap">
            <table>
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
        </div>
    </div>

    <div class="footer">suicontrol</div>
</body>
</html>
