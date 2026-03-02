<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcfCustomer extends Model
{
    use HasFactory;
    protected $table = 'srno_customer_master';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
