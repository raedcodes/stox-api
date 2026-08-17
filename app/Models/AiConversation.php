<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'messages',
        'history',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'history' => 'array',
        ];
    }

    public function user(): MorphTo
    {
        return $this->morphTo(name: 'user', type: 'user_type', id: 'user_id');
    }

    /**
     * The given user's single ongoing "Ask Stox" thread, created empty on
     * first use. See the migration for why the actor is polymorphic.
     */
    public static function forUser(Authenticatable $user): self
    {
        return static::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'user_type' => $user->getMorphClass()],
            ['messages' => [], 'history' => []],
        );
    }
}
