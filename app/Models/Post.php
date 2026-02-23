<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table      = 'post_master';
    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_name',
        'department',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A post can be applied to by many users (candidates).
     */
    public function users()
    {
        return $this->hasMany(User::class, 'post', 'post_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get only active posts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}