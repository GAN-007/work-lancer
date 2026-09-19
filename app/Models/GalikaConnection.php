<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaConnection extends Model{
    protected $guarded=[];
    protected $casts=['expires_at'=>'datetime','scopes'=>'array','metadata'=>'array'];
    protected $hidden=['access_token','refresh_token','api_key'];
}
