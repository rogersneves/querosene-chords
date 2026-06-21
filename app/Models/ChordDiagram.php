<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChordDiagram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'strings_pattern', 'fingering', 'fingers', 'barre',
    ];

    protected $casts = [
        'fingering' => 'array',
        'fingers' => 'array',
        'barre' => 'integer',
    ];
}
