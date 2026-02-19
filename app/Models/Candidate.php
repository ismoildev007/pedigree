<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = ['name', 'email', 'phone'];

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }
}
