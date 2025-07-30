<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{

    use HasFactory;

    protected $fillable = ['identifier','beneficiaire', 'description', 'hash', 'date_information','informations_complementaires','type_id','user_id',];

    // Dans le modèle Document
    public function histories()
    {
        return $this->hasMany(DocumentHistory::class);
    }
public function type()
{
    return $this->belongsTo(Type::class);
}

public function users()
{
    return $this->belongsToMany(User::class);
}

}
