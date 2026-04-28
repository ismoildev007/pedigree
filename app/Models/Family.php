<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = ['name', 'created_by'];

    public function people()
    {
        return $this->hasMany(Person::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'family_user');
    }
}
