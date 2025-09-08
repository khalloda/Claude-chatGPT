<?php
/**
 * 403 Forbidden Error Page
 * 
 * Used for access denied / security errors.
 * Safe for production - no sensitive data exposure.
 */

$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/assets/images/favicon.ico">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #ff7f7f 0%, #ff4757 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .error-icon {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 20px;
            display: block;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 16px;
        }
        
        .error-message {
            font-size: 16px;
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 30px;
            color: #856404;
            font-size: 14px;
        }
        
        .error-id {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 30px;
            word-break: break-all;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-1px);
        }
        
        .login-link {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .timestamp {
            color: #95a5a6;
            font-size: 12px;
            margin-top: 20px;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 40px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .error-icon {
                font-size: 48px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        
        <h1>Access Denied</h1>
        
        <div class="error-message">
            You don't have permission to access this resource. This could be due to:
        </div>
        
        <div class="security-notice">
            <strong>Common reasons:</strong><br>
            • You're not logged in<br>
            • You don't have the required permissions<br>
            • Your session has expired<br>
            • The resource requires authentication
        </div>
        
        <div class="error-id">
            <strong>Error ID:</strong> <?= htmlspecialchars($errorId ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>
        </div>
        
        <div class="action-buttons">
            <a href="/" class="btn btn-primary">Return Home</a>
            <a href="/login" class="btn btn-secondary">Login</a>
        </div>
        
        <div class="login-link">
            <p>If you believe this is an error, please <a href="/login">sign in</a> or contact your administrator.</p>
        </div>
        
        <div class="timestamp">
            <?= date('Y-m-d H:i:s T') ?>
        </div>
    </div>
</body>
</html>