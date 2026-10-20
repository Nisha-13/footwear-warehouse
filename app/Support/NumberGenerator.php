<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public static function make(string $prefix, string $table, string $column, int $pad = 5): string
    {
        $date = now()->format('Ymd');
        $base = sprintf('%s-%s-', $prefix, $date);

        $last = DB::table($table)
            ->where($column, 'like', $base . '%')
            ->orderByDesc($column)
            ->value($column);

        $seq = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($base)) + 1;
        }

        return $base . str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
    }

    public static function invoice(): string
    {
        return self::make('PUR', 'purchases', 'invoice_no');
    }

    public static function saleInvoice(): string
    {
        return self::make('SL', 'sales', 'invoice_no');
    }

    public static function ledger(): string
    {
        return self::make('LED', 'ledger_entries', 'entry_no');
    }

    public static function transfer(): string
    {
        return self::make('TRF', 'transfers', 'transfer_no');
    }

    public static function delivery(): string
    {
        return self::make('DLV', 'deliveries', 'delivery_no');
    }
}
