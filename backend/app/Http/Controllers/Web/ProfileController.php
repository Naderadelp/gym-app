<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = auth()->user()->load('media');

        return Inertia::render('Profile/Edit', [
            'user'        => [
                'id'              => $user->id,
                'name'            => $user->name,
                'display_name'    => $user->display_name,
                'email'           => $user->email,
                'unit_preference' => $user->unit_preference,
                'avatar_url'      => $user->getFirstMediaUrl('avatar'),
            ],
            'bodyMetrics' => auth()->user()->bodyMetrics()
                ->latest('logged_at')
                ->get(['id', 'weight', 'height', 'body_fat_percentage', 'logged_at']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'display_name'    => ['nullable', 'string', 'max:255'],
            'unit_preference' => ['nullable', 'in:metric,imperial'],
        ]);

        auth()->user()->update($validated);

        return redirect()->route('profile.edit')->with('success', 'Profile updated.');
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);

        $user = auth()->user();
        $user->clearMediaCollection('avatar');
        $user->addMediaFromRequest('avatar')
            ->usingFileName(md5(time()) . '.' . $request->file('avatar')->extension())
            ->toMediaCollection('avatar');

        return back()->with('success', 'Avatar updated.');
    }
}
