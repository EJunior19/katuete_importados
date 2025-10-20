<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ClientDocument extends Model
{
    protected $fillable = ['client_id','type','file_path'];

    // Para que aparezcan al hacer ->toArray() (opcional)
    protected $appends = [
        'url', 'ext', 'display_name', 'size_kb', 'mime', 'is_image'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** URL pública (requiere: php artisan storage:link) */
    public function getUrlAttribute(): string
    {
        // Asumo disco 'public' → storage/app/public
        return asset('storage/'.$this->file_path);
    }

    /** Extensión en minúsculas */
    public function getExtAttribute(): string
    {
        return strtolower(pathinfo($this->file_path ?? '', PATHINFO_EXTENSION));
    }

    /** Nombre a mostrar (basename del path) */
    public function getDisplayNameAttribute(): string
    {
        return basename($this->file_path ?? '') ?: '—';
    }

    /** Tamaño en KB (si el archivo está en el disco 'public') */
    public function getSizeKbAttribute(): ?string
    {
        try {
            if (!$this->file_path) return null;
            $bytes = Storage::disk('public')->size($this->file_path);
            return number_format($bytes/1024, 1).' KB';
        } catch (\Throwable $e) {
            return null; // si no existe o no se puede leer
        }
    }

    /** MimeType (si el archivo existe) */
    public function getMimeAttribute(): ?string
    {
        try {
            if (!$this->file_path) return null;
            return Storage::disk('public')->mimeType($this->file_path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** ¿Es imagen? */
    public function getIsImageAttribute(): bool
    {
        $mime = $this->mime;
        return is_string($mime) && Str::contains($mime, 'image');
    }
}
