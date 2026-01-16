<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'import_log';
    protected $fillable = [
        'filename',
        'disk',
        'status',
        'total_rows',
        'success_rows',
        'error_rows',
        'error_message',
    ];

    public function items()
    {
        return $this->hasMany(ImportLogItem::class);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'partial', 'failed']);
    }
}
