<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class CrmUser extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $table='crm_users';
    protected $fillable=[
        'id',
        'user_id',
        'name',
        'email',
        'role',
        'company_id',


    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
