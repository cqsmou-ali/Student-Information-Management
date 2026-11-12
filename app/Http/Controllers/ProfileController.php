<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** "View personal profile": every role can see their own account (and student record, if any). */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->role === 'student' ? $user->student()->with(['course', 'yearLevel', 'section'])->first() : null;

        return view('profile', ['user' => $user, 'student' => $student]);
    }
}
