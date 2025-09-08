<?php
/**
 * Generic 500 Error Page
 * 
 * Used for internal server errors and exceptions.
 * Safe for production - no sensitive data exposure.
 */

$pageTitle = 'Internal Server Error';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .details-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            display: none;
        }
        
        .details-section.show {
            display: block;
        }
        
        .details-toggle {
            background: none;
            border: none;
            color: #3498db;
            font-size: 14px;
            cursor: pointer;
            text-decoration: underline;
            margin-top: 20px;
        }
        
        .stack-trace {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            text-align: left;
            overflow-x: auto;
            white-space: pre-wrap;
            margin-top: 15px;
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
        <div class="error-icon">⚠️</div>
        
        <h1>Something went wrong</h1>
        
        <div class="error-message">
            We're sorry, but an unexpected error occurred while processing your request. 
            Our team has been notified and is working to fix the issue.
        </div>
        
        <div class="error-id">
            <strong>Error ID:</strong> <?= htmlspecialchars($errorId ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>
        </div>
        
        <div class="action-buttons">
            <a href="/" class="btn btn-primary">Return Home</a>
            <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
        </div>
        
        <?php if (isset($showDetails) && $showDetails): ?>
        <button class="details-toggle" onclick="toggleDetails()">Show Technical Details</button>
        
        <div class="details-section" id="details">
            <h3>Technical Details</h3>
            <div class="stack-trace">
Error Type: <?= htmlspecialchars($errorType ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>

Message: <?= htmlspecialchars($errorMessage ?? 'No details available', ENT_QUOTES, 'UTF-8') ?>

<?php if (isset($exception) && $exception instanceof \Throwable): ?>
File: <?= htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') ?>

Line: <?= $exception->getLine() ?>

<?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="timestamp">
            <?= date('Y-m-d H:i:s T') ?>
        </div>
    </div>
    
    <script>
        function toggleDetails() {
            const details = document.getElementById('details');
            const toggle = document.querySelector('.details-toggle');
            
            if (details.classList.contains('show')) {
                details.classList.remove('show');
                toggle.textContent = 'Show Technical Details';
            } else {
                details.classList.add('show');
                toggle.textContent = 'Hide Technical Details';
            }
        }
        
        // Auto-refresh page after 30 seconds (helpful for temporary issues)
        setTimeout(() => {
            const refresh = confirm('This page will refresh automatically. Click OK to refresh now or Cancel to stay.');
            if (refresh) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>