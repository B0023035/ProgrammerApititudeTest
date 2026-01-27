<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AdminManagementController extends Controller
{
    /**
     * Display a listing of admins.
     */
    public function index(): Response
    {
        $admins = Admin::select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Admins/Index', [
            'admins' => $admins,
        ]);
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Admins/Create');
    }

    /**
     * Store a newly created admin in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.admins.index')
            ->with('success', '管理者アカウントを作成しました。');
    }

    /**
     * Remove the specified admin from storage.
     */
    public function destroy(Admin $admin)
    {
        // 自分自身は削除できないようにする
        if ($admin->id === auth()->guard('admin')->id()) {
            return back()->withErrors(['error' => '自分自身のアカウントは削除できません。']);
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')
            ->with('success', '管理者アカウントを削除しました。');
    }
}
