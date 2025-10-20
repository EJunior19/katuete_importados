<?php
// app/Events/ClientReferenceCreated.php
namespace App\Events;

use App\Models\ClientReference;
use Illuminate\Foundation\Events\Dispatchable;

class ClientReferenceCreated
{
    use Dispatchable;

    /** Referencia recién creada */
    public ClientReference $reference;

    public function __construct(ClientReference $reference)
    {
        $this->reference = $reference;
    }
}
