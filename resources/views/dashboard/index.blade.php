@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Dashboard de Pedidos</h1>

    {{-- Métricas básicas --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total de Pedidos</h6>
                    <p class="display-6 mb-0">{{ $total_orders }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h6 class="card-title text-muted">Receita Total (Local)</h6>
                    <p class="h3 text-success mb-0">R$ {{ number_format($total_revenue_local, 2, ',', '.') }}</p>
                    <small class="text-muted">
                        @foreach($total_revenue_by_cur as $cur => $val)
                            {{ $cur }} {{ number_format($val, 2, ',', '.') }}@if(!$loop->last), @endif
                        @endforeach
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h6 class="card-title text-muted">Pedidos Entregues</h6>
                    <p class="h3 mb-0">{{ $delivered_count }}</p>
                    <small class="text-muted">{{ $delivered_rate }}% de entrega</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-secondary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Clientes Únicos</h6>
                    <p class="h3 mb-0">{{ $unique_customers }}</p>
                    <small class="text-muted">Média {{ $average_orders_per_customer }} pedidos/cliente</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumo financeiro --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Faturamento Bruto</h6>
                    <p class="h4 mb-0">R$ {{ number_format($total_revenue_local, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total de Reembolsos</h6>
                    <p class="h4 text-danger mb-0">R$ {{ number_format($total_refunds, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title text-muted">Receita Líquida</h6>
                    <p class="h4 text-success mb-0">R$ {{ number_format($net_revenue, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Taxa de reembolso / ticket / produto estrela --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-{{ $refund_rate_level }}">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Taxa de Reembolso</h6>
                    <p class="display-6 mb-2">{{ $refund_rate_percent }}%</p>
                    <span class="badge bg-{{ $refund_rate_level }}">
                        @if($refund_rate_level === 'success') Saudável
                        @elseif($refund_rate_level === 'warning') Atenção
                        @else Crítico
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Ticket Médio</h6>
                    <p class="h3 mb-0">R$ {{ number_format($average_ticket, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php $tp = $top_product; @endphp
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-title text-muted">Produto Mais Vendido</h6>
                    @if($tp)
                        <p class="mb-1"><strong>{{ $tp['name'] }}</strong></p>
                        <small class="text-muted">Quantidade: {{ $tp['qty'] }}</small><br>
                        <small class="text-muted">Receita: R$ {{ number_format($tp['revenue'], 2, ',', '.') }}</small>
                    @else
                        <p class="text-muted mb-0">Sem dados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Análise de Upsell --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Pedidos com Upsell (mais de 1 item)</h6>
                    <p class="mb-1">Quantidade: {{ $upsell['upsell_orders_count'] }}</p>
                    <p class="mb-1">Receita: R$ {{ number_format($upsell['upsell_orders_revenue'], 2, ',', '.') }}</p>
                    <p class="mb-0">Ticket médio: R$ {{ number_format($upsell['avg_ticket_upsell'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-body">
                    <h6 class="card-title text-muted">Pedidos sem Upsell</h6>
                    <p class="mb-1">Quantidade: {{ $upsell['no_upsell_orders_count'] }}</p>
                    <p class="mb-1">Receita: R$ {{ number_format($upsell['no_upsell_orders_revenue'], 2, ',', '.') }}</p>
                    <p class="mb-0">Ticket médio: R$ {{ number_format($upsell['avg_ticket_no_upsell'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Top 5 produtos por receita --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Produtos por Receita</h5>
                    <ul class="list-group list-group-flush">
                        @forelse($top5_products as $name => $p)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $name }}</span>
                                <span>R$ {{ number_format($p['revenue'], 2, ',', '.') }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Sem dados suficientes.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        {{-- Gráfico de barras simples para top 5 --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Receita por Produto (Top 5)</h5>
                    <canvas id="topProductsChart" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top 10 cidades --}}
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Top 10 Cidades em Vendas</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Cidade</th>
                                <th>Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($top_cities as $city => $count)
                                <tr>
                                    <td>{{ $city }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Vendas por dia --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Vendas por Dia</h5>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales_per_day as $day => $value)
                        <tr>
                            <td>{{ $day }}</td>
                            <td>R$ {{ number_format($value, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabela de pedidos --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title d-flex justify-content-between align-items-center">
                <span>Tabela de Pedidos</span>
                <form class="d-flex ms-3" method="get" action="{{ url()->current() }}">
                    <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm me-2" placeholder="Buscar por ID, cliente ou email">
                    <button class="btn btn-sm btn-outline-primary">Buscar</button>
                </form>
            </h5>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Status Pag.</th>
                            <th>Status Entrega</th>
                            <th>Valor</th>
                            <th>Moeda</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order['id'] }}</td>
                                <td>{{ $order['name'] }}</td>
                                <td>{{ $order['email'] }}</td>
                                <td>{{ $order['financial_status'] ?? '' }}</td>
                                <td>{{ $order['fulfillment_status'] ?? '' }}</td>
                                <td>R$ {{ number_format($order['amount'], 2, ',', '.') }}</td>
                                <td>{{ $order['currency'] }}</td>
                                <td>{{ $order['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted text-center">Nenhum pedido encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $orders->onEachSide(0)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function() {
        const products = @json($top5_products ?? []);
        const labels = Object.keys(products);
        const data = labels.map(name => products[name].revenue || 0);

        if (labels.length > 0) {
            const ctx = document.getElementById('topProductsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Receita (local)',
                        data: data,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        }
    })();
</script>
@endpush
