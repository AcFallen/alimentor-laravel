<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan Semanal Detallado</title>
    <style>
        @page { margin: 20mm 50mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1F4E79; padding-bottom: 8px; }
        .header h1 { font-size: 18px; color: #1F4E79; margin-bottom: 2px; }
        .header .subtitle { font-size: 11px; color: #5B9BD5; font-style: italic; margin-bottom: 4px; }
        .header .report-title { font-size: 14px; color: #2E75B6; font-weight: bold; }
        .meta-table { width: 100%; margin-bottom: 12px; font-size: 10px; }
        .meta-table td { padding: 2px 6px; }
        .meta-table .label { font-weight: bold; color: #1F4E79; width: 14%; }
        .meta-table .value { width: 36%; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { background-color: #2E75B6; color: #fff; font-weight: bold; font-size: 9px; text-align: center; padding: 4px 4px; border: 1px solid #2563a0; }
        table.data-table td { padding: 3px 5px; border: 1px solid #BFBFBF; font-size: 9px; }
        table.data-table td.num { text-align: right; }
        table.data-table td.center { text-align: center; }
        .day-number { font-weight: bold; text-align: center; vertical-align: middle; background-color: #F8F9FA; }
        .day-date { font-weight: bold; text-align: center; vertical-align: middle; font-size: 8px; background-color: #F8F9FA; }
        .meal-label { font-weight: bold; background-color: #D6E4F0; font-size: 8px; }
        .subtotal-row td { font-weight: bold; background-color: #E2EFDA !important; font-size: 9px; padding: 4px 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">INFORME DE PLAN DE ALIMENTACI&Oacute;N SEMANAL DETALLADO</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Fecha de emisi&oacute;n:</td>
            <td class="value">{{ $emissionDate }}</td>
            <td class="label">Objetivo:</td>
            <td class="value">{{ $objective }}</td>
        </tr>
        <tr>
            <td class="label">Usuario:</td>
            <td class="value">{{ $userName }}</td>
            <td class="label">Nutricionista:</td>
            <td class="value">{{ $nutritionist }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">N&deg;</th>
                <th style="width: 10%;">FECHA</th>
                <th style="width: 12%;">COMIDA</th>
                <th style="width: 58%;">DETALLE DEL MEN&Uacute;</th>
                <th style="width: 16%;">Aporte Energ&eacute;tico (Kcal)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($days as $day)
                @foreach ($day['meals'] as $mealIndex => $meal)
                    <tr>
                        @if ($mealIndex === 0)
                            <td class="day-number" rowspan="{{ count($day['meals']) }}">{{ $day['number'] }}</td>
                            <td class="day-date" rowspan="{{ count($day['meals']) }}">{{ $day['date'] }}<br>{{ $day['dateShort'] }}</td>
                        @endif
                        <td class="meal-label">{{ $meal['label'] }}</td>
                        <td>{{ $meal['detail'] }}</td>
                        <td class="num">{{ $meal['kcal'] }}</td>
                    </tr>
                @endforeach

                <tr class="subtotal-row">
                    <td colspan="4" style="text-align: right;">Sub Total</td>
                    <td class="num">{{ $day['totalKcal'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
