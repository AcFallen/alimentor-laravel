<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Estandarizada</title>
    <style>
        @page {
            margin: 20mm 50mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #1F4E79;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 18px;
            color: #1F4E79;
            margin-bottom: 2px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #5B9BD5;
            font-style: italic;
            margin-bottom: 4px;
        }

        .header .report-title {
            font-size: 14px;
            color: #2E75B6;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 10px;
        }

        .meta-table td {
            padding: 2px 6px;
        }

        .meta-table .label {
            font-weight: bold;
            width: 14%;
            color: #1F4E79;
        }

        .meta-table .value {
            width: 36%;
        }

        .date-header {
            background-color: #1F4E79;
            color: #fff;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            padding: 5px 8px;
            margin-top: 8px;
        }

        .meal-header {
            background-color: #2E75B6;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 8px;
        }

        .section-label {
            font-weight: bold;
            font-style: italic;
            font-size: 9px;
            padding: 3px 8px;
            background-color: #F2F2F2;
            border-bottom: 1px solid #D9D9D9;
        }

        .recipe-name {
            font-weight: bold;
            font-size: 9px;
            padding: 3px 8px;
            background-color: #D6E4F0;
            border-bottom: 1px solid #B4C6E7;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        table.data-table th {
            background-color: #4472C4;
            color: #fff;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            padding: 4px 4px;
            border: 1px solid #3a63a8;
        }

        table.data-table td {
            padding: 3px 5px;
            border: 1px solid #BFBFBF;
            font-size: 9px;
        }

        table.data-table td.num {
            text-align: right;
        }

        table.data-table td.center {
            text-align: center;
        }

        table.data-table tbody tr:nth-child(even) td {
            background-color: #F8F9FA;
        }

        .col-num { width: 4%; }
        .col-ingredient { width: 26%; }
        .col-net { width: 10%; }
        .col-unit { width: 10%; }
        .col-perf { width: 10%; }
        .col-gross { width: 10%; }
        .col-ucost { width: 9%; }
        .col-pcost { width: 10%; }
        .col-tcost { width: 11%; }

        .meal-total {
            background-color: #E2EFDA;
        }

        .day-total {
            background-color: #B4C6E7;
        }

        .grand-total {
            background-color: #1F4E79;
            color: #fff;
        }

        .total-row td {
            font-weight: bold;
            font-size: 9px;
            padding: 4px 5px;
            border: 1px solid #999;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">REPORTE DE RECETA ESTANDARIZADA</div>
    </div>

    {{-- Metadata --}}
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

    {{-- Report Body --}}
    @foreach ($days as $dayIndex => $day)
        @if ($dayIndex > 0)
            <div style="margin-top: 6px;"></div>
        @endif

        <div class="date-header">{{ $day['date'] }}</div>

        @foreach ($day['meals'] as $meal)
            <div class="meal-header">{{ $meal['label'] }}</div>

            @if (count($meal['recipes']) > 0)
                <div class="section-label">RECETAS</div>

                @foreach ($meal['recipes'] as $recipe)
                    <div class="recipe-name">{{ $recipe['name'] }} - Porciones: {{ $recipe['diners'] }}</div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="col-num">N&deg;</th>
                                <th class="col-ingredient">Ingrediente</th>
                                <th class="col-net">Cant. Neta (g)</th>
                                <th class="col-unit">Unidad</th>
                                <th class="col-perf">Rend. Est. (%)</th>
                                <th class="col-gross">Cant. Bruta (g)</th>
                                <th class="col-ucost">Costo/Ud</th>
                                <th class="col-pcost">Costo/Porci&oacute;n</th>
                                <th class="col-tcost">Presup. Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recipe['ingredients'] as $ingredient)
                                <tr>
                                    <td class="center">{{ $ingredient['index'] }}</td>
                                    <td>{{ $ingredient['name'] }}</td>
                                    <td class="num">{{ $ingredient['netQty'] }}</td>
                                    <td class="center">{{ $ingredient['unit'] }}</td>
                                    <td class="num">{{ $ingredient['performance'] }}</td>
                                    <td class="num">{{ $ingredient['grossQty'] }}</td>
                                    <td class="num">{{ $ingredient['unitCost'] }}</td>
                                    <td class="num">{{ $ingredient['portionCost'] }}</td>
                                    <td class="num">{{ $ingredient['totalCost'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endif

            @if (count($meal['looseFoods']) > 0)
                <div class="section-label">ALIMENTOS INDIVIDUALES</div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-num">N&deg;</th>
                            <th class="col-ingredient">Ingrediente</th>
                            <th class="col-net">Cant. Neta (g)</th>
                            <th class="col-unit">Unidad</th>
                            <th class="col-perf">Rend. Est. (%)</th>
                            <th class="col-gross">Cant. Bruta (g)</th>
                            <th class="col-ucost">Costo/Ud</th>
                            <th class="col-pcost">Costo/Porci&oacute;n</th>
                            <th class="col-tcost">Presup. Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meal['looseFoods'] as $foodIndex => $food)
                            <tr>
                                <td class="center">{{ $foodIndex + 1 }}</td>
                                <td>{{ $food['name'] }}</td>
                                <td class="num">{{ $food['netQty'] }}</td>
                                <td class="center">{{ $food['unit'] }}</td>
                                <td class="num">{{ $food['performance'] }}</td>
                                <td class="num">{{ $food['grossQty'] }}</td>
                                <td class="num">{{ $food['unitCost'] }}</td>
                                <td class="num">{{ $food['portionCost'] }}</td>
                                <td class="num">{{ $food['totalCost'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Meal Total --}}
            <table class="data-table">
                <tr class="total-row meal-total">
                    <td colspan="6">Total {{ $meal['label'] }}</td>
                    <td class="num" colspan="2">{{ number_format($meal['portionCost'], 2) }}</td>
                    <td class="num">{{ number_format($meal['totalCost'], 2) }}</td>
                </tr>
            </table>
        @endforeach

        {{-- Day Total --}}
        <table class="data-table">
            <tr class="total-row day-total">
                <td colspan="6">Total D&iacute;a {{ $day['dateShort'] }}</td>
                <td class="num" colspan="2">{{ number_format($day['portionCost'], 2) }}</td>
                <td class="num">{{ number_format($day['totalCost'], 2) }}</td>
            </tr>
        </table>
    @endforeach

    {{-- Grand Total --}}
    <table class="data-table" style="margin-top: 6px;">
        <tr class="total-row grand-total">
            <td colspan="6">TOTAL PLANIFICACI&Oacute;N</td>
            <td class="num" colspan="2">{{ number_format($grandPortionCost, 2) }}</td>
            <td class="num">{{ number_format($grandTotalCost, 2) }}</td>
        </tr>
    </table>
</body>
</html>
