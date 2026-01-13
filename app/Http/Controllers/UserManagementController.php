<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::withCount('examSessions')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // 各ユーザーに学年情報を追加
        $users->getCollection()->transform(function ($user) {
            $grade = $user->getCurrentGrade();
            if ($grade !== null && $grade >= 1 && $grade <= 3) {
                $user->grade = $grade . '年';
            } elseif ($grade !== null && $grade >= 4) {
                // 学年が4以上 = 卒業生
                $user->grade = $user->graduation_year . '年卒';
            } else {
                $user->grade = null;
            }
            return $user;
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::with(['examSessions'])->findOrFail($id);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index');
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);

        return redirect()->route('admin.users.index');
    }
}
