<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currency = config('app.currency', '$');

        // NOTE: legacy app used tenant_id; for now select tenant 1 or first tenant
        $tenant = DB::table('tenants')->select('id')->first();
        $tenant_id = $tenant ? (int) $tenant->id : 1;

        $today = Carbon::today()->toDateString();

        $customer_count = (int) DB::table('customers')->where('tenant_id', $tenant_id)->where('is_active', 1)->count();
        $supplier_count = (int) DB::table('suppliers')->where('tenant_id', $tenant_id)->where('is_active', 1)->count();
        $product_count = (int) DB::table('products')->where('tenant_id', $tenant_id)->count();

        $inventory_value = (float) DB::table('products')
            ->where('tenant_id', $tenant_id)
            ->selectRaw('COALESCE(SUM(current_stock * buying_price), 0) as total')
            ->value('total');

        $low_stock_count = (int) DB::table('products')->where('tenant_id', $tenant_id)->whereColumn('current_stock', '<=', 'min_stock')->count();
        $credit_customer_count = (int) DB::table('customers')->where('tenant_id', $tenant_id)->where('credit_balance', '>', 0)->count();
        $credit_supplier_count = (int) DB::table('suppliers')->where('tenant_id', $tenant_id)->where('credit_balance', '>', 0)->count();
        $today_installations = (int) DB::table('customer_equipment')->where('tenant_id', $tenant_id)->where('installation_date', $today)->count();
        $upcoming_maintenance = (int) DB::table('customer_maintenance')->where('tenant_id', $tenant_id)->where('scheduled_on', '>=', $today)->where('status', '<>', 'completed')->count();
        $near_reorder = (int) DB::table('products')->where('tenant_id', $tenant_id)->whereRaw('current_stock <= (min_stock + 3)')->count();
        $warranty_alerts = (int) DB::table('customer_equipment')->where('tenant_id', $tenant_id)->whereNotNull('warranty_expiry')->where('warranty_expiry', '<=', Carbon::parse($today)->addDays(30)->toDateString())->count();
        $maintenance_due = (int) DB::table('customer_maintenance')->where('tenant_id', $tenant_id)->where('scheduled_on', '<=', $today)->where('status', '<>', 'completed')->count();

        // Simple trend (place-holder) based on inventory_value
        $inventory_trend = [];
        for ($i = 0; $i < 6; $i++) {
            $inventory_trend[] = round($inventory_value * (1 + ($i * 0.03) - 0.02), 2);
        }

        $chartSvg = $this->chartSvg($inventory_trend, '#ffffff', 'rgba(255,255,255,0.12)');

        return view('pages.dashboard', compact(
            'currency', 'customer_count', 'supplier_count', 'product_count', 'inventory_value', 'low_stock_count',
            'credit_customer_count', 'credit_supplier_count', 'today_installations', 'upcoming_maintenance', 'near_reorder',
            'warranty_alerts', 'maintenance_due', 'inventory_trend', 'chartSvg'
        ));
    }

    private function chartSvg(array $values, string $color, string $fillColor = '', int $height = 190, int $width = 520): string
    {
        if (empty($values)) {
            return '<div class="muted">No data available.</div>';
        }

        $values = array_values($values);
        $count = count($values);
        $max = max(max($values), 1);
        $min = min(min($values), 0);
        $spread = $max - $min;
        if ($spread === 0) $spread = 1;

        $step = $count > 1 ? ($width - 24) / ($count - 1) : 0;
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $value = (float) $values[$i];
            $x = 12 + ($count === 1 ? $width / 2 : $i * $step);
            $y = $height - 16 - (($value - $min) / $spread) * ($height - 32);
            $points[] = [$x, $y];
        }

        $path = '';
        foreach ($points as $index => [$x, $y]) {
            $path .= ($index === 0 ? 'M' : 'L') . $x . ',' . $y . ' ';
        }

        $lastPoint = $points[$count - 1] ?? [12, $height - 16];
        $firstPoint = $points[0] ?? [12, $height - 16];
        $areaPath = rtrim($path) . ' L ' . $lastPoint[0] . ',' . ($height - 12) . ' L ' . $firstPoint[0] . ',' . ($height - 12) . ' Z';

        $svg = '<svg class="chart-svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Chart">';
        $svg .= '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" rx="12" fill="#16161f"></rect>';
        if ($fillColor !== '') {
            $svg .= '<path d="' . $areaPath . '" fill="' . $fillColor . '" opacity="0.35"></path>';
        }
        $svg .= '<path d="' . rtrim($path) . '" fill="none" stroke="' . $color . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>';
        foreach ($points as [$x, $y]) {
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4.5" fill="' . $color . '"></circle>';
        }
        $svg .= '</svg>';

        return $svg;
    }
}
