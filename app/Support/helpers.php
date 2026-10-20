<?php

use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Warehouse;

if (! function_exists('money')) {
    function money($value, string $symbol = 'Rs '): string
    {
        return $symbol . number_format((float) $value, 0);
    }
}

if (! function_exists('money_precise')) {
    function money_precise($value, string $symbol = 'Rs '): string
    {
        return $symbol . number_format((float) $value, 2);
    }
}

if (! function_exists('stockable_url')) {
    function stockable_url($stockable): string
    {
        if ($stockable instanceof Warehouse) {
            return route('warehouses.show', $stockable);
        }
        if ($stockable instanceof Shop) {
            return route('shops.show', $stockable);
        }

        return '#';
    }
}

if (! function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        $map = [
            'active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger',
            'paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger',
            'delivered' => 'success', 'in_transit' => 'info', 'dispatched' => 'primary',
            'assigned' => 'secondary', 'pending' => 'warning', 'draft' => 'secondary',
            'cancelled' => 'dark', 'failed' => 'danger', 'picked_up' => 'info',
            'received' => 'success',
        ];
        $tone = $map[$status] ?? 'secondary';

        return '<span class="badge badge-soft-' . $tone . '">' . e(ucwords(str_replace('_', ' ', (string) $status))) . '</span>';
    }
}

if (! function_exists('transfer_status_badge')) {
    function transfer_status_badge(?string $status): string
    {
        return status_badge($status);
    }
}
