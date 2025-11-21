@extends('layouts.app')

@section('content')
<h2 class="text-xl font-bold mb-4">Dashboard - Backend Metrics (Simplified UI)</h2>

<div class="space-y-4">

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Totais</h3>
        <p>Total de Pedidos: {{ $total_orders }}</p>
        <p>Receita Total: R$ {{ number_format($total_revenue,2,',','.') }}</p>
        <p>Receita Líquida: R$ {{ number_format($net_revenue,2,',','.') }}</p>
        <p>Reembolsos: R$ {{ number_format($total_refunds,2,',','.') }}</p>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Operacional</h3>
        <p>Pedidos Entregues: {{ $delivered_count }} ({{ $delivered_rate }}%)</p>
        <p>Clientes Únicos: {{ $unique_customers }}</p>
        <p>Média de Pedidos por Cliente: {{ $average_orders_per_customer }}</p>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Produto Mais Vendido</h3>
        @if($top_product)
            <p>{{ $top_product['name'] }} — {{ $top_product['qty'] }} unidades — R$ {{ number_format($top_product['revenue'],2,',','.') }}</p>
        @else
            <p>Nenhum produto encontrado.</p>
        @endif
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Top 5 Produtos por Receita</h3>
        <ul>
            @foreach($top5_products as $name=>$p)
                <li>{{ $name }} — R${{ number_format($p['revenue'],2,',','.') }}</li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Top 10 Cidades</h3>
        <ul>
            @foreach($top_cities as $city=>$count)
                <li>{{ $city }} — {{ $count }} pedidos</li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Produtos com mais Reembolsos</h3>
        <ul>
            @foreach($top_refund_products as $name=>$qty)
                <li>{{ $name }} — {{ $qty }} itens reembolsados</li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <h3 class="font-semibold">Vendas no Tempo</h3>
        <ul>
            @foreach($sales_per_day as $day=>$value)
                <li>{{ $day }} — R$ {{ number_format($value,2,',','.') }}</li>
            @endforeach
        </ul>
    </div>

</div>
@endsection
