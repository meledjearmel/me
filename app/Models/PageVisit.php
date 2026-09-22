<?php

namespace App\Models;

use Database\Factories\PageVisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageVisit extends Model
{
    /** @use HasFactory<PageVisitFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'visitable_type',
        'visitable_id',
        'path',
        'referrer',
        'device',
        'duration_seconds',
    ];

    /** @return MorphTo<Model, $this> */
    public function visitable(): MorphTo
    {
        return $this->morphTo();
    }
}
