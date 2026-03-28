<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Micronutrientes</title>
    <style>
        @page { margin: 20mm 30mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1F4E79; padding-bottom: 8px; }
        .header h1 { font-size: 18px; color: #1F4E79; margin-bottom: 2px; }
        .header .subtitle { font-size: 11px; color: #5B9BD5; font-style: italic; margin-bottom: 4px; }
        .header .report-title { font-size: 14px; color: #2E75B6; font-weight: bold; }
        .meta-table { width: 100%; margin-bottom: 12px; font-size: 10px; }
        .meta-table td { padding: 2px 6px; }
        .meta-table .label { font-weight: bold; color: #1F4E79; }
        .date-header { background-color: #1F4E79; color: #fff; font-weight: bold; font-size: 11px; text-align: center; padding: 5px 8px; margin-top: 8px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.data-table th { background-color: #4472C4; color: #fff; font-weight: bold; font-size: 7px; text-align: center; padding: 3px 2px; border: 1px solid #3a63a8; }
        table.data-table td { padding: 2px 3px; border: 1px solid #BFBFBF; font-size: 8px; }
        table.data-table td.num { text-align: right; }
        .meal-header td { font-weight: bold; background-color: #2E75B6 !important; color: #fff; font-size: 8px; text-align: center; padding: 3px 6px; }
        .recipe-label td { font-style: italic; background-color: #D6E4F0 !important; font-size: 8px; padding: 2px 6px; }
        .subtotal-row td { font-weight: bold; background-color: #D6E4F0 !important; font-size: 8px; padding: 3px 3px; }
        .day-total td { font-weight: bold; background-color: #1F4E79 !important; color: #fff; font-size: 9px; padding: 4px 3px; border: 1px solid #1a3d5c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">REPORTE GENERAL DE MICRONUTRIENTES</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label" style="width: 14%;">Fecha de emisi&oacute;n:</td>
            <td style="width: 20%;">{{ $emissionDate }}</td>
            <td class="label" style="width: 14%;">Planificaci&oacute;n:</td>
            <td>{{ $mealPlanName }}</td>
        </tr>
        <tr>
            <td class="label">Per&iacute;odo:</td>
            <td>{{ $startDate }} - {{ $endDate }}</td>
            <td class="label">Usuario:</td>
            <td>Sistema Alimentor</td>
        </tr>
    </table>

    @php $colCount = 3 + count($nutrientHeaders); @endphp

    @foreach ($days as $day)
        <div class="date-header">FECHA: {{ $day['date'] }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 24%;">ALIMENTO</th>
                    <th style="width: 9%;">PESO NETO (g)</th>
                    <th style="width: 9%;">PESO BRUTO (g)</th>
                    @foreach ($nutrientHeaders as $nh)
                        <th>{{ $nh['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($day['meals'] as $meal)
                    <tr class="meal-header"><td colspan="{{ $colCount }}">{{ $meal['label'] }}</td></tr>

                    @foreach ($meal['sections'] as $section)
                        @if ($section['type'] === 'recipe')
                            <tr class="recipe-label"><td colspan="{{ $colCount }}">Recetas: {{ $section['name'] }}</td></tr>
                        @endif

                        @foreach ($section['rows'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="num">{{ $row['net'] }}</td>
                                <td class="num">{{ $row['gross'] }}</td>
                                @foreach ($nutrientHeaders as $nh)
                                    <td class="num">{{ $row[$nh['key']] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="subtotal-row">
                        <td>SUBTOTAL {{ $meal['label'] }}</td>
                        <td class="num">{{ $meal['subtotal']['net'] }}</td>
                        <td class="num">{{ $meal['subtotal']['gross'] }}</td>
                        @foreach ($nutrientHeaders as $nh)
                            <td class="num">{{ $meal['subtotal'][$nh['key']] }}</td>
                        @endforeach
                    </tr>
                @endforeach

                <tr class="day-total">
                    <td>TOTAL DEL D&Iacute;A - {{ $day['date'] }}</td>
                    <td class="num">{{ $day['total']['net'] }}</td>
                    <td class="num">{{ $day['total']['gross'] }}</td>
                    @foreach ($nutrientHeaders as $nh)
                        <td class="num">{{ $day['total'][$nh['key']] }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
