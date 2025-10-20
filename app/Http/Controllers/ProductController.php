<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /* =======================
     * Helpers
     * ======================= */

    /**
     * Limpia un valor monetario o numérico que puede venir con puntos, comas o texto.
     * "150.000" -> 150000 (int), ""|null -> null
     */
    private function cleanInt($v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        return (int) preg_replace('/\D+/', '', (string) $v);
    }

    /* =======================
     * VISTAS CRUD
     * ======================= */

    public function index()
    {
        $products = Product::with(['brand','category','supplier','installments'])
            ->latest()
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        // Preview del próximo code sin consumir la secuencia (solo PG)
        $nextId = null;
        $code   = null;

        try {
            $row = DB::selectOne("
                SELECT last_value + increment_by AS next_id
                FROM pg_sequences
                WHERE schemaname='public' AND sequencename='products_id_seq'
            ");
            $nextId = $row?->next_id;
            $code   = $nextId ? sprintf('PRD-%05d', $nextId) : null;
        } catch (\Throwable $e) {
            // Para MySQL/SQLite, omitimos preview silenciosamente
        }

        $brands     = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers  = Supplier::orderBy('name')->get();

        return view('products.create', compact('nextId','code','brands','categories','suppliers'));
    }

    public function store(Request $request)
    {
        // 1) Normalizar entradas (enteros en Gs)
        $priceCash = $this->cleanInt($request->input('price_cash'));

        // Acepta ambos nombres por compatibilidad: installment_prices[] (nuevo) o installment_price[] (legacy)
        $rawInstallmentPrices = (array) $request->input('installment_prices', $request->input('installment_price', []));
        $installmentPrices    = array_map(fn($v) => $this->cleanInt($v), $rawInstallmentPrices);

        $installments = (array) $request->input('installments', []);

        // Merge para validar sobre enteros
        $request->merge([
            'price_cash'         => $priceCash,
            'installment_prices' => $installmentPrices,
            'installments'       => $installments,
        ]);

        // 2) Validación
        $validated = $request->validate([
            'name'                 => ['required','string','max:255'],
            'brand_id'             => ['required','exists:brands,id'],
            'category_id'          => ['required','exists:categories,id'],
            'supplier_id'          => ['required','exists:suppliers,id'],
            'price_cash'           => ['nullable','integer','min:0'],
            'active'               => ['required','boolean'],
            'notes'                => ['nullable','string'],

            'installments'         => ['array'],
            'installments.*'       => ['nullable','integer','min:1'],
            'installment_prices'   => ['array'],
            'installment_prices.*' => ['nullable','integer','min:0'],
        ]);

        // 3) Persistencia
        DB::transaction(function () use ($validated, $installments, $installmentPrices) {

            // Crear producto
            $product = Product::create([
                'name'        => $validated['name'],
                'brand_id'    => $validated['brand_id'],
                'category_id' => $validated['category_id'],
                'supplier_id' => $validated['supplier_id'],
                'price_cash'  => $validated['price_cash'] ?? null, // entero en Gs
                'active'      => (bool)($validated['active'] ?? true),
                'notes'       => $validated['notes'] ?? null,
            ]);

            // Pares cuota -> precio (ignorando vacíos)
            $rows = [];
            foreach ($installments as $i => $n) {
                $n = (int) $n;
                $p = $installmentPrices[$i] ?? null;
                if ($n && $p) {
                    $rows[] = [
                        'installments'      => $n,
                        'installment_price' => (int) $p, // entero en Gs
                    ];
                }
            }

            if (!empty($rows)) {
                $product->installments()->createMany($rows);
            }
        });

        return redirect()
            ->route('products.index')
            ->with('success', "Producto {$validated['name']} creado correctamente con cuotas.");
    }

    public function show(Product $product)
    {
        $product->load(['brand','category','supplier','installments']);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $brands     = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $suppliers  = Supplier::orderBy('name')->get();

        // Cargar cuotas para mostrarlas en el form de edición
        $product->load('installments');

        return view('products.edit', compact('product','brands','categories','suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        // 1) Normalizar entradas (enteros en Gs)
        $priceCash = $this->cleanInt($request->input('price_cash'));

        // Acepta ambos nombres: installment_prices[] (nuevo) o installment_price[] (legacy)
        $rawInstallmentPrices = (array) $request->input('installment_prices', $request->input('installment_price', []));
        $installmentPrices    = array_map(fn($v) => $this->cleanInt($v), $rawInstallmentPrices);

        $installments = (array) $request->input('installments', []);

        // Merge para validar sobre enteros
        $request->merge([
            'price_cash'         => $priceCash,
            'installment_prices' => $installmentPrices,
            'installments'       => $installments,
        ]);

        // 2) Validación
        $validated = $request->validate([
            'name'                 => ['required','string','max:255'],
            'brand_id'             => ['required','exists:brands,id'],
            'category_id'          => ['required','exists:categories,id'],
            'supplier_id'          => ['required','exists:suppliers,id'],
            'price_cash'           => ['nullable','integer','min:0'],
            'active'               => ['required','boolean'],
            'notes'                => ['nullable','string'],

            'installments'         => ['array'],
            'installments.*'       => ['nullable','integer','min:1'],
            'installment_prices'   => ['array'],
            'installment_prices.*' => ['nullable','integer','min:0'],
        ]);

        // 3) Persistencia
        DB::transaction(function () use ($product, $validated, $installments, $installmentPrices) {

            // Actualizar cabecera
            $product->update([
                'name'        => $validated['name'],
                'brand_id'    => $validated['brand_id'],
                'category_id' => $validated['category_id'],
                'supplier_id' => $validated['supplier_id'],
                'price_cash'  => $validated['price_cash'] ?? null, // entero en Gs
                'active'      => (bool)($validated['active'] ?? true),
                'notes'       => $validated['notes'] ?? null,
            ]);

            // Reemplazar cuotas
            $product->installments()->delete();

            $rows = [];
            foreach ($installments as $i => $n) {
                $n = (int) $n;
                $p = $installmentPrices[$i] ?? null;
                if ($n && $p) {
                    $rows[] = [
                        'installments'      => $n,
                        'installment_price' => (int) $p, // entero en Gs
                    ];
                }
            }

            if (!empty($rows)) {
                $product->installments()->createMany($rows);
            }
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success','Producto actualizado con cuotas.');
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            $product->installments()->delete();
            $product->delete();
        });

        return redirect()
            ->route('products.index')
            ->with('success','Producto eliminado');
    }

    /* =======================
     * API
     * ======================= */

    /**
     * Autocomplete / búsqueda libre (nombre, code, id).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $driver = DB::connection()->getDriverName(); // mysql, pgsql, sqlite...
        $like   = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        $needle = "%{$q}%";

        $rows = Product::query()
            ->where('active', true)
            ->where(function($w) use ($like, $needle, $q) {
                $w->where('name', $like, $needle)
                  ->orWhere('code', $like, $needle);

                if (ctype_digit($q)) {
                    $w->orWhere('id', (int)$q);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get([
                'id','code','name','stock','price_cash',
                'brand_id','category_id','supplier_id'
            ]);

        return response()->json($rows);
    }

    /**
     * Buscar por ID numérico.
     */
    public function findById(int $id)
    {
        $prod = Product::where('active', true)->find($id);

        if (!$prod) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        return response()->json([
            'id'           => $prod->id,
            'code'         => $prod->code,
            'name'         => $prod->name,
            'price_cash'   => $prod->price_cash, // entero en Gs
            'stock'        => $prod->stock,
            'installments' => $prod->installments()->get(['installments','installment_price']),
        ]);
    }

    /**
     * Buscar por code (o ID numérico). Devuelve price_cash + cuotas.
     */
    public function findByCode($code)
    {
        $q = Product::query()->where('active', true);

        if (ctype_digit((string)$code)) {
            $prod = $q->where('id', (int)$code)->first();
        } else {
            $prod = $q->where('code', $code)->first();
            if (!$prod) {
                $num = preg_replace('/\D+/', '', (string)$code);
                if ($num !== '' && ctype_digit($num)) {
                    $prod = Product::where('active', true)->where('id', (int)$num)->first();
                }
            }
        }

        if (!$prod) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        return response()->json([
            'id'           => $prod->id,
            'code'         => $prod->code,
            'name'         => $prod->name,
            'price_cash'   => $prod->price_cash, // entero en Gs
            'stock'        => $prod->stock,
            'installments' => $prod->installments()->get(['installments','installment_price']),
        ]);
    }
}
