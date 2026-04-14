<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>

<h2>Commandes du jour : ({{ $date }})</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        @foreach($orders as $order)
        <tr>
            <td>#{{ $order->id }}</td>
            <td>{{ $order->user->name ?? '' }}</td>
            <td>{{ $order->total_price }} DH</td>
            <td>{{ $order->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>