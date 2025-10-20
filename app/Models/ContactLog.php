<?php
// app/Models/ContactLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLog extends Model
{
    protected $fillable = [
        'client_id','channel','type','status','to_ref','template','external_id',
        'message','meta','sent_at',
    ];
    protected $casts = [
        'meta'   => 'array',
        'sent_at'=> 'datetime',
    ];
    public function client(){ return $this->belongsTo(Client::class); }
}
