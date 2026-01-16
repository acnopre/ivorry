<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLogItem extends Model
{
    protected $table = 'import_log_item';

    protected $fillable = [
        'import_log_id',
        'row_number',
        'raw_data',
        'status',
        'message',
    ];


    protected $casts = [
        'raw_data' => 'array',
    ];

    public function importLog()
    {
        return $this->belongsTo(ImportLog::class);
    }
}
