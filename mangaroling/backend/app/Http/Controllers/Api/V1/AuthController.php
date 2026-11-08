<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function login(LoginRequest $request): JsonResponse
    {
        // Cookie-session login only works for stateful SPA requests (see SANCTUM_STATEFUL_DOMAINS).
        if (! $request->hasSession()) {
            return response()->json(['message' => 'Login requires a stateful SPA request.', 'code' => 'stateful_required'], 400);
        }

        $email = $request->validated('email');

        if (! Auth::guard('web')->attempt([
            'email' => $email,
            'password' => $request->validated('password'),
            'is_active' => true,
        ])) {
            $known = User::where('email', $email)->first();
            $this->audit->record('auth.login_failed', 'user', null, [
                'email' => Str::limit($email, 150, ''),
                'reason' => $known && ! $known->is_active ? 'inactive' : 'invalid_credentials',
            ]);

            // Same message for every failure: do not reveal which emails exist or are deactivated.
            throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
        }

        $request->session()->regenerate();   // prevents session fixation

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->record('auth.login', 'user', $user->id, [], $user);

        return (new UserResource($user))->response();
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        $this->audit->record('auth.logout', 'user', $user->id, [], $user);

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['The current password is incorrect.']]);
        }

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ])->save();

        $user->tokens()->delete();   // revoke API tokens issued before the change
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->audit->record('auth.password_changed', 'user', $user->id, [], $user);

        return (new UserResource($user->load('roles')))->response();
    }
}
