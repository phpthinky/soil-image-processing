<?php
class Security {
    
    public static function isLoginBlocked($ip = null) {
        if (!SECURITY_ENABLED) return false;
        
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'login_block_' . md5($ip);
        
        if (isset($_SESSION[$key])) {
            $block_time = $_SESSION[$key];
            if (time() - $block_time < LOGIN_BLOCK_DURATION) {
                return true;
            } else {
                unset($_SESSION[$key]);
            }
        }
        
        return false;
    }
    
    public static function incrementLoginAttempts($ip = null) {
        if (!SECURITY_ENABLED) return 0;
        
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . md5($ip);
        
        $attempts = $_SESSION[$key] ?? 0;
        $attempts++;
        $_SESSION[$key] = $attempts;
        
        if ($attempts >= LOGIN_BLOCK_AFTER) {
            self::blockLogin($ip);
        }
        
        return $attempts;
    }
    
    public static function getLoginAttempts($ip = null) {
        if (!SECURITY_ENABLED) return 0;
        
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . md5($ip);
        
        return $_SESSION[$key] ?? 0;
    }
    
    public static function blockLogin($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'login_block_' . md5($ip);
        $_SESSION[$key] = time();
    }
    
    public static function clearLoginAttempts($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . md5($ip);
        unset($_SESSION[$key]);
    }
    
    public static function shouldShowCaptcha($ip = null) {
        if (!SECURITY_ENABLED || !RECAPTCHA_ENABLED) return false;
        
        $attempts = self::getLoginAttempts($ip);
        return $attempts >= LOGIN_CAPTCHA_AFTER;
    }
    
    public static function resetOnSuccess($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        self::clearLoginAttempts($ip);
        $key = 'login_block_' . md5($ip);
        unset($_SESSION[$key]);
    }
}
?>