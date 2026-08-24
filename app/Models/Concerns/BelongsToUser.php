<?php

namespace App\Models\Concerns;

use App\Models\Scopes\UserOwnedScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Applies the UserOwnedScope global scope and auto-assigns user_id on create.
 *
 * Use this trait on models holding *personal* per-user data (e.g. workouts,
 * weight logs, health profile). Do NOT use it on the spec's shared tables
 * (meals, meal_plans, exercises, lab_markers, settings) — those are visible
 * to both of Centrum's two users by design.
 *
 * IMPORTANT: UserOwnedScope only filters when Auth::check() is true. Outside
 * an authenticated HTTP request (queue workers, scheduled commands, tinker,
 * artisan) the scope is a no-op and queries return ALL users' rows. Any code
 * running outside a request context must scope explicitly, e.g.:
 *   Model::withoutGlobalScope(UserOwnedScope::class)->where('user_id', $id)
 * or use the scopeForUser() helper below: Model::forUser($user)->get().
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new UserOwnedScope);

        static::creating(function ($model) {
            if (! $model->user_id && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Explicitly scope a query to a given user, bypassing UserOwnedScope's
     * reliance on the authenticated request. Safe to use outside HTTP
     * request context (queue jobs, scheduled commands, console).
     */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->withoutGlobalScope(UserOwnedScope::class)
            ->where($this->getTable().'.user_id', $user instanceof User ? $user->id : $user);
    }
}
