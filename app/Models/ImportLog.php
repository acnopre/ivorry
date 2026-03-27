<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'import_log';
    protected $fillable = [
        'filename',
        'user_id',
        'disk',
        'status',
        'import_type',
        'batch_status',
        'total_rows',
        'success_rows',
        'error_rows',
        'skipped_rows',
        'duplicate_rows',
        'updated_rows',
        'error_message',
    ];

    public function items()
    {
        return $this->hasMany(ImportLogItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'partial', 'failed']);
    }
}
