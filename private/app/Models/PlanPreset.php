<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPreset extends Model
{
    protected $table = 'plan_presets';

    protected $fillable = [
        'plan',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];
}
