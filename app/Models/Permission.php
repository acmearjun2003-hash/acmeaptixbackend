<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;


    protected $table = "permission_role";


    public function role()
    {
        return $this->belongsToMany(\TCG\Voyager\Models\Role::class, 'role_id');
    }
}
