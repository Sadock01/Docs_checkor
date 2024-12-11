<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{

    use HasFactory;

    protected $fillable = ['identifier', 'description', 'hash','type_id','user_id',];

    // Dans le modèle Document
public function type()
{
    return $this->belongsTo(Type::class);
}

public function users()
{
    return $this->belongsToMany(User::class);
}

}
