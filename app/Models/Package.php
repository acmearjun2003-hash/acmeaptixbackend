<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory; // Laravel 8: factory support via trait (not base Model)

    protected $table = 'srno_acme_package'; // update to your actual package table name

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = false; 

    protected $fillable = [
        'packagename',
        'description',
        'active',
        'project_id',
    ];

    protected $casts = [
        'active'     => 'integer',
        'project_id' => 'integer',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function customers()
    {
        return $this->hasMany(
            CustomerMaster::class, // related model
            'packagecode',         // foreign key on customers table
            'id'                   // local key on packages table
        );
    }
}

