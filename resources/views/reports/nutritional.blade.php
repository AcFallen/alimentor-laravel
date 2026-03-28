<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Nutricional</title>
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
            color: #1F4E79;
            width: 14%;
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

        .meal-label {
            background-color: #2E75B6;
            color: #fff;
            font-weight: bold;
            font-size: 9px;
            padding: 3px 6px;
        }

        .meal-total td {
            font-weight: bold;
            background-color: #D6E4F0 !important;
            font-size: 9px;
            padding: 3px 5px;
        }

        .day-total td {
            font-weight: bold;
            background-color: #B4C6E7 !important;
            font-size: 9px;
            padding: 4px 5px;
        }

        .grand-total td {
            font-weight: bold;
            background-color: #1F4E79 !important;
            color: #fff;
            font-size: 10px;
            padding: 5px 5px;
            border: 1px solid #1a3d5c;
        }

        table.data-table tbody tr:nth-child(even) td {
            background-color: #F8F9FA;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">REPORTE NUTRICIONAL</div>
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
    @foreach ($days as $day)
        <div class="date-header">{{ $day['date'] }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Tiempo de Comida</th>
                    <th style="width: 37%;">Alimento</th>
                    <th style="width: 12%;">Energ&iacute;a (kcal)</th>
                    <th style="width: 12%;">Prote&iacute;nas (g)</th>
                    <th style="width: 12%;">Carbohidratos (g)</th>
                    <th style="width: 12%;">Grasa (g)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($day['meals'] as $meal)
                    @if (count($meal['items']) > 0)
                        @foreach ($meal['items'] as $itemIndex => $item)
                            <tr>
                                @if ($itemIndex === 0)
                                    <td class="meal-label" rowspan="{{ count($meal['items']) }}">{{ $meal['label'] }}</td>
                                @endif
                                <td>{{ $item['name'] }}</td>
                                <td class="num">{{ $item['energy'] }}</td>
                                <td class="num">{{ $item['protein'] }}</td>
                                <td class="num">{{ $item['carbs'] }}</td>
                                <td class="num">{{ $item['fat'] }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="meal-label">{{ $meal['label'] }}</td>
                            <td colspan="5" style="text-align: center; color: #999;">Sin alimentos</td>
                        </tr>
                    @endif

                    {{-- Meal Total --}}
                    <tr class="meal-total">
                        <td colspan="2" style="text-align: right;">Total {{ $meal['label'] }}</td>
                        <td class="num">{{ $meal['totals']['energy'] }}</td>
                        <td class="num">{{ $meal['totals']['protein'] }}</td>
                        <td class="num">{{ $meal['totals']['carbs'] }}</td>
                        <td class="num">{{ $meal['totals']['fat'] }}</td>
                    </tr>
                @endforeach

                {{-- Day Total --}}
                <tr class="day-total">
                    <td colspan="2" style="text-align: right;">Total D&iacute;a</td>
                    <td class="num">{{ $day['totals']['energy'] }}</td>
                    <td class="num">{{ $day['totals']['protein'] }}</td>
                    <td class="num">{{ $day['totals']['carbs'] }}</td>
                    <td class="num">{{ $day['totals']['fat'] }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    {{-- Grand Total --}}
    <table class="data-table" style="margin-top: 6px;">
        <tr class="grand-total">
            <td colspan="2" style="text-align: right; width: 52%;">TOTAL GENERAL</td>
            <td class="num" style="width: 12%;">{{ $grandTotals['energy'] }}</td>
            <td class="num" style="width: 12%;">{{ $grandTotals['protein'] }}</td>
            <td class="num" style="width: 12%;">{{ $grandTotals['carbs'] }}</td>
            <td class="num" style="width: 12%;">{{ $grandTotals['fat'] }}</td>
        </tr>
    </table>
</body>
</html>
