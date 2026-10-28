<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isTeacher()
                ? redirect()->route('teachers.dashboard')
                : redirect()->route('students.dashboard');
        }

        return Inertia::render('Signup', [
            'initialRole' => $request->query('role', 'student'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => $request->validated('role'),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        if ($user->isTeacher()) {
            return redirect()->intended(route('teachers.dashboard'));
        }

        return redirect()->intended(route('students.dashboard'));
    }
}
