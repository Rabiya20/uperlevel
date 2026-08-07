<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScreenCapture;
use App\Models\ScreenCaptureSettings;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Consumed by the (separate, not-yet-built) desktop capture agent — never
 * by the browser. Every action here derives the tenant/user strictly from
 * the authenticated Sanctum token, never from anything the client sends,
 * so one agent can never read or write another employee's data.
 */
class AgentController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password) || ! $user->tenant_id) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $token = $user->createToken('screen-agent')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function config(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = ScreenCaptureSettings::forTenant($user->tenant);

        return response()->json([
            'enabled' => $settings->enabled,
            'interval_mode' => $settings->interval_mode,
            'interval_minutes' => $settings->interval_minutes,
            'random_min_minutes' => $settings->random_min_minutes,
            'random_max_minutes' => $settings->random_max_minutes,
        ]);
    }

    public function storeScreenshot(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'max:8192'],
            'captured_at' => ['required', 'date'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('image')->store("screen-captures/{$user->tenant_id}/{$user->id}", 'local');

        $capture = ScreenCapture::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'file_path' => $path,
            'original_filename' => $request->file('image')->getClientOriginalName(),
            'captured_at' => $data['captured_at'],
            'device_name' => $data['device_name'] ?? null,
        ]);

        return response()->json(['id' => $capture->id], 201);
    }
}
