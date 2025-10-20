<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    // GET /search/products?q=texto
    public function products(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') return response()->json([]);

        // Postgres: ILIKE para case-insensitive
        $items = Product::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'ILIKE', "%{$q}%")
                  ->orWhere('code', 'ILIKE', "%{$q}%");
            })
            ->where('active', true)
            ->limit(10)
            ->get(['id','code','name','price_cash','stock']);

        return response()->json($items);
    }

    // GET /search/suppliers?q=texto
    public function suppliers(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') return response()->json([]);

        $items = Supplier::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'ILIKE', "%{$q}%")
                  ->orWhere('ruc', 'ILIKE', "%{$q}%");
            })
            ->where('active', true)
            ->limit(10)
            ->get(['id','name','ruc','phone','email']);

        return response()->json($items);
    }
}
