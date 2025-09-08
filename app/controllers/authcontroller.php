<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use function App\Core\verify_csrf_request;
use function App\Core\csrf_token;
use App\Core\Logger;

final class AuthController extends Controller
{
    public function loginform(): void
    {
        // just show the form
        $this->view('auth/login', []);
    }

    public function login(): void
    {
        if (!verify_csrf_request()) {
            http_response_code(419);
            $this->view('auth/login', ['error' => 'Invalid session token. Please try again.']);
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        if ($email === '' || $pass === '') {
            $this->view('auth/login', ['error' => 'Email and password are required.']);
            return;
        }

        try {
            $startTime = microtime(true);
            $stmt = DB::conn()->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Log database performance
            Logger::database(
                'SELECT user for authentication', 
                microtime(true) - $startTime,
                ['email' => $email]
            );

            if (!$user || !password_verify($pass, $user['password_hash'])) {
                Logger::authentication('Login failed', [
                    'email' => $email, 
                    'reason' => !$user ? 'user_not_found' : 'invalid_password',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                $this->view('auth/login', ['error' => 'Invalid credentials.']);
                return;
            }

            // Successful login
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            
            Logger::authentication('Login successful', [
                'user_id' => (int)$user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            header('Location: /');
            exit;
        } catch (\PDOException $e) {
            // Handle database-specific errors
            Logger::error('Database error during login', [
                'email' => $email,
                'pdo_error' => $e->getMessage(),
                'pdo_code' => $e->getCode()
            ]);
            
            $this->view('auth/login', ['error' => 'Database connection error. Please try again.']);
        } catch (\Throwable $e) {
            // Handle all other errors
            Logger::critical('Unexpected error during login', [
                'email' => $email,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $this->view('auth/login', ['error' => 'Server error, please try again.']);
        }
    }

    public function logout(): void
    {
        if (!verify_csrf_request()) {
            http_response_code(419);
            $this->view('auth/login', ['error' => 'Invalid session token.']);
            return;
        }
        $uid = $_SESSION['user']['id'] ?? null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
        if ($uid) { Logger::info('Logout', ['user_id' => $uid]); }
        header('Location: /login');
        exit;
    }
}
