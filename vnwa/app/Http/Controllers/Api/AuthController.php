<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Get user data with additional information.
     */
    private function getUserData($user): array
    {
        return [
            ...$user->toArray(),
            'must_verify_email' => $user instanceof MustVerifyEmail && method_exists($user, 'mustVerifyEmail') ? $user->mustVerifyEmail() : false,
            'has_password' => (bool) $user->password,
            'roles' => $user->roles()->select('name')->pluck('name'),
            'connected_providers' => $user->getConnectedProviders(),
        ];
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Login successful',
            'user' => $this->getUserData($user),
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_guest' => false,
        ]);
        $user->assignRole(Role::where('name', 'user')->first());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Registration successful',
            'user' => $this->getUserData($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Guest login - only requires name
     */
    public function guestLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate unique guest email
        $guestEmail = 'guest_'.uniqid().'_'.time().'@guest.local';

        $user = User::create([
            'name' => $request->name,
            'email' => $guestEmail,
            'is_guest' => true,
            'email_verified_at' => now(), // Guest emails are auto-verified
        ]);
        $user->assignRole(Role::where('name', 'user')->first());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Guest login successful',
            'user' => $this->getUserData($user),
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'ok' => true,
            'user' => $this->getUserData($user),
        ]);
    }

    /**
     * Sync favorites and reading history from cookies/localStorage
     */
    public function syncData(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $favorites = $request->input('favorites', []);
        $readingHistory = $request->input('reading_history', []);

        // Sync favorites
        if (is_array($favorites) && ! empty($favorites)) {
            foreach ($favorites as $mangaId) {
                if (is_numeric($mangaId)) {
                  Favorite::firstOrCreate([
                        'user_id' => $user->id,
                        'manga_id' => (int) $mangaId,
                    ]);
                }
            }
        }

        // Sync reading history
        if (is_array($readingHistory) && ! empty($readingHistory)) {
            foreach ($readingHistory as $item) {
                if (isset($item['manga_id']) && is_numeric($item['manga_id'])) {
                  ReadingHistory::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'manga_id' => (int) $item['manga_id'],
                        ],
                        [
                            'manga_chapter_id' => isset($item['chapter_id']) ? (int) $item['chapter_id'] : null,
                            'chapter_order' => $item['chapter_order'] ?? null,
                            'chapter_name' => $item['chapter_name'] ?? null,
                            'last_read_at' => isset($item['last_read_at']) ? $item['last_read_at'] : now(),
                        ]
                    );
                }
            }
        }

        return response()->json([
            'ok' => true,
            'message' => 'Data synced successfully',
        ]);
    }

    public function deviceDisconnect(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Device disconnected successfully',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|string|max:255',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('avatar')) {
            $user->avatar = $request->avatar;
        }

        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'user' => $this->getUserData($user->fresh()),
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'notify_email_new_chapter' => 'sometimes|boolean',
            'notify_email_comment_reply' => 'sometimes|boolean',
            'notify_email_recommendations' => 'sometimes|boolean',
            'notify_push_new_chapter' => 'sometimes|boolean',
            'notify_push_comment_reply' => 'sometimes|boolean',
            'privacy_public_profile' => 'sometimes|boolean',
            'privacy_show_reading_history' => 'sometimes|boolean',
            'privacy_show_favorites' => 'sometimes|boolean',
        ]);

        $user->fill($request->only([
            'notify_email_new_chapter',
            'notify_email_comment_reply',
            'notify_email_recommendations',
            'notify_push_new_chapter',
            'notify_push_comment_reply',
            'privacy_public_profile',
            'privacy_show_reading_history',
            'privacy_show_favorites',
        ]));

        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Cập nhật cài đặt thành công',
            'user' => $this->getUserData($user->fresh()),
        ]);
    }

    /**
     * Redirect to Google OAuth provider.
     */
    public function redirectToGoogle(): JsonResponse
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $redirectUrl = $driver->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'ok' => true,
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $socialUser = $driver->stateless()->user();

            $user = User::where('google_id', $socialUser->getId())
                ->orWhere('email', $socialUser->getEmail())
                ->first();

            if ($user) {
                // User exists, update Google ID if not set
                if (! $user->google_id) {
                    $user->google_id = $socialUser->getId();
                    $user->provider = 'google';
                    if (! $user->avatar && $socialUser->getAvatar()) {
                        $user->avatar = $socialUser->getAvatar();
                    }
                    $user->save();
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'google_id' => $socialUser->getId(),
                    'provider' => 'google',
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole(Role::where('name', 'user')->first());
            }

            $token = $user->createToken('api-token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect($frontendUrl.'/auth/callback?token='.urlencode($token));
        } catch (\Exception $e) {
            // Redirect to frontend with error
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect($frontendUrl.'/auth/callback?error='.urlencode($e->getMessage()));
        }
    }

    /**
     * Redirect to Discord OAuth provider.
     */
    public function redirectToDiscord(): JsonResponse
    {
        /** @var \Laravel\Socialite\Contracts\Provider $driver */
        $driver = Socialite::driver('discord');
        $redirectUrl = $driver->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'ok' => true,
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle Discord OAuth callback.
     */
    public function handleDiscordCallback(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Contracts\Provider $driver */
            $driver = Socialite::driver('discord');
            $socialUser = $driver->stateless()->user();

            $user = User::where('discord_id', $socialUser->getId())
                ->orWhere('email', $socialUser->getEmail())
                ->first();

            if ($user) {
                // User exists, update Discord ID if not set
                if (! $user->discord_id) {
                    $user->discord_id = $socialUser->getId();
                    $user->provider = $user->provider ?: 'discord';
                    if (! $user->avatar && $socialUser->getAvatar()) {
                        $user->avatar = $socialUser->getAvatar();
                    }
                    $user->save();
                }
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'discord_id' => $socialUser->getId(),
                    'provider' => 'discord',
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole(Role::where('name', 'user')->first());
            }

            $token = $user->createToken('api-token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect($frontendUrl.'/auth/callback?token='.urlencode($token));
        } catch (\Exception $e) {
            // Redirect to frontend with error
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

            return redirect($frontendUrl.'/auth/callback?error='.urlencode($e->getMessage()));
        }
    }
}
