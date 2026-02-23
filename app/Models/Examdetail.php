<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamDetail extends Model
{
    protected $table      = 'examdetails';
    protected $primaryKey = 'EDOWNCODE';

    public $timestamps = false;

    protected $fillable = [
        'EMOWNCODE',
        'QBOWNCODE',
        'USERANSWER',
        'CORRECTANSWER',
        'CANDIDATEID',
    ];

    protected $casts = [
        'USERANSWER'    => 'integer',
        'CORRECTANSWER' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A detail row belongs to one exam session.
     */
    public function examMaster()
    {
        return $this->belongsTo(ExamMaster::class, 'EMOWNCODE', 'EMOWNCODE');
    }

    /**
     * A detail row belongs to one question.
     */
    public function question()
    {
        return $this->belongsTo(QuestionBank::class, 'QBOWNCODE', 'QBOWNCODE');
    }

    /**
     * A detail row belongs to one candidate (User).
     */
    public function candidate()
    {
        return $this->belongsTo(User::class, 'CANDIDATEID', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCorrect(): bool
    {
        return $this->USERANSWER === $this->CORRECTANSWER;
    }
}