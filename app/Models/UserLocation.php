<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class UserLocation extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $table='user_locations';
    public $timestamps = false;

    protected $fillable=[
        'id',
        'location_id',
        'company_id',
        'user_id',

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
