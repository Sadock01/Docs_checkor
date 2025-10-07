<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Type extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_used'];

    protected $casts = [
        'is_used' => 'boolean', // ✅ toujours retourné comme booléen
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
