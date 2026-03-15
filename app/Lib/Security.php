<?php
class Security {
    public static function ensureSession(){
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
    public static function csrfToken(){
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])){
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function validateCsrf($token){
        self::ensureSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid){
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate
        }
        return $valid;
    }
    public static function csrfField(){
        return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(self::csrfToken()).'">';
    }
}