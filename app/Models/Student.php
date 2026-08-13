<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['name', 'grade', 'progul'];


    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }
}
