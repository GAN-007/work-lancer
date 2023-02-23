<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    // protected $fillable = [
    //     'headline',
    //     'title',
    //     'category',
    //     'description',
    //     'skills',
    //     'hourly_pay',
    //     'project_pay',
    // ];

    public function jobemployer(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
