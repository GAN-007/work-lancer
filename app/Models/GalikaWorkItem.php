<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalikaWorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id','action','status','priority','attempt_count',
        'lease_owner','lease_expires_at','available_at','started_at','completed_at',
        'last_error','payload','result'
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'lease_expires_at' => 'datetime',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
