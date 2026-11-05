<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModerationLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AdminUserController — mengelola pengguna dan administrator.
 *
 * FITUR & KEAMANAN:
 * - Menampilkan daftar user dan administrator
 * - Hanya Super Admin yang berhak mengubah role atau mengelola administrator
 * - Admin biasa diblokir dari mengubah role atau menonaktifkan Super Admin
 * - Proteksi Anti-Lockout: Super Admin terakhir tidak dapat didemosi, dinonaktifkan, atau dihapus
 * - Setiap perubahan role / status dicatat ke dalam ModerationLog untuk audit trail
 */
class AdminUserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna dan administrator.
     */
    public function index(Request $request): View
    {
        $query = User::query()->orderBy('role')->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Ubah role pengguna (HANYA BISA DILAKUKAN OLEH SUPER ADMIN).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        // 1. Verifikasi wewenang aktor
        if (! $actor->isSuperAdmin()) {
            return back()->with('error', 'Akses ditolak. Hanya Super Admin yang berhak mengubah role pengguna.');
        }

        // 2. Validasi input role
        $validated = $request->validate([
            'role'   => ['required', 'in:user,admin,super_admin'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'role.required' => 'Role tujuan wajib dipilih.',
            'role.in'       => 'Pilihan role tidak valid.',
        ]);

        $newRole = $validated['role'];
        $oldRole = $user->role;

        if ($newRole === $oldRole) {
            return back()->with('info', 'Role pengguna tidak mengalami perubahan.');
        }

        // 3. Periksa proteksi role & anti-lockout pada model User
        if (! $user->canChangeRoleTo($actor, $newRole)) {
            return back()->with('error', 'Tindakan ditolak: Anda tidak dapat mendemosi atau mengubah role dari Super Admin terakhir.');
        }

        // 4. Update role
        $user->role = $newRole;
        $user->save();

        // 5. Catat audit trail ke ModerationLog
        ModerationLog::record(
            $actor,
            'user',
            $user->id,
            'change_role',
            $validated['reason'] ?? "Perubahan role dari '{$oldRole}' menjadi '{$newRole}'",
            [
                'target_user_id' => $user->id,
                'target_email'   => $user->email,
                'old_role'       => $oldRole,
                'new_role'       => $newRole,
            ]
        );

        return back()->with('success', "Role untuk pengguna '{$user->name}' berhasil diubah menjadi '{$newRole}'.");
    }

    /**
     * Aktifkan / Nonaktifkan status akun pengguna.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        // 1. Cek wewenang & anti-lockout
        if ($user->is_active && ! $user->canBeDeactivatedBy($actor)) {
            return back()->with('error', 'Tindakan ditolak: Anda tidak memiliki wewenang atau akun ini adalah Super Admin terakhir yang tidak boleh dinonaktifkan.');
        }

        // 2. Admin biasa tidak boleh menonaktifkan sesama admin atau super admin
        if ($user->isAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Akses ditolak: Admin biasa tidak dapat menonaktifkan sesama administrator.');
        }

        $newStatus = ! $user->is_active;
        $user->is_active = $newStatus;
        $user->save();

        $actionText = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan';

        // 3. Catat ke ModerationLog
        ModerationLog::record(
            $actor,
            'user',
            $user->id,
            'toggle_status',
            "Akun pengguna {$actionText} oleh {$actor->name}",
            [
                'target_user_id' => $user->id,
                'target_email'   => $user->email,
                'is_active'      => $newStatus,
            ]
        );

        return back()->with('success', "Akun '{$user->name}' berhasil {$actionText}.");
    }
}
