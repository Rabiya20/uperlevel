<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScreenCapture;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Receives captures from the hostname-mapped desktop agent (see
 * desktop-agent/agent.js) — a machine name, not a per-employee login,
 * is the whole trust boundary here, so:
 *   - a shared secret header is the only thing standing between this
 *     endpoint and anyone else on the network (see AGENT_SHARED_SECRET)
 *   - files are written to the private `local` disk, never `public` —
 *     these are screenshots of someone's screen, and the admin viewing
 *     screen (Admin\Company\ScreenCaptureController) already enforces
 *     tenant + owner/admin-only access on top of that
 */
class CaptureAgentController extends Controller
{
    private const MAX_IMAGE_BYTES = 15 * 1024 * 1024; // 15MB decoded

    public function store(Request $request): JsonResponse
    {
        $expectedSecret = config('services.capture_agent.shared_secret');
        if ($expectedSecret && ! hash_equals($expectedSecret, (string) $request->header('X-Agent-Secret'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'machine_name' => ['required', 'string', 'max:100'],
            'image' => ['required', 'string', 'max:20000000'], // ~20M chars of base64 ≈ 15MB decoded
            'captured_at' => ['nullable', 'date'],
        ]);

        $user = User::where('machine_name', $data['machine_name'])->first();

        if (! $user) {
            // No error response — an unmapped agent has no way to act on
            // one, and a distinct status would just confirm to anyone
            // probing this endpoint which machine names are registered.
            Log::warning("Screen capture agent: no employee mapped to machine \"{$data['machine_name']}\".");

            return response()->json(['status' => 'ignored']);
        }

        $binary = $this->decodeImage($data['image']);
        if (! $binary) {
            return response()->json(['message' => 'Invalid image payload'], 422);
        }

        $path = "screen-captures/{$user->tenant_id}/{$user->id}/".Str::uuid().'.jpg';
        Storage::disk('local')->put($path, $binary);

        $capture = ScreenCapture::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'file_path' => $path,
            'original_filename' => basename($path),
            'captured_at' => $data['captured_at'] ?? now(),
            'device_name' => $data['machine_name'],
            'client_ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'stored', 'id' => $capture->id], 201);
    }

    /** Accepts both a raw base64 string and a "data:image/jpeg;base64,..." URI, since capture libraries differ. */
    private function decodeImage(string $image): ?string
    {
        if (str_contains($image, ',') && str_starts_with($image, 'data:')) {
            $image = substr($image, strpos($image, ',') + 1);
        }

        $binary = base64_decode($image, true);

        if ($binary === false || strlen($binary) < 100 || strlen($binary) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        return $binary;
    }
}
