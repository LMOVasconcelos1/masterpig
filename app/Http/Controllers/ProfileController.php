<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        unset($validated['foto_perfil']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->{$user->getEmailVerifiedAtColumn()} = null;
        }

        if ($request->hasFile('foto_perfil')) {
            if (! Schema::hasColumn($user->getTable(), 'foto_perfil')) {
                return Redirect::to(route('profile.edit', [], false))->withErrors([
                    'foto_perfil' => 'O campo de foto de perfil ainda não existe no banco. Execute o SQL de ajuste e tente novamente.',
                ]);
            }

            $path = $request->file('foto_perfil')->store('profile-photos', 'public');

            if (! empty($user->foto_perfil)) {
                Storage::disk('public')->delete($user->foto_perfil);
            }

            $user->foto_perfil = $path;
        }

        $user->save();

        return Redirect::to(route('profile.edit', [], false))->with('status', 'profile-updated');
    }

    public function photo(Request $request, string $path)
    {
        $user = $request->user();
        $path = ltrim($path, '/');

        if (! Schema::hasColumn($user->getTable(), 'foto_perfil')) {
            abort(404);
        }

        if ($path === '' || $user->foto_perfil !== $path) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
