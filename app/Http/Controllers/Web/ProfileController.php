<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Image;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    /**
     * Show profile page
     */
    public function index()
    {
        return view('admin.profile.index');
    }

    /**
     * Update profile information
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Profile updated successfully'),
        ]);
    }

    /**
     * Update avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        // Delete old avatar image if exists
        if ($user->avatar) {
            $oldImage = Image::where('filename', $user->avatar)->first();
            if ($oldImage) {
                $oldImage->delete();
            } else {
                // Fallback for old storage method
                $oldPath = base_path('project_img/avatars/' . $user->avatar);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }

        // Upload new avatar using Image model
        $image = Image::upload($request->file('avatar'), 'avatars', $user);

        $user->update(['avatar' => $image->filename]);

        return response()->json([
            'success' => true,
            'message' => __('Avatar updated successfully'),
            'avatar_url' => $user->avatar_url,
        ]);
    }

    /**
     * Remove avatar
     */
    public function removeAvatar()
    {
        $user = auth()->user();

        if ($user->avatar) {
            $oldImage = Image::where('filename', $user->avatar)->first();
            if ($oldImage) {
                $oldImage->delete();
            } else {
                // Fallback for old storage method
                $oldPath = base_path('project_img/avatars/' . $user->avatar);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Avatar removed successfully'),
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('Current password is incorrect'),
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Password updated successfully'),
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('Password is incorrect'),
            ], 422);
        }

        // Don't allow Admin Tenant to delete their account if they are the only admin
        if ($user->isAdminTenant()) {
            $adminRole = \App\Models\Role::where('name', 'Admin Tenant')->first();
            $adminCount = \App\Models\User::where('role_id', $adminRole?->id)->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => __('Cannot delete the only admin account'),
                ], 422);
            }
        }

        // Delete avatar if exists
        if ($user->avatar) {
            $oldImage = Image::where('filename', $user->avatar)->first();
            if ($oldImage) {
                $oldImage->delete();
            } else {
                $oldPath = base_path('project_img/avatars/' . $user->avatar);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }

        // Logout
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => __('Account deleted successfully'),
            'redirect' => route('login'),
        ]);
    }
}
