<?php

namespace App\Models;

use App\Concerns\HasPublicationStatus;
use App\Enums\UsesCategory;
use Database\Factories\UsesItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/** Un élément de la page « Uses » : matériel, outil de développement, application ou service. */
class UsesItem extends Model
{
    /** @use HasFactory<UsesItemFactory> */
    use HasFactory, HasPublicationStatus, HasTranslations, SoftDeletes;

    /** @var array<int, string> */
    protected $translatable = ['description'];

    /** @var list<string> */
    protected $fillable = [
        'category',
        'name',
        'description',
        'url',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'category' => UsesCategory::class,
        'sort_order' => 'integer',
    ];
}
