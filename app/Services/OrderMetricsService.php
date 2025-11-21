<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OrderMetricsService
{
    protected string $endpoint;
    protected int $cacheTtl = 600;

    public function __construct()
    {
        $this->endpoint = config('services.six.endpoint', env('SIX_API_ENDPOINT', 'https://dev-crm.ogruposix.com/candidato-teste-pratico-backend-dashboard/test-orders'));
    }

    /**
     * Busca pedidos da API com cache
     */
    protected function fetchOrders(): array
    {
        return Cache::remember('six_orders_v6', $this->cacheTtl, function () {
            try {
                $request = Http::timeout(15);

                // Verificação SSL para evitar cURL 60
                if (app()->environment('local')) {
                    $request = $request->withoutVerifying();
                }

                $response = $request->get($this->endpoint);

                if (! $response->successful()) {
                    return [];
                }

                $json = $response->json();

                $orders = collect($json['orders'] ?? [])
                    ->map(fn ($item) => $item['order'] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                return $orders;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Normaliza valores monetários (vírgula/ponto, milhar)
     */
    protected function money($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $s = preg_replace('/[^0-9,\.\-]/', '', (string) $value);

        if (substr_count($s, ',') === 1 && substr_count($s, '.') > 0 && strrpos($s, ',') > strrpos($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        return (float) $s;
    }

    /**
     * Retorna todas as métricas
     */
    public function getMetrics(): array
    {
        $orders = $this->fetchOrders();

        $totalOrders = count($orders);

        $faturamentoBruto = 0.0;
        $totalRevenueByCurrency = [];
        $totalRefunds = 0.0;
        $delivered = 0;
        $customers = [];
        $ordersWithRefund = 0;

        $products = [];
        $cities = [];
        $salesPerDay = [];

        $ordersTable = [];

        $upsellOrdersCount = 0;
        $upsellOrdersRevenue = 0.0;
        $noUpsellOrdersCount = 0;
        $noUpsellOrdersRevenue = 0.0;

        foreach ($orders as $order) {

            // FATURAMENTO BRUTO REAL DO PEDIDO
            $orderTotal = $this->money($order['total_price'] ?? $order['current_total_price'] ?? 0);

            $faturamentoBruto += $orderTotal;

            $currency = $order['local_currency'] ?? ($order['currency'] ?? 'USD');
            $totalRevenueByCurrency[$currency] = ($totalRevenueByCurrency[$currency] ?? 0) + $orderTotal;

            $hasRefundThisOrder = false;

            // REEMBOLSO FINANCEIRO
            if (!empty($order['refunds'])) {
                foreach ($order['refunds'] as $refund) {
                    $totalRefunds += $this->money($refund['total_amount'] ?? 0);
                }
                $hasRefundThisOrder = true;
            }

            // ITENS DO PEDIDO (APENAS VENDAS E UPSSELL)
            $lineItems = $order['line_items'] ?? [];
            $totalQtyInOrder = 0;

            foreach ($lineItems as $item) {
                $name = $item['name'] ?? 'Sem nome';
                $qty  = (int) ($item['quantity'] ?? 0);

                $itemRevenue = $this->money($item['local_currency_item_total_price'] ?? 0);
                $totalQtyInOrder += $qty;

                if (!isset($products[$name])) {
                    $products[$name] = [
                        'sold_qty' => 0,
                        'revenue'  => 0.0,
                    ];
                }

                $products[$name]['sold_qty'] += $qty;
                $products[$name]['revenue']  += $itemRevenue;

                if (!empty($item['is_refunded'])) {
                    $hasRefundThisOrder = true;
                }
            }

            if ($hasRefundThisOrder) {
                $ordersWithRefund++;
            }

            // PEDIDOS ENTREGUES
            if (($order['fulfillment_status'] ?? '') === 'Fully Fulfilled') {
                $delivered++;
            }

            // CLIENTES ÚNICOS
            $customerId = $order['customer']['id']
                ?? ($order['contact_email'] ?? null);

            if ($customerId) {
                $customers[$customerId] = true;
            }

            // TOP CIDADES
            $city = $order['shipping_address']['city'] ?? 'Não informado';
            $cities[$city] = ($cities[$city] ?? 0) + 1;

            // VENDAS POR DIA
            $createdAt = $order['created_at'] ?? null;
            $day = $createdAt ? substr($createdAt, 0, 10) : 'Sem data';
            $salesPerDay[$day] = ($salesPerDay[$day] ?? 0) + $orderTotal;

            // ANÁLISE DE UPSELL
            if ($totalQtyInOrder > 1) {
                $upsellOrdersCount++;
                $upsellOrdersRevenue += $orderTotal;
            } else {
                $noUpsellOrdersCount++;
                $noUpsellOrdersRevenue += $orderTotal;
            }

            // TABELA DE PEDIDOS
            $ordersTable[] = [
                'id'                 => $order['id'] ?? null,
                'name'               => trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? '')),
                'email'              => $order['contact_email'] ?? '',
                'financial_status'   => $order['financial_status'] ?? '',
                'fulfillment_status' => $order['fulfillment_status'] ?? '',
                'amount'             => $orderTotal,
                'currency'           => $currency,
                'created_at'         => $createdAt,
            ];
        }

        // TOP 5 PRODUTOS POR RECEITA
        $productsByRevenue = $products;
        uasort($productsByRevenue, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        $top5Products = array_slice($productsByRevenue, 0, 5, true);

        // PRODUTO MAIS VENDIDO
        $topProduct = null;
        if (!empty($products)) {
            $byQty = $products;
            uasort($byQty, fn ($a, $b) => $b['sold_qty'] <=> $a['sold_qty']);
            $name = array_key_first($byQty);
            $topProduct = [
                'name'    => $name,
                'qty'     => $byQty[$name]['sold_qty'],
                'revenue' => $byQty[$name]['revenue'],
            ];
        }

        // TOP 10 CIDADES
        arsort($cities);
        $topCities = array_slice($cities, 0, 10, true);

        // TAXA DE REEMBOLSO
        $refundRatePercent = $totalOrders > 0
            ? round(($ordersWithRefund / $totalOrders) * 100, 2)
            : 0.0;

        $refundRateLevel =
            $refundRatePercent < 5   ? 'success' :
            ($refundRatePercent < 10 ? 'warning' : 'danger');

        // DEMAIS MÉTRICAS
        $uniqueCustomersCount = count($customers);

        $deliveredRate = $totalOrders > 0
            ? round(($delivered / $totalOrders) * 100, 2)
            : 0.0;

        $averageTicket = $totalOrders > 0
            ? $faturamentoBruto / $totalOrders
            : 0.0;

        $avgTicketUpsell = $upsellOrdersCount > 0
            ? $upsellOrdersRevenue / $upsellOrdersCount
            : 0.0;

        $avgTicketNoUpsell = $noUpsellOrdersCount > 0
            ? $noUpsellOrdersRevenue / $noUpsellOrdersCount
            : 0.0;

        return [
            'total_orders'          => $totalOrders,
            'total_revenue_local'   => $faturamentoBruto,
            'total_revenue_by_cur'  => $totalRevenueByCurrency,

            'total_refunds'         => $totalRefunds,
            'net_revenue'           => $faturamentoBruto - $totalRefunds,

            'delivered_count'       => $delivered,
            'delivered_rate'        => $deliveredRate,
            'unique_customers'      => $uniqueCustomersCount,
            'average_orders_per_customer' =>
                $uniqueCustomersCount ? round($totalOrders / $uniqueCustomersCount, 2) : 0.0,

            'refund_rate_percent'   => $refundRatePercent,
            'refund_rate_level'     => $refundRateLevel,

            'top_product'           => $topProduct,
            'orders_table'          => $ordersTable,

            'top5_products'         => $top5Products,
            'top_cities'            => $topCities,
            'sales_per_day'         => $salesPerDay,
            'average_ticket'        => $averageTicket,

            'upsell' => [
                'upsell_orders_count'      => $upsellOrdersCount,
                'upsell_orders_revenue'    => $upsellOrdersRevenue,
                'no_upsell_orders_count'   => $noUpsellOrdersCount,
                'no_upsell_orders_revenue' => $noUpsellOrdersRevenue,
                'avg_ticket_upsell'        => $avgTicketUpsell,
                'avg_ticket_no_upsell'     => $avgTicketNoUpsell,
            ],
        ];
    }

}
