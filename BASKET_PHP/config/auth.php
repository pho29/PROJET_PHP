<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    public static function check() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function requireAuth() {
        if (!self::check()) {
            header('Location: /PROJET_PHP/BASKET_PHP/views/login.php');
            exit();
        }
    }

    public static function login($user_id, $username = null) {
        $_SESSION['user_id'] = $user_id;
        if ($username) {
            $_SESSION['username'] = $username;
        }
    }

    public static function logout() {
        session_unset();
        session_destroy();
    }

    public static function getUsername() {
        return $_SESSION['username'] ?? 'Utilisateur';
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>