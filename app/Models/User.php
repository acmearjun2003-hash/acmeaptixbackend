<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends \TCG\Voyager\Models\User
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'mobileno',
        'avatar',
        'password',
        'settings',
        'highestquali',
        'ssc',
        'hsc',
        'diploma',
        'degree',
        'masterdegree',
        'aptiscore',
        'examstarted',
        'aptidate',
        'aptitime',
        'techroundpercent',
        'interviewpercent',
        'referenceby',
        'document',
        'post',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'techroundpercent'  => 'decimal:2',
        'interviewpercent'  => 'decimal:2',
        'aptiscore'         => 'integer',
        'examstarted'       => 'integer',
         'password'          => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A user belongs to one role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * A user (candidate) applies for one post.
     */
    public function Post()
    {
        return $this->belongsTo(Post::class, 'post', 'post_id');
    }

    /**
     * A candidate has many exam sessions.
     */
    public function exams()
    {
        return $this->hasMany(ExamMaster::class, 'CANDIDATEID', 'id');
    }

    /**
     * A candidate has many exam detail records (direct shortcut).
     */
    public function examDetails()
    {
        return $this->hasMany(ExamDetail::class, 'CANDIDATEID', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isHRAdmin(): bool
    {
        return $this->role?->name === 'hradmin';
    }

    public function isCandidate(): bool
    {
        return $this->role?->name === 'candidate';
    }
}