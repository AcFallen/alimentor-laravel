<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requerimiento Semanal</title>
    <style>
        @page {
            margin: 20mm 25mm;
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
            padding: 4px 3px;
            border: 1px solid #3a63a8;
        }

        table.data-table td {
            padding: 3px 4px;
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

        .category-header td {
            font-weight: bold;
            font-style: italic;
            background-color: #D6E4F0 !important;
            font-size: 9px;
            padding: 3px 6px;
        }

        .subtotal-row td {
            font-weight: bold;
            background-color: #E2EFDA !important;
            font-size: 9px;
            padding: 4px 4px;
        }

        .grand-total td {
            font-weight: bold;
            background-color: #1F4E79 !important;
            color: #fff;
            font-size: 10px;
            padding: 5px 4px;
            border: 1px solid #1a3d5c;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>ALIMENTOR - SISTEMA DE PLANIFICACI&Oacute;N DE DIETAS</h1>
        <div class="subtitle">Comprometidos con tu bienestar</div>
        <div class="report-title">INFORME SEMANAL DE REQUERIMIENTO DE ALIMENTOS</div>
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

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">N&deg;</th>
                <th style="width: 22%;">Alimento</th>
                <th style="width: 8%;">Costo Ud</th>
                <th style="width: 8%;">Unidad</th>
                @foreach ($dates as $date)
                    <th>{{ $date['label'] }}</th>
                @endforeach
                <th style="width: 8%;">Cant. Total</th>
                <th style="width: 10%;">Total Ud (Aprox)</th>
                <th style="width: 8%;">Costo Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalColspan = 4 + count($dates) + 2; @endphp

            @foreach ($categories as $category)
                {{-- Category Header --}}
                <tr class="category-header">
                    <td colspan="{{ 4 + count($dates) + 3 }}">{{ $category['name'] }}</td>
                </tr>

                {{-- Food Rows --}}
                @foreach ($category['rows'] as $row)
                    <tr>
                        <td class="center">{{ $row['index'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ $row['unitCost'] }}</td>
                        <td class="center">{{ $row['unitName'] }}</td>
                        @foreach ($row['dayValues'] as $val)
                            <td class="num">{{ $val }}</td>
                        @endforeach
                        <td class="num">{{ $row['totalKg'] }}</td>
                        <td class="num">{{ $row['totalUnits'] }}</td>
                        <td class="num">{{ $row['totalCost'] }}</td>
                    </tr>
                @endforeach

                {{-- Category Subtotal --}}
                <tr class="subtotal-row">
                    <td colspan="{{ $totalColspan }}" style="text-align: right;">Subtotal {{ $category['name'] }}:</td>
                    <td class="num">{{ number_format($category['total'], 2) }}</td>
                </tr>
            @endforeach

            {{-- Grand Total --}}
            <tr class="grand-total">
                <td colspan="{{ $totalColspan }}" style="text-align: right;">COSTO TOTAL PLANIFICACI&Oacute;N</td>
                <td class="num">{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
