<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = ['title', 'is_active'];

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
