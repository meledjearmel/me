<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Abonné à la newsletter du blog. L'inscription n'est active qu'après confirmation
 * par le lien reçu par email (double opt-in).
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'email',
        'locale',
        'ip_address',
    ];

    /** @var list<string> */
    protected $hidden = ['token'];

    /** @var array<string, string> */
    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber): void {
            $subscriber->token ??= Str::random(64);
        });
    }

    /**
     * Abonnés qui reçoivent les nouveaux articles : confirmés et toujours inscrits.
     *
     * @param  Builder<Subscriber>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public function unsubscribeUrl(): string
    {
        return rtrim((string) config('app.url'), '/')."/{$this->locale}/newsletter/{$this->token}/unsubscribe";
    }

    public function confirmUrl(): string
    {
        return rtrim((string) config('app.url'), '/')."/{$this->locale}/newsletter/{$this->token}/confirm";
    }
}
