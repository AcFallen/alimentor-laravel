<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Macronutrientes</title>
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
        .meta-table .label { font-weight: bold; color: #1F4E79; width: 14%; }
        .meta-table .value { width: 36%; }
        .date-header { background-color: #1F4E79; color: #fff; font-weight: bold; font-size: 11px; text-align: center; padding: 5px 8px; margin-top: 8px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.data-table th { background-color: #4472C4; color: #fff; font-weight: bold; font-size: 7px; text-align: center; padding: 3px 2px; border: 1px solid #3a63a8; }
        table.data-table td { padding: 2px 3px; border: 1px solid #BFBFBF; font-size: 8px; }
        table.data-table td.num { text-align: right; }
        .meal-header td { font-weight: bold; background-color: #2E75B6 !important; color: #fff; font-size: 8px; text-align: center; padding: 3px 6px; }
        .recipe-label td { font-style: italic; background-color: #D6E4F0 !important; font-size: 8px; padding: 2px 6px; }
        .subtotal-row td { font-weight: bold; background-color: #D6E4F0 !important; font-size: 8px; padding: 3px 3px; }
        .vc-row td { font-weight: bold; font-size: 8px; padding: 2px 3px; text-align: right; }
        .day-total td { font-weight: bold; background-color: #1F4E79 !important; color: #fff; font-size: 9px; padding: 4px 3px; border: 1px solid #1a3d5c; }
        .vct-summary td { font-size: 8px; padding: 2px 3px; }
        .vct-summary .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">REPORTE DE MACRONUTRIENTES</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Fecha de emisi&oacute;n:</td>
            <td class="value">{{ $emissionDate }}</td>
            <td class="label">Planificaci&oacute;n:</td>
            <td class="value">{{ $mealPlanName }}</td>
        </tr>
        <tr>
            <td class="label">Per&iacute;odo:</td>
            <td class="value">{{ $startDate }} al {{ $endDate }}</td>
            <td class="label">Usuario:</td>
            <td class="value">Sistema Alimentor</td>
        </tr>
    </table>

    @foreach ($days as $day)
        <div class="date-header">{{ $day['date'] }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 24%;">ALIMENTO</th>
                    <th rowspan="2" style="width: 8%;">PESO NETO (g)</th>
                    <th rowspan="2" style="width: 8%;">PESO BRUTO (g)</th>
                    <th colspan="2" style="width: 14%;">PROTE&Iacute;NAS</th>
                    <th colspan="2" style="width: 14%;">GRASAS</th>
                    <th colspan="2" style="width: 14%;">CARBOHIDRATOS</th>
                    <th rowspan="2" style="width: 8%;">TOTAL (kcal)</th>
                </tr>
                <tr>
                    <th>g</th><th>kcal</th>
                    <th>g</th><th>kcal</th>
                    <th>g</th><th>kcal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($day['meals'] as $meal)
                    <tr class="meal-header"><td colspan="10">{{ $meal['label'] }}</td></tr>

                    @foreach ($meal['sections'] as $section)
                        @if ($section['type'] === 'recipe')
                            <tr class="recipe-label"><td colspan="10">Recetas: {{ $section['name'] }}</td></tr>
                        @endif

                        @foreach ($section['rows'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="num">{{ $row['net'] }}</td>
                                <td class="num">{{ $row['gross'] }}</td>
                                <td class="num">{{ $row['prot_g'] }}</td>
                                <td class="num">{{ $row['prot_kcal'] }}</td>
                                <td class="num">{{ $row['fat_g'] }}</td>
                                <td class="num">{{ $row['fat_kcal'] }}</td>
                                <td class="num">{{ $row['carb_g'] }}</td>
                                <td class="num">{{ $row['carb_kcal'] }}</td>
                                <td class="num">{{ $row['total_kcal'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach

                    <tr class="subtotal-row">
                        <td>SUBTOTAL {{ $meal['label'] }}</td>
                        <td class="num">{{ $meal['subtotal']['net'] }}</td>
                        <td class="num">{{ $meal['subtotal']['gross'] }}</td>
                        <td class="num">{{ $meal['subtotal']['prot_g'] }}</td>
                        <td class="num">{{ $meal['subtotal']['prot_kcal'] }}</td>
                        <td class="num">{{ $meal['subtotal']['fat_g'] }}</td>
                        <td class="num">{{ $meal['subtotal']['fat_kcal'] }}</td>
                        <td class="num">{{ $meal['subtotal']['carb_g'] }}</td>
                        <td class="num">{{ $meal['subtotal']['carb_kcal'] }}</td>
                        <td class="num">{{ $meal['subtotal']['total_kcal'] }}</td>
                    </tr>
                    <tr class="vc-row">
                        <td colspan="9" style="text-align: right;">V.C. {{ $meal['label'] }}</td>
                        <td class="num">{{ $meal['vcKcal'] }}</td>
                    </tr>
                    <tr class="vc-row">
                        <td colspan="9" style="text-align: right;">Dist. {{ $meal['label'] }}</td>
                        <td class="num">{{ $day['mealDists'][$meal['label']] ?? '0.0%' }}</td>
                    </tr>
                @endforeach

                <tr class="day-total">
                    <td>TOTAL GENERAL</td>
                    <td class="num">{{ $day['total']['net'] }}</td>
                    <td class="num">{{ $day['total']['gross'] }}</td>
                    <td class="num">{{ $day['total']['prot_g'] }}</td>
                    <td class="num">{{ $day['total']['prot_kcal'] }}</td>
                    <td class="num">{{ $day['total']['fat_g'] }}</td>
                    <td class="num">{{ $day['total']['fat_kcal'] }}</td>
                    <td class="num">{{ $day['total']['carb_g'] }}</td>
                    <td class="num">{{ $day['total']['carb_kcal'] }}</td>
                    <td class="num">{{ $day['total']['total_kcal'] }}</td>
                </tr>
            </tbody>
        </table>

        {{-- VCT Summary --}}
        <table class="data-table" style="width: 40%; margin-left: auto; margin-bottom: 10px;">
            <thead>
                <tr><th></th><th>PROTE&Iacute;NAS</th><th>GRASAS</th><th>CARBOHIDRATOS</th></tr>
            </thead>
            <tbody class="vct-summary">
                <tr><td class="bold">VCT (g)</td><td class="num">{{ $day['vct']['g']['prot'] }}</td><td class="num">{{ $day['vct']['g']['fat'] }}</td><td class="num">{{ $day['vct']['g']['carb'] }}</td></tr>
                <tr><td class="bold">VCT (kcal)</td><td class="num">{{ $day['vct']['kcal']['prot'] }}</td><td class="num">{{ $day['vct']['kcal']['fat'] }}</td><td class="num">{{ $day['vct']['kcal']['carb'] }}</td></tr>
                <tr><td class="bold">VCT (%)</td><td class="num" style="font-weight: bold;">{{ $day['vct']['percent']['prot'] }}</td><td class="num" style="font-weight: bold;">{{ $day['vct']['percent']['fat'] }}</td><td class="num" style="font-weight: bold;">{{ $day['vct']['percent']['carb'] }}</td></tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>
