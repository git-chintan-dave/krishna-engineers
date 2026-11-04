<?php

namespace App\Services;

use App\Models\SectionSize;
use App\Models\Tool;
use Illuminate\Support\Collection;

class StockDashboardService
{
    private const int STOCK_ALERT_LIMIT = 20;

    /**
     * Get raw material stock dashboard data.
     */
    public function rawMaterialStock(): array
    {
        $threshold = max(
            1,
            (int) config('inventory.low_stock_threshold', 10)
        );

        $summary = SectionSize::query()
            ->selectRaw(
                '
                    COUNT(*) as total,
                    SUM(CASE WHEN quantity >= ? THEN 1 ELSE 0 END) as healthy,
                    SUM(CASE WHEN quantity > 0 AND quantity < ? THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
                ',
                [$threshold, $threshold]
            )
            ->first();

        $lowItems = SectionSize::query()
            ->with('section:id,name')
            ->where('quantity', '>', 0)
            ->where('quantity', '<', $threshold)
            ->orderBy('quantity')
            ->orderBy('section_id')
            ->orderBy('size')
            ->limit(self::STOCK_ALERT_LIMIT)
            ->get()
            ->map(fn (SectionSize $item) => [
                'id' => $item->id,
                'name' => $item->section?->name ?? 'Unknown section',
                'detail' => $item->size,
                'quantity' => (int) $item->quantity,
            ]);

        $outItems = SectionSize::query()
            ->with('section:id,name')
            ->where('quantity', 0)
            ->orderBy('section_id')
            ->orderBy('size')
            ->limit(self::STOCK_ALERT_LIMIT)
            ->get()
            ->map(fn (SectionSize $item) => [
                'id' => $item->id,
                'name' => $item->section?->name ?? 'Unknown section',
                'detail' => $item->size,
                'quantity' => (int) $item->quantity,
            ]);

        return $this->formatStockResponse(
            $summary,
            $lowItems,
            $outItems,
            $threshold
        );
    }

    /**
     * Get tools stock dashboard data.
     */
    public function toolStock(): array
    {
        $threshold = max(
            1,
            (int) config('inventory.low_stock_threshold', 10)
        );

        $summary = Tool::query()
            ->selectRaw(
                '
                    COUNT(*) as total,
                    SUM(CASE WHEN quantity >= ? THEN 1 ELSE 0 END) as healthy,
                    SUM(CASE WHEN quantity > 0 AND quantity < ? THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
                ',
                [$threshold, $threshold]
            )
            ->first();

        $lowItems = Tool::query()
            ->where('quantity', '>', 0)
            ->where('quantity', '<', $threshold)
            ->orderBy('quantity')
            ->orderBy('type')
            ->orderBy('name')
            ->limit(self::STOCK_ALERT_LIMIT)
            ->get()
            ->map(fn (Tool $tool) => [
                'id' => $tool->id,
                'name' => $tool->name,
                'detail' => $tool->type,
                'quantity' => (int) $tool->quantity,
            ]);

        $outItems = Tool::query()
            ->where('quantity', 0)
            ->orderBy('type')
            ->orderBy('name')
            ->limit(self::STOCK_ALERT_LIMIT)
            ->get()
            ->map(fn (Tool $tool) => [
                'id' => $tool->id,
                'name' => $tool->name,
                'detail' => $tool->type,
                'quantity' => (int) $tool->quantity,
            ]);

        return $this->formatStockResponse(
            $summary,
            $lowItems,
            $outItems,
            $threshold
        );
    }

    /**
     * Format common stock dashboard response.
     */
    private function formatStockResponse(
        object $summary,
        Collection $lowItems,
        Collection $outItems,
        int $threshold
    ): array {
        $totalLow = (int) $summary->low;
        $totalOut = (int) $summary->out_of_stock;

        $items = $lowItems
            ->concat($outItems)
            ->sortBy('quantity')
            ->values();

        return [
            'threshold' => $threshold,

            'total' => (int) $summary->total,
            'healthy' => (int) $summary->healthy,
            'low' => $totalLow,
            'out' => $totalOut,

            'items' => $items->all(),

            'alertLimit' => self::STOCK_ALERT_LIMIT,

            'hasMoreAlerts' => (
                $totalLow > $lowItems->count()
                || $totalOut > $outItems->count()
            ),
        ];
    }
}
