<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGymSetRequest;
use App\Models\GymSet;
use App\Models\Scopes\UserOwnedScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymSetController extends Controller
{
    public function update(UpdateGymSetRequest $request, GymSet $gymSet): JsonResponse
    {
        // Workout::gymExercises()->workout() carries the BelongsToUser global
        // scope, which filters by the *currently authenticated* user. When an
        // attacker (not the owner) loads this relation, the scope silently
        // excludes the other user's workout and the relation resolves to
        // null — so the ownership check must bypass the scope to actually
        // read who owns the workout, rather than relying on it to filter.
        $workoutUserId = (int) $gymSet->gymExercise->workout()
            ->withoutGlobalScope(UserOwnedScope::class)
            ->value('user_id');

        abort_unless($workoutUserId === $request->user()->id, 404);

        $gymSet->update($request->validated());

        return response()->json(['status' => 'ok']);
    }
}
