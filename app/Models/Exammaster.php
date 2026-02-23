<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMaster extends Model
{
    protected $table      = 'exammaster';
    protected $primaryKey = 'EMOWNCODE';

    public $timestamps = false;

    protected $fillable = [
        'CANDIDATEID',
        'IPADDRESS',
        'EXAMDATE',
        'EXAMTIME',
        'EXAMSCORE',
        'COMPLETEDSTATUS',
        'TIMEELAPSED',
    ];

    protected $casts = [
        'COMPLETEDSTATUS' => 'boolean',
        'EXAMSCORE'       => 'integer',
        'TIMEELAPSED'     => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * An exam session belongs to one candidate (User).
     */
    public function candidate()
    {
        return $this->belongsTo(User::class, 'CANDIDATEID', 'id');
    }

    /**
     * An exam session has many detail/answer rows.
     */
    public function examDetails()
    {
        return $this->hasMany(ExamDetail::class, 'EMOWNCODE', 'EMOWNCODE');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where('COMPLETEDSTATUS', 1);
    }

    public function scopePending($query)
    {
        return $query->where('COMPLETEDSTATUS', 0);
    }
}