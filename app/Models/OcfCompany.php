<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcfCompany extends Model
{
    use HasFactory;
     protected $table = 'srno_company_master';
    protected $primaryKey = 'id';
    public $timestamps = false;
}