<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationSetting extends Model
{
    use HasFactory;
    public $table="location_settings";
    protected $fillable = [
        'id',
        'state',
        'location_name',
        'priority',
        'leads',
        'location_id',
        'company_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
