<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crawler_Info extends Model
{
    use HasFactory;
    protected $table = 'crawler_infos';

    protected $fillable = [
        'url',
        'title', 
        'description', 
        'body',
        'img_name'
    ];

}
