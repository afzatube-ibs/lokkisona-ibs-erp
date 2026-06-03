<?php

namespace App\Controllers;

use App\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        Auth::guestOnly();
        view('auth.login', [
            'pageTitle' => 'Sign In',
            'error' => $_SESSION['login_error'] ?? null,
        ]);
        unset($_SESSION['login_error']);
    }

    public function login()
    {
        Auth::guestOnly();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Please enter your username and password.';
            redirect('/login');
        }

        if (!Auth::attempt($username, $password)) {
            $_SESSION['login_error'] = 'Invalid credentials. Please try again.';
            redirect('/login');
        }

        redirect('/dashboard');
    }

    public function logout()
    {
        Auth::logout();
        redirect('/login');
    }
}
