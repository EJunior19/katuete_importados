<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientReference;
use Illuminate\Http\Request;
use App\Events\ClientReferenceCreated;

class ClientReferenceController extends Controller
{
    /**
     * Guarda una nueva referencia (cliente o contacto libre)
     */
    public function store(Request $request, Client $client)
    {
        // ─────────────────────────────────────────────────────────────
        // MODO A: referencia a un CLIENTE existente
        // ─────────────────────────────────────────────────────────────
        if ($request->filled('referenced_client_id')) {
            $data = $request->validate([
                'referenced_client_id' => ['required', 'exists:clients,id'],
                'relationship'         => ['nullable', 'string', 'max:100'],
                'note'                 => ['nullable', 'string', 'max:255'],
            ]);

            // Evitar auto-referenciarse
            abort_if((int) $data['referenced_client_id'] === (int) $client->id, 422, 'No puede referenciarse a sí mismo.');

            $ref = ClientReference::firstOrCreate(
                [
                    'client_id'            => $client->id,
                    'referenced_client_id' => (int) $data['referenced_client_id'],
                ],
                [
                    'client_id'            => $client->id,
                    'referenced_client_id' => (int) $data['referenced_client_id'],
                    'name'                 => null,
                    'phone'                => null,
                    'email'                => null,
                    'address'              => null,
                    'relationship'         => $data['relationship'] ?? null,
                    'note'                 => $data['note'] ?? null,
                ],
            );

            // Disparar automatización de onboarding
            event(new ClientReferenceCreated($ref));

            return back()->with('success', 'Referencia (cliente) agregada correctamente.');
        }

        // ─────────────────────────────────────────────────────────────
        // MODO B: contacto LIBRE (no es cliente)
        // ─────────────────────────────────────────────────────────────
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:50'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:255'],
            'note'         => ['nullable', 'string', 'max:255'],
            'telegram'     => ['nullable', 'string', 'max:64'], // ej: @usuario
        ]);

        // Normalizaciones suaves
        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? '')); // solo dígitos
        $tg = ltrim((string) ($data['telegram'] ?? ''), '@');                 // sin '@'

        $ref = ClientReference::create([
            'client_id'            => $client->id,
            'referenced_client_id' => null,
            'name'                 => $data['name'],
            'relationship'         => $data['relationship'] ?? null,
            'phone'                => $phone ?: null,
            'email'                => $data['email'] ?? null,
            'address'              => $data['address'] ?? null,
            'note'                 => $data['note'] ?? null,
            'telegram'             => $tg ?: null,
            'notify_opt_in'        => true,
            'notify_channels'      => $tg
                ? ['telegram', 'whatsapp', 'email']
                : ['whatsapp', 'email'],
        ]);

        // Disparar automatización de vinculación (deep-link, etc.)
        event(new ClientReferenceCreated($ref));

        return back()->with('success', 'Referencia agregada correctamente.');
    }

    /**
     * Elimina una referencia de un cliente
     */
    public function destroy(Client $client, ClientReference $reference)
    {
        abort_unless($reference->client_id === $client->id, 403);
        $reference->delete();

        return back()->with('success', 'Referencia eliminada correctamente.');
    }
}
