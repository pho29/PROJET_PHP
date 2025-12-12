<?php
require_once __DIR__ . '/../models/UserDAO.php';
require_once __DIR__ . '/../config/auth.php';

class AuthController {
    private $userDAO;

    public function __construct() {
        $this->userDAO = new UserDAO();
    }

    public function login($username, $password) {
        $user = $this->userDAO->verifyUser($username, $password);
        if ($user) {
            Auth::login($user['id'], 'entraineur'); 
            return true;
        }
        return false;
    }

    public function initializeAdmin() {
        if (!$this->userDAO->userExists('entraineur')) {
            $this->userDAO->createUser('entraineur', 'basket123');
        }
    }

    public function logout() {
        Auth::logout();
    }
}