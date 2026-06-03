<?php

namespace App;

class Auth
{
    public static function startSession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = config('app.session_name', 'lokkisona_ibs_erp_session');
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function check()
    {
        self::startSession();
        return !empty($_SESSION['ibs_authenticated']);
    }

    public static function user()
    {
        self::startSession();
        return $_SESSION['ibs_user'] ?? null;
    }

    public static function attempt($username, $password)
    {
        $validUser = config('app.auth.username', 'admin');
        $validPass = config('app.auth.password', 'admin');

        if ($username !== $validUser || $password !== $validPass) {
            app_log('Failed login attempt for user: ' . $username, 'warning');
            ActivityLog::record('failed_login', 'Failed login attempt', [
                'user' => $username,
                'role' => 'guest',
            ]);
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION['ibs_authenticated'] = true;
        $_SESSION['ibs_user'] = $username;
        $_SESSION['ibs_login_at'] = time();

        app_log('User logged in: ' . $username);
        ActivityLog::record('login', 'User logged in', [
            'user' => $username,
            'role' => 'admin',
        ]);
        return true;
    }

    public static function logout()
    {
        self::startSession();
        $user = $_SESSION['ibs_user'] ?? 'unknown';
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        app_log('User logged out: ' . $user);
        ActivityLog::record('logout', 'User logged out', [
            'user' => $user,
            'role' => 'admin',
        ]);
    }

    public static function requireAuth()
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function guestOnly()
    {
        if (self::check()) {
            redirect('/dashboard');
        }
    }
}
