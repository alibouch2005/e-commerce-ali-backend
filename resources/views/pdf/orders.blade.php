<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            font-size: 12px;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
        }

        .header {
            margin-bottom: 20px;
            text-align: center;
        }

        .date {
            text-align: right;
            margin-bottom: 10px;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #3498db;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 15px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="header">
    <h1>📦 Rapport des commandes</h1>
</div>

<div class="date">
    Date : {{ $date }}
</div>

<table>
    <thead>
        <tr>
            <th># ID</th>
            <th>Client</th>
            <th>Total (DH)</th>
            <th>Statut</th>
        </tr>
    </thead>

    <tbody>
        @foreach($orders as $order)
        <tr>
            <td>#{{ $order->id }}</td>
            <td>{{ $order->user->name ?? 'N/A' }}</td>
            <td>{{ number_format($order->total_price, 2) }} DH</td>
            <td>{{ $order->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@php
    $totalGlobal = $orders->sum('total_price');
@endphp

<div class="total">
    Total des ventes : {{ number_format($totalGlobal, 2) }} DH
</div>

<div class="footer">
    Généré automatiquement par le système E-commerce - {{ now()->format('Y-m-d H:i') }}
</div>

</body>
</html>