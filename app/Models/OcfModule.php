<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcfModule extends Model
{
    use HasFactory;

    protected $table = 'srno_ocf_modules';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function module()
    {
        return $this->belongsTo(AcmeModule::class, 'modulecode', 'id');
    }

    public function moduleType()
    {
        return $this->belongsTo(AcmeModuleType::class, 'moduletypes', 'id');
                                                      
    }

    public function master()
{
    return $this->belongsTo(OcfMaster::class, 'ocfcode', 'id');
}

}
