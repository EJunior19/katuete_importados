<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinancePinController extends Controller
{
    /**
     * Muestra el formulario del PIN
     */
    public function show()
    {
        return view('finance.pin'); // tu vista resources/views/finance/pin.blade.php
    }

    /**
     * Verifica el PIN ingresado
     */
    public function verify(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'string'],
        ]);

        $expected = config('finance.pin', env('FINANCE_PIN', '1234'));

        if ($request->input('pin') !== $expected) {
            return back()->withErrors(['pin' => 'PIN incorrecto']);
        }

        $request->session()->put('finance_pin_ok', true);
        $request->session()->put('finance_pin_time', time());

        return redirect()->route('finance.index')->with('success', 'Acceso concedido al panel financiero.');
    }

    /**
     * Bloquea el panel (elimina el PIN de la sesión)
     */
    public function lock(Request $request)
    {
        $request->session()->forget(['finance_pin_ok', 'finance_pin_time']);
        return redirect()->route('finance.pin')->with('info', 'Panel bloqueado. Ingresá el PIN para continuar.');
    }
}
