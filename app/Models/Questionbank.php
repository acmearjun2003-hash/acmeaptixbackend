<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $table      = 'questionbank';
    protected $primaryKey = 'QBOWNCODE';

    // Legacy table has no created_at / updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'CATEGORYCODE',
        'QUESTION',
        'OPTION1',
        'OPTION2',
        'OPTION3',
        'OPTION4',
        'CORRECTANSWER',
    ];

    protected $casts = [
        'CORRECTANSWER' => 'integer',
        'CATEGORYCODE'  => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * A question appears in many exam detail rows.
     */
    public function examDetails()
    {
        return $this->hasMany(ExamDetail::class, 'QBOWNCODE', 'QBOWNCODE');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the text label for a given option number (1-4).
     */
    public function getOptionText(int $optionNumber): ?string
    {
        return $this->{"OPTION{$optionNumber}"} ?? null;
    }
}