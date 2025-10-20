<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientDocumentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        // Validación: múltiple y tipos permitidos
        $request->validate([
            'type'     => ['required','string','max:50'],
            'files'    => ['required','array'],
            'files.*'  => ['file','max:10240', 'mimes:pdf,doc,docx,odt,jpg,jpeg,png,webp'], // 10MB
        ], [
            'files.required'   => 'Seleccioná al menos un archivo.',
            'files.*.mimes'    => 'Formato no permitido. Permitidos: PDF, DOC, DOCX, ODT, JPG, PNG, WEBP.',
            'files.*.max'      => 'Cada archivo no debe superar los 10MB.',
        ]);

        foreach ($request->file('files') as $file) {
            $safeBase   = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBase   = Str::slug($safeBase, '_');
            $extension  = strtolower($file->getClientOriginalExtension());
            $filename   = now()->format('Ymd_His') . '_' . Str::random(6) . '_' . $safeBase . '.' . $extension;

            // Guarda en storage/app/public/client_docs/{client_id}/...
            $path = $file->storeAs("client_docs/{$client->id}", $filename, 'public');

            ClientDocument::create([
                'client_id' => $client->id,
                'type'      => $request->string('type'),
                'file_path' => $path,
            ]);
        }

        return back()->with('success', 'Documentos subidos correctamente.');
    }

    public function destroy(Client $client, ClientDocument $doc)
    {
        // Seguridad: el doc debe pertenecer al cliente
        abort_unless($doc->client_id === $client->id, 403);

        Storage::disk('public')->delete($doc->file_path);
        $doc->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
