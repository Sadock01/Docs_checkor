<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verification extends Model
{
    use HasFactory;

  // App\Models\DocumentVerification.php

protected $fillable = [
    'ip_address',
    'user_agent',
    'browser',
    'device_type',
    'platform',
    'via_file',
    'entered_data',
    'extracted_data',
    'document_found',
    'is_matching',
    'mismatches',
];

 protected $casts = [
        'via_file'      => 'boolean',
        'document_found'=> 'boolean',
        'is_matching'   => 'boolean',
        'entered_data'  => 'array',     
        'extracted_data'=> 'array',     
        'mismatches'    => 'array',
    ];
    // Relation avec le modèle Document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
