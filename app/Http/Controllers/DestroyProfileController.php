<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Models\LearnerProfile;
use App\Services\AccountDeletionService;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class DestroyProfileController extends Controller
{
    public function __invoke(DeleteAccountRequest $request, AccountDeletionService $deletion, AuthService $auth): RedirectResponse
    {
        $identity = $request->user() ?? LearnerProfile::completedFor(null, $request->cookie('artaswaha_device'));
        abort_if($identity === null, 403);
        $filesDeleted = $deletion->delete($identity, $request->session()->getId());
        $auth->logout($request);
        Inertia::clearHistory();

        return to_route('home')->with('status', $filesDeleted
            ? 'Akun dan data terkait berhasil dihapus.'
            : 'Akun dihapus. Sebagian file belum dapat dibersihkan. Hubungi kerthajayasoftware@gmail.com untuk tindak lanjut.')
            ->with('status_tone', $filesDeleted ? 'success' : 'info')
            ->withCookie(Cookie::forget('artaswaha_device'));
    }
}
