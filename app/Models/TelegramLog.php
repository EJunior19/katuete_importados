<?php

// app/Models/TelegramLog.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TelegramLog extends Model {
  protected $fillable = ['client_id','direction','type','message','status','meta'];
  protected $casts = ['meta'=>'array'];
  public function client(){ return $this->belongsTo(Client::class); }
}
