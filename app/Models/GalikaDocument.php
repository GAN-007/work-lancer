<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaDocument extends Model{
    protected $guarded=[];
    protected $casts=['extracted_facts'=>'array','confirmed'=>'boolean'];
}
