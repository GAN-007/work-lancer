<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalikaOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'canonical_key','employer','role_title','requisition_id','source_name',
        'source_url','authoritative_url','location_text','remote_policy',
        'publication_timestamp','discovered_at','verified_at','qualification_state',
        'verification_state','application_status','application_route','route_url',
        'freshness_hours','fit_score','blocker_code','blocker_detail',
        'submitted_at','confirmed_at','acknowledged_at','interview_at','offer_at',
        'last_checked_at','next_action_at'
    ];

    protected $casts = [
        'publication_timestamp' => 'datetime',
        'discovered_at' => 'datetime',
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'interview_at' => 'datetime',
        'offer_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'next_action_at' => 'datetime',
        'freshness_hours' => 'decimal:2',
        'fit_score' => 'decimal:2',
    ];

    public function answers()
    {
        return $this->hasMany(GalikaApplicationAnswer::class, 'opportunity_id');
    }

    public function deliveryEvents()
    {
        return $this->hasMany(GalikaDeliveryEvent::class, 'opportunity_id');
    }

    public function workItems()
    {
        return $this->hasMany(GalikaWorkItem::class, 'opportunity_id');
    }
}
