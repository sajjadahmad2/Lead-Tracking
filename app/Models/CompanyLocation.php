<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class CompanyLocation extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $table='company_locations';
    protected $fillable=[
        'id',
        'location_id',
        'location_name',
        'location_email',
        'company_id',
        'status',
        'leads_dem',
        'leads_dev',
        'manager_id',
        'type',
        'medicare',
        'crm_user_id',

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function crmLocation(){
        return $this->hasMany(UserLocation::class,'location_id','id');
    }

}
