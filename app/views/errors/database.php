<?php
/**
 * Database Error Page
 * 
 * Used for database connection and query errors.
 * Safe for production - no sensitive data exposure.
 */

$pageTitle = 'Database Error';
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
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
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
        
        .status-notice {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 30px;
            color: #721c24;
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
            cursor: pointer;
            border: none;
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
        
        .retry-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .retry-button {
            background: #27ae60;
            color: white;
            margin-top: 10px;
        }
        
        .retry-button:hover {
            background: #229954;
        }
        
        .timestamp {
            color: #95a5a6;
            font-size: 12px;
            margin-top: 20px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e74c3c;
            margin-right: 8px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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
        <div class="error-icon">🗄️</div>
        
        <h1>Database Error</h1>
        
        <div class="error-message">
            We're experiencing database connectivity issues. This is usually temporary 
            and our system administrators have been automatically notified.
        </div>
        
        <div class="status-notice">
            <span class="status-indicator"></span>
            <strong>Database Status:</strong> Connection unavailable<br>
            Our team is working to restore service as quickly as possible.
        </div>
        
        <div class="error-id">
            <strong>Error ID:</strong> <?= htmlspecialchars($errorId ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>
        </div>
        
        <div class="action-buttons">
            <a href="/" class="btn btn-primary">Return Home</a>
            <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
        </div>
        
        <div class="retry-section">
            <p><strong>Try again in a moment:</strong></p>
            <button onclick="location.reload()" class="btn retry-button">Retry Now</button>
            <p style="margin-top: 10px; font-size: 14px; color: #7f8c8d;">
                The issue may resolve itself automatically.
            </p>
        </div>
        
        <div class="timestamp">
            <?= date('Y-m-d H:i:s T') ?>
        </div>
    </div>
    
    <script>
        // Auto-retry after 10 seconds
        let retryCount = 0;
        const maxRetries = 3;
        
        function autoRetry() {
            if (retryCount < maxRetries) {
                retryCount++;
                console.log(`Auto-retry attempt ${retryCount}/${maxRetries}`);
                
                // Show retry notification
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed; top: 20px; right: 20px; 
                    background: #3498db; color: white; 
                    padding: 10px 20px; border-radius: 6px;
                    z-index: 1000; font-size: 14px;
                `;
                notification.textContent = `Auto-retry ${retryCount}/${maxRetries}...`;
                document.body.appendChild(notification);
                
                setTimeout(() => location.reload(), 2000);
            }
        }
        
        // Start auto-retry after 10 seconds
        setTimeout(autoRetry, 10000);
    </script>
</body>
</html>