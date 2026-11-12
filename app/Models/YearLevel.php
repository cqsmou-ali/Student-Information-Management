<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearLevel extends Model
{
    public $timestamps = false;

    protected $table = 'year_levels';

    protected $guarded = ['id'];
}
