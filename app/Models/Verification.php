<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'verification_date',
        'status',
    ];

    // Relation avec le modèle Document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
