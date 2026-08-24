<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsAcceptance extends Model
{
    use HasFactory;

    protected $table = 'terms_acceptances';

    protected $fillable = [
        'id_terms_and_conditions',
        'id_user',
        'id_gimnasio',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function terms()
    {
        return $this->belongsTo(TermsAndConditions::class, 'id_terms_and_conditions');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }
}
