<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename', 'format', 'total_files',
        'imported_count', 'failed_count', 'status', 'log',
    ];

    protected $casts = [
        'log' => 'array',
        'total_files' => 'integer',
        'imported_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }
}
