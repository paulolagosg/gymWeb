<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'user_id',
        'survey_id',
        'training_time',
        'nps_score',
        'nps_reason',
        'servqual_ratings',
        'open_answers'
    ];

    protected $casts = [
        'servqual_ratings' => 'array',
        'open_answers' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
