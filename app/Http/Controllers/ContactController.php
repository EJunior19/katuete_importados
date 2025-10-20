<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // GET /clients/{client}/contacts  (si querés usarlo)
    public function index(Client $client)
    {
        // Podés listar o simplemente redirigir al show del cliente:
        return redirect()->route('clients.show', $client);
    }

    // GET /clients/{client}/contacts/create
    public function create(Client $client)
    {
        return view('contacts.create', compact('client'));
    }

    // POST /clients/{client}/contacts
    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
            'active'   => 'nullable|boolean',
        ]);

        // crea y setea client_id automáticamente
        $client->contacts()->create($data);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Contacto creado exitosamente.');
    }

    // GET /contacts/{contact}
    public function show(Contact $contact)
    {
        // opcional: podrías redirigir al cliente
        return redirect()->route('clients.show', $contact->client);
    }

    // GET /contacts/{contact}/edit
    public function edit(Contact $contact)
    {
        $client = $contact->client;
        return view('contacts.edit', compact('client', 'contact'));
    }

    // PUT/PATCH /contacts/{contact}
    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'notes'    => 'nullable|string',
            'active'   => 'nullable|boolean',
        ]);

        $contact->update($data);

        return redirect()
            ->route('clients.show', $contact->client)
            ->with('success', 'Contacto actualizado exitosamente.');
    }

    // DELETE /contacts/{contact}
    public function destroy(Contact $contact)
    {
        $client = $contact->client;
        $contact->delete();

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Contacto eliminado exitosamente.');
    }
}
