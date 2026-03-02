<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcfMaster extends Model
{
    use HasFactory;

    protected $table = 'srno_ocf_master';
    protected $primaryKey = 'id';
    public $timestamps = false;

    /**
     * Each OCF Master has many OCF Modules
     */
    public function modules()
    {
        return $this->hasMany(OcfModule::class, 'ocfcode', 'id');
    }

    /**
     * Customer relationship (customercode → srno_customer_master.id)
     */
    public function customer()
    {
        return $this->belongsTo(OcfCustomer::class, 'customercode', 'id');
    }

    /**
     * Company relationship (companycode → srno_company_master.id)
     */
    public function company()
    {
        return $this->belongsTo(OcfCompany::class, 'companycode', 'id');
    }
}