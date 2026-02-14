<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    protected $fillable = ['version', 'notes', 'released_at'];

    protected $casts = [
        'released_at' => 'datetime',
    ];

    public static function current(): ?string
    {
        try {
            return self::latest('released_at')->value('version');
        } catch (\Exception $e) {
            return null;
        }
    }
}
