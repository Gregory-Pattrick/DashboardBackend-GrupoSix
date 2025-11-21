<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OrderService
{
    protected $endpoint;
    protected $cacheTtl = 600;

    public function __construct()
    {
        $this->endpoint = config('services.six.endpoint');
    }

    protected function fetchOrders(): array
    {
        return Cache::remember("six_orders_raw", $this->cacheTtl, function () {
            return Http::withoutVerifying()->timeout(10)->get($this->endpoint)->json() ?? [];
        });
    }

    protected function money($v)
    {
        if (is_numeric($v)) return (float)$v;
        $s = preg_replace('/[^0-9,\.]/', '', (string)$v);
        if (substr_count($s, ',') == 1 && substr_count($s, '.') > 0 && strrpos($s, ',') > strrpos($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
        return (float)$s;
    }

    public function getMetrics(): array
    {
        $orders = $this->fetchOrders();
        $totalOrders = count($orders);

        $totalRevenue = 0;
        $totalRefunds = 0;
        $delivered = 0;
        $customers = [];
        $products = [];
        $cities = [];
        $refundProducts = [];
        $salesPerDay = [];
        $table = [];

        foreach ($orders as $o) {
            $amount = $this->money($o['local_currency_amount'] ?? 0);
            $totalRevenue += $amount;

            foreach ($o['refunds'] ?? [] as $r) {
                $totalRefunds += $this->money($r['total_amount'] ?? 0);
            }

            if (($o['fulfillment_status'] ?? "") === "Fully Fulfilled") $delivered++;

            $cid = $o['customer']['id'] ?? ($o['contact_email'] ?? null);
            if ($cid) $customers[$cid] = true;

            $city = $o['shipping_address']['city'] ?? "N/A";
            $cities[$city] = ($cities[$city] ?? 0) + 1;

            $day = substr($o['created_at'] ?? "", 0, 10);
            $salesPerDay[$day] = ($salesPerDay[$day] ?? 0) + $amount;

            foreach ($o['line_items'] ?? [] as $item) {
                $name = $item['name'] ?? "Unknown";
                $qty = $item['quantity'] ?? 0;
                $rev = $this->money($item['local_currency_item_total_price'] ?? 0);

                if (!isset($products[$name])) $products[$name] = ["qty" => 0, "revenue" => 0];
                $products[$name]["qty"] += $qty;
                $products[$name]["revenue"] += $rev;

                if (!empty($item['is_refunded'])) {
                    $refundProducts[$name] = ($refundProducts[$name] ?? 0) + $qty;
                }
            }

            $table[] = [
                "id" => $o["id"] ?? null,
                "name" => trim(($o['customer']['first_name'] ?? "") . " " . ($o['customer']['last_name'] ?? "")),
                "email" => $o["contact_email"] ?? "",
                "financial_status" => $o["financial_status"] ?? "",
                "fulfillment_status" => $o["fulfillment_status"] ?? "",
                "value" => $amount,
                "created_at" => $o["created_at"] ?? "",
            ];
        }

        arsort($cities);
        uasort($products, fn($a, $b) => $b["revenue"] <=> $a["revenue"]);
        arsort($refundProducts);
        ksort($salesPerDay);

        $topProductKey = array_key_first($products);
        $topProduct = $topProductKey ? [
            "name" => $topProductKey,
            "qty" => $products[$topProductKey]["qty"],
            "revenue" => $products[$topProductKey]["revenue"]
        ] : null;

        return [
            "total_orders" => $totalOrders,
            "total_revenue" => $totalRevenue,
            "total_refunds" => $totalRefunds,
            "net_revenue" => $totalRevenue - $totalRefunds,
            "delivered_count" => $delivered,
            "delivered_rate" => $totalOrders ? round(($delivered / $totalOrders) * 100, 2) : 0,
            "unique_customers" => count($customers),
            "average_orders_per_customer" => count($customers) ? round($totalOrders / count($customers), 2) : 0,
            "top_product" => $topProduct,
            "top5_products" => array_slice($products, 0, 5, true),
            "top_cities" => array_slice($cities, 0, 10, true),
            "top_refund_products" => $refundProducts,
            "sales_per_day" => $salesPerDay,
            "orders_table" => $table
        ];
    }
}
