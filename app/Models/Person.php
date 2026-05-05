<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Person extends Model
{
    protected $fillable = [
        'family_id',
        'parent_id',
        'first_name',
        'last_name',
        'gender',
        'birth_year',
        'death_year',
        'photo',
        'description',
        'workspace_x',
        'workspace_y',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function parent()
    {
        return $this->belongsTo(Person::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Person::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with(['childrenRecursive', 'spouses']);
    }

    public function spouses()
    {
        return $this->belongsToMany(Person::class, 'marriages', 'person_id', 'spouse_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($person) {
            // Delete children recursively
            $person->children()->each(function ($child) {
                $child->delete();
            });
            
            // Delete photo if exists
            if ($person->photo) {
                Storage::disk('public')->delete($person->photo);
            }
        });
    }
}
