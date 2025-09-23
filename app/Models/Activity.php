<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends SpatieActivity
{
    protected $table = 'activity_log';

    public function causer(): MorphTo
    {
        return parent::causer();
    }
}
