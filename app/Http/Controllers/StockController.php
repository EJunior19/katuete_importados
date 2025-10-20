<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class StockController extends Controller
{
    public function check(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        $shortages = [];
        $available = [];

        foreach ($data['items'] as $it) {
            $stock = (int) (Product::whereKey($it['product_id'])->value('stock') ?? 0);
            $qty   = (int) $it['qty'];

            $available[$it['product_id']] = $stock;
            if ($stock < $qty) {
                $shortages[] = [
                    'product_id' => $it['product_id'],
                    'stock'      => $stock,
                    'required'   => $qty,
                    'max_sell'   => $stock,
                ];
            }
        }

        return response()->json([
            'ok'        => count($shortages) === 0,
            'shortages' => $shortages,
            'available' => $available,
        ], count($shortages) ? 409 : 200);
    }
}
