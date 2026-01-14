<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        // ソートパラメータの取得
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // 許可されたソートフィールドのバリデーション
        $allowedSortFields = ['id', 'name', 'email', 'created_at', 'exam_sessions_count', 'grade'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // ソート方向のバリデーション
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query = User::withCount('examSessions');

        // 受験回数でソートする場合
        if ($sortField === 'exam_sessions_count') {
            $query->orderBy('exam_sessions_count', $sortDirection);
        }
        // 学年でソートする場合（graduation_yearを使用）
        elseif ($sortField === 'grade') {
            $query->orderBy('graduation_year', $sortDirection);
        }
        // その他のフィールドでソート
        else {
            $query->orderBy($sortField, $sortDirection);
        }

        $users = $query->paginate(20)->withQueryString();

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
            'sort' => $sortField,
            'direction' => $sortDirection,
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
