<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalikaDeliveryEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id','event_type','route','recipient','external_message_id',
        'evidence','occurred_at','terminal','supersedes_event_id'
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'terminal' => 'boolean',
    ];
}
