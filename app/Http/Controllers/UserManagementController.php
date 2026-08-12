<?php

namespace App\Http\Controllers;

use App\Models\ToolsUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = ToolsUser::query()->orderBy('nama')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new ToolsUser,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', 'unique:tools_user,nama'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        ToolsUser::create([
            'nama' => $data['nama'],
            'password' => $data['password'],
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(ToolsUser $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ToolsUser $user)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('tools_user', 'nama')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:4'],
        ]);

        $user->nama = $data['nama'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()->route('users.index')->with('success', 'User berhasil diubah.');
    }

    public function destroy(ToolsUser $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus user yang sedang login.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
