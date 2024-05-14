<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    public $table="contacts";
    protected $fillable = [
        'id',
        'state',
        'location_id',
        'company_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
