<?php

namespace App\Support;

use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function getStock(ProductVariant $variant, Model $stockable): Stock
    {
        return Stock::firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'stockable_type' => $stockable->getMorphClass(),
                'stockable_id' => $stockable->getKey(),
            ],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );
    }

    public function add(ProductVariant $variant, Model $stockable, int $qty, string $type, array $meta = []): Stock
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity to add must be greater than zero.');
        }

        return DB::transaction(function () use ($variant, $stockable, $qty, $type, $meta) {
            $stock = $this->getStock($variant, $stockable);
            $stock->quantity += $qty;
            $stock->save();

            $this->logMovement($variant, $stockable, $type, $qty, $stock->quantity, $meta);

            return $stock;
        });
    }

    public function remove(ProductVariant $variant, Model $stockable, int $qty, string $type, array $meta = [], bool $allowNegative = false): Stock
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity to remove must be greater than zero.');
        }

        return DB::transaction(function () use ($variant, $stockable, $qty, $type, $meta, $allowNegative) {
            $stock = $this->getStock($variant, $stockable);

            if (! $allowNegative && $stock->quantity < $qty) {
                throw new RuntimeException(sprintf(
                    'Insufficient stock for %s. Available: %d, requested: %d.',
                    $variant->sku,
                    $stock->quantity,
                    $qty
                ));
            }

            $stock->quantity -= $qty;
            $stock->save();

            $this->logMovement($variant, $stockable, $type, -$qty, $stock->quantity, $meta);

            return $stock;
        });
    }

    public function adjust(ProductVariant $variant, Model $stockable, int $newQty, array $meta = []): Stock
    {
        return DB::transaction(function () use ($variant, $stockable, $newQty, $meta) {
            $stock = $this->getStock($variant, $stockable);
            $diff = $newQty - $stock->quantity;
            $stock->quantity = $newQty;
            $stock->save();

            if ($diff !== 0) {
                $this->logMovement($variant, $stockable, 'adjustment', $diff, $stock->quantity, $meta);
            }

            return $stock;
        });
    }

    protected function logMovement(ProductVariant $variant, Model $stockable, string $type, int $qty, int $balance, array $meta): void
    {
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'stockable_type' => $stockable->getMorphClass(),
            'stockable_id' => $stockable->getKey(),
            'type' => $type,
            'quantity' => $qty,
            'balance_after' => $balance,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'note' => $meta['note'] ?? null,
            'user_id' => $meta['user_id'] ?? auth()->id(),
        ]);
    }
}
