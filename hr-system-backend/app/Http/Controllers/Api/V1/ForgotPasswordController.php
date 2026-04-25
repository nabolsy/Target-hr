<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Send password reset link via email.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            // Don't reveal whether email exists
            return response()->json([
                'message' => 'If an account exists with that email, a reset link has been sent.',
            ]);
        }

        // Generate token
        $token = Str::random(64);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Store token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Build reset URL
        $resetUrl = config('app.frontend_url', 'http://localhost:3000') . "/reset-password?token={$token}&email=" . urlencode($request->email);

        // Send email
        try {
            Mail::send([], [], function ($message) use ($user, $resetUrl) {
                $message->to($user->email, $user->name)
                    ->subject('Reset Your Password — Target HR')
                    ->html($this->resetEmailHtml($user->name, $resetUrl));
            });
        } catch (\Exception $e) {
            // Log but don't expose error
            \Log::error('Password reset email failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'If an account exists with that email, a reset link has been sent.',
        ]);
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Reset token has expired. Please request a new one.'], 422);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Password has been reset successfully. You can now log in.']);
    }

    private function resetEmailHtml(string $name, string $resetUrl): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="margin:0;padding:0;background:#0f172a;font-family:system-ui,sans-serif;">
          <div style="max-width:500px;margin:40px auto;background:#1e293b;border-radius:16px;overflow:hidden;">
            <div style="background:#6366f1;padding:24px 32px;text-align:center;">
              <h1 style="color:#fff;margin:0;font-size:22px;">Target HR</h1>
            </div>
            <div style="padding:32px;">
              <p style="color:#e2e8f0;font-size:16px;margin:0 0 16px;">Hi {$name},</p>
              <p style="color:#94a3b8;font-size:14px;line-height:1.7;margin:0 0 24px;">
                You requested a password reset for your Target account. Click the button below to create a new password.
              </p>
              <div style="text-align:center;margin:24px 0;">
                <a href="{$resetUrl}" style="background:#6366f1;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:600;font-size:15px;display:inline-block;">
                  Reset Password
                </a>
              </div>
              <p style="color:#64748b;font-size:12px;line-height:1.6;margin:16px 0 0;">
                This link will expire in 60 minutes. If you didn't request this reset, you can safely ignore this email.
              </p>
            </div>
            <div style="padding:16px 32px;border-top:1px solid #334155;text-align:center;">
              <p style="color:#475569;font-size:11px;margin:0;">© 2026 Target HR. All rights reserved.</p>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}
