<?php

namespace Formwork\Admin\Utils;

use Formwork\Core\Formwork;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Cookie;

class Session
{
    /**
     * Start a new session
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', true);
            $options = [
                'expires'  => 0,
                'path'     => HTTPRequest::root(),
                'secure'   => HTTPRequest::isHTTPS(),
                'httponly' => true,
                'samesite' => Cookie::SAMESITE_STRICT
            ];
            if (($timeout = Formwork::instance()->option('admin.session_timeout')) > 0) {
                $options['expires'] = time() + $timeout * 60;
            }
            session_name('formwork_session');
            session_start();
            if (!isset($_COOKIE[session_name()]) || $options['expires'] > 0) {
                // Send session cookie if not already sent or timeout is set
                Cookie::send(session_name(), session_id(), $options, true);
            } elseif ($_COOKIE[session_name()] !== session_id()) {
                // Remove cookie if session id is not valid
                unset($_COOKIE[session_name()]);
                Cookie::send(session_name(), '', ['expires' => time() - 3600] + $options, true);
            }
        }
    }

    /**
     * Set a session key to value
     */
    public static function set(string $key, $value): void
    {
        static::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session key
     */
    public static function get(string $key)
    {
        static::start();
        if (static::has($key)) {
            return $_SESSION[$key];
        }
    }

    /**
     * Return whether a key is in session data
     */
    public static function has(string $key): bool
    {
        static::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session data by key
     */
    public static function remove(string $key): void
    {
        static::start();
        if (static::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * End a session and destroy all data
     */
    public static function destroy(): void
    {
        session_destroy();
    }
}
