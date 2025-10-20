<?php
namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index()
    {
        $movements = InventoryMovement::with('product','user')->latest()->paginate(15);
        return view('inventory.index', compact('movements'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('inventory.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:entrada,salida',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($data['product_id']);

        // Validar stock en salidas
        if ($data['type'] === 'salida' && $product->stock < $data['quantity']) {
            return back()->withErrors(['quantity' => 'Stock insuficiente.'])->withInput();
        }

        $data['user_id'] = auth()->id();

        // Crear movimiento
        $movement = InventoryMovement::create($data);

        // Actualizar stock
        if ($data['type'] === 'entrada') {
            $product->increment('stock', $data['quantity']);
        } else {
            $product->decrement('stock', $data['quantity']);
        }

        return redirect()->route('inventory.index')
            ->with('ok', $data['type']==='entrada'
                ? '✅ Entrada registrada y stock actualizado.'
                : '✅ Salida registrada y stock actualizado.');
    }

    public function destroy(InventoryMovement $inventoryMovement)
    {
        // 🚨 Recomendado: no eliminar directamente, marcar como anulado
        $inventoryMovement->delete();
        return back()->with('ok','🗑️ Movimiento eliminado.');
    }
}
