<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Only admin can configure AI
if ($_SESSION['role'] !== 'admin') {
    die('Access denied. Only administrators can configure AI settings.');
}

$config_file = 'ai_config.php';
$success_message = '';
$error_message = '';

// Read current configuration
$current_config = [
    'provider' => 'mock',
    'gemini_key' => '',
    'gemini_model' => 'gemini-1.5-flash',
    'gemini_api_version' => 'v1',
    'openai_key' => '',
    'openai_model' => 'gpt-3.5-turbo'
];

// Load AI config to get provider
require_once 'ai_config.php';
$current_config['provider'] = AI_PROVIDER;

// Load API keys from ai_keys.php
$keysFile = __DIR__ . '/ai_keys.php';
if (file_exists($keysFile)) {
    require_once $keysFile;
    if (defined('GEMINI_API_KEY')) {
        $current_config['gemini_key'] = GEMINI_API_KEY;
    }
    if (defined('OPENAI_API_KEY')) {
        $current_config['openai_key'] = OPENAI_API_KEY;
    }
}

// Load model settings from ai_config.php
if (defined('GEMINI_MODEL')) {
    $current_config['gemini_model'] = GEMINI_MODEL;
}
if (defined('GEMINI_API_VERSION')) {
    $current_config['gemini_api_version'] = GEMINI_API_VERSION;
}
if (defined('OPENAI_MODEL')) {
    $current_config['openai_model'] = OPENAI_MODEL;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider = $_POST['provider'] ?? 'gemini';
    $gemini_key = $_POST['gemini_key'] ?? '';
    $gemini_model = $_POST['gemini_model'] ?? 'gemini-1.5-flash';
    $gemini_api_version = $_POST['gemini_api_version'] ?? 'v1';
    $openai_key = $_POST['openai_key'] ?? '';
    $openai_model = $_POST['openai_model'] ?? 'gpt-3.5-turbo';
    
    // Validate
    if ($provider === 'gemini' && empty($gemini_key)) {
        $error_message = 'Please provide a Gemini API key';
    } elseif ($provider === 'openai' && empty($openai_key)) {
        $error_message = 'Please provide an OpenAI API key';
    } else {
        // Save API keys to ai_keys.php
        $keysContent = "<?php\n";
        $keysContent .= "/**\n";
        $keysContent .= " * AI API Keys Configuration\n";
        $keysContent .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $keysContent .= " * IMPORTANT: This file is not tracked in git for security\n";
        $keysContent .= " */\n\n";
        $keysContent .= "// Google Gemini API Key\n";
        $keysContent .= "define('GEMINI_API_KEY', '" . addslashes($gemini_key) . "');\n\n";
        $keysContent .= "// OpenAI API Key\n";
        $keysContent .= "define('OPENAI_API_KEY', '" . addslashes($openai_key) . "');\n";
        $keysContent .= "?>\n";
        
        if (!file_put_contents($keysFile, $keysContent)) {
            $error_message = '❌ Failed to save API keys. Check file permissions.';
        } else {
            // Update provider and model settings in ai_config.php
            $template_content = file_get_contents($config_file);
            
            $template_content = preg_replace(
                "/define\('AI_PROVIDER',\s*'[^']+'\);/",
                "define('AI_PROVIDER', '{$provider}');",
                $template_content
            );
            $template_content = preg_replace(
                "/define\('GEMINI_MODEL',\s*'[^']+'\);/",
                "define('GEMINI_MODEL', '{$gemini_model}');",
                $template_content
            );
            $template_content = preg_replace(
                "/define\('GEMINI_API_VERSION',\s*'[^']+'\);/",
                "define('GEMINI_API_VERSION', '{$gemini_api_version}');",
                $template_content
            );
            $template_content = preg_replace(
                "/define\('OPENAI_MODEL',\s*'[^']+'\);/",
                "define('OPENAI_MODEL', '{$openai_model}');",
                $template_content
            );
            
            if (file_put_contents($config_file, $template_content)) {
                $success_message = '✅ AI configuration saved successfully!';
                $current_config = [
                    'provider' => $provider,
                    'gemini_key' => $gemini_key,
                    'gemini_model' => $gemini_model,
                    'gemini_api_version' => $gemini_api_version,
                    'openai_key' => $openai_key,
                    'openai_model' => $openai_model
                ];
            } else {
                $error_message = '❌ Failed to save configuration file. Check file permissions.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Configuration - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
        }
        .custom-toast {
            min-width: 300px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-success { border-left: 4px solid #28a745; }
        .toast-error { border-left: 4px solid #dc3545; }
        .toast-warning { border-left: 4px solid #ffc107; }
        .toast-info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2><i class="fas fa-robot mr-2"></i>AI Configuration</h2>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle mr-2"></i>SECURITY WARNING</h6>
                    <p class="mb-2">
                        <strong>Your API key was previously exposed in GitHub!</strong> You should:
                    </p>
                    <ol class="mb-0">
                        <li>Go to <a href="https://makersuite.google.com/app/apikey" target="_blank" class="text-white"><u>Google AI Studio</u></a></li>
                        <li>Delete the exposed API key immediately</li>
                        <li>Generate a new API key</li>
                        <li>Enter the new key below and save</li>
                    </ol>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Configure AI Provider</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-server mr-1"></i>AI Provider</label>
                                <select name="provider" id="provider" class="form-control form-control-lg" required>
                                    <option value="mock" <?php echo $current_config['provider'] === 'mock' ? 'selected' : ''; ?>>Mock AI (No API Key - For Testing)</option>
                                    <option value="gemini" <?php echo $current_config['provider'] === 'gemini' ? 'selected' : ''; ?>>Google Gemini (FREE)</option>
                                    <option value="openai" <?php echo $current_config['provider'] === 'openai' ? 'selected' : ''; ?>>OpenAI (Paid - Better Quality)</option>
                                </select>
                            </div>
                            
                            <!-- Mock AI Configuration -->
                            <div id="mock_config" style="display: none;">
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-rocket mr-2"></i>Mock AI (Demo Mode)</h6>
                                    <ul class="mb-0">
                                        <li>✅ No API key required</li>
                                        <li>✅ Works instantly</li>
                                        <li>✅ Perfect for testing</li>
                                        <li>✅ Generates realistic job descriptions</li>
                                        <li>⚠️ Not as sophisticated as real AI</li>
                                    </ul>
                                    <hr>
                                    <strong>How it works:</strong>
                                    <p class="mb-0">Mock AI generates professional job descriptions using templates. It's perfect for testing the system without setting up API keys. When you're ready for production, switch to Gemini (FREE) or OpenAI for better quality.</p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i><strong>Ready to use!</strong> No configuration needed. Just click "Save Configuration" and start generating jobs.
                                </div>
                            </div>
                            
                            <!-- Gemini Configuration -->
                            <div id="gemini_config" style="display: none;">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle mr-2"></i>Google Gemini (FREE)</h6>
                                    <ul class="mb-0">
                                        <li>✅ Completely FREE</li>
                                        <li>✅ 60 requests per minute</li>
                                        <li>✅ Good quality job descriptions</li>
                                        <li>✅ No credit card required</li>
                                    </ul>
                                    <hr>
                                    <strong>Get your FREE API key:</strong>
                                    <ol class="mb-0">
                                        <li>Visit: <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a></li>
                                        <li>Sign in with your Google account</li>
                                        <li>Click "Create API Key"</li>
                                        <li>Copy and paste it below</li>
                                    </ol>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold"><i class="fas fa-key mr-1"></i>Gemini API Key</label>
                                    <input type="text" name="gemini_key" class="form-control" value="<?php echo htmlspecialchars($current_config['gemini_key']); ?>" placeholder="AIzaSy...">
                                    <small class="text-muted">Your Google Gemini API key</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold"><i class="fas fa-brain mr-1"></i>Gemini Model</label>
                                    <select name="gemini_model" id="gemini_model" class="form-control">
                                        <optgroup label="⭐ Recommended for Production (v1 API - Stable)">
                                            <option value="gemini-1.5-flash" <?php echo $current_config['gemini_model'] === 'gemini-1.5-flash' ? 'selected' : ''; ?>>gemini-1.5-flash (Stable, fast, recommended) ⭐</option>
                                            <option value="gemini-1.5-pro" <?php echo $current_config['gemini_model'] === 'gemini-1.5-pro' ? 'selected' : ''; ?>>gemini-1.5-pro (Higher quality, slower)</option>
                                            <option value="gemini-1.0-pro" <?php echo $current_config['gemini_model'] === 'gemini-1.0-pro' ? 'selected' : ''; ?>>gemini-1.0-pro (Older stable version)</option>
                                        </optgroup>
                                        <optgroup label="🔄 Latest Pointers (v1 API - Auto-updates)">
                                            <option value="gemini-2.5-flash" <?php echo $current_config['gemini_model'] === 'gemini-2.5-flash' ? 'selected' : ''; ?>>gemini-2.5-flash (Latest, may require v1beta)</option>
                                            <option value="gemini-2.5-pro" <?php echo $current_config['gemini_model'] === 'gemini-2.5-pro' ? 'selected' : ''; ?>>gemini-2.5-pro (Latest pro, may require v1beta)</option>
                                            <option value="gemini-2.0-flash" <?php echo $current_config['gemini_model'] === 'gemini-2.0-flash' ? 'selected' : ''; ?>>gemini-2.0-flash (Newer, may require v1beta)</option>
                                            <option value="gemini-2.0-flash-001" <?php echo $current_config['gemini_model'] === 'gemini-2.0-flash-001' ? 'selected' : ''; ?>>gemini-2.0-flash-001 (Specific version)</option>
                                        </optgroup>
                                        <optgroup label="💡 Lightweight Options (v1 API)">
                                            <option value="gemini-2.5-flash-lite" <?php echo $current_config['gemini_model'] === 'gemini-2.5-flash-lite' ? 'selected' : ''; ?>>gemini-2.5-flash-lite (Lighter 2.5)</option>
                                            <option value="gemini-2.0-flash-lite" <?php echo $current_config['gemini_model'] === 'gemini-2.0-flash-lite' ? 'selected' : ''; ?>>gemini-2.0-flash-lite (Lighter 2.0)</option>
                                            <option value="gemini-2.0-flash-lite-001" <?php echo $current_config['gemini_model'] === 'gemini-2.0-flash-lite-001' ? 'selected' : ''; ?>>gemini-2.0-flash-lite-001</option>
                                        </optgroup>
                                        <optgroup label="🔄 Latest Pointers (v1beta - Auto-updates)">
                                            <option value="gemini-flash-latest" <?php echo $current_config['gemini_model'] === 'gemini-flash-latest' ? 'selected' : ''; ?>>gemini-flash-latest (Always latest flash)</option>
                                            <option value="gemini-flash-lite-latest" <?php echo $current_config['gemini_model'] === 'gemini-flash-lite-latest' ? 'selected' : ''; ?>>gemini-flash-lite-latest (Always latest lite)</option>
                                            <option value="gemini-pro-latest" <?php echo $current_config['gemini_model'] === 'gemini-pro-latest' ? 'selected' : ''; ?>>gemini-pro-latest (Always latest pro)</option>
                                        </optgroup>
                                        <optgroup label="🧪 Experimental/Preview (v1beta - Cutting edge)">
                                            <option value="gemini-3-pro-preview" <?php echo $current_config['gemini_model'] === 'gemini-3-pro-preview' ? 'selected' : ''; ?>>gemini-3-pro-preview (Next gen pro)</option>
                                            <option value="gemini-3-flash-preview" <?php echo $current_config['gemini_model'] === 'gemini-3-flash-preview' ? 'selected' : ''; ?>>gemini-3-flash-preview (Next gen flash)</option>
                                            <option value="gemini-exp-1206" <?php echo $current_config['gemini_model'] === 'gemini-exp-1206' ? 'selected' : ''; ?>>gemini-exp-1206 (Experimental Dec 6)</option>
                                            <option value="gemini-2.5-flash-preview-09-2025" <?php echo $current_config['gemini_model'] === 'gemini-2.5-flash-preview-09-2025' ? 'selected' : ''; ?>>gemini-2.5-flash-preview-09-2025</option>
                                            <option value="deep-research-pro-preview-12-2025" <?php echo $current_config['gemini_model'] === 'deep-research-pro-preview-12-2025' ? 'selected' : ''; ?>>deep-research-pro-preview-12-2025</option>
                                        </optgroup>
                                        <optgroup label="🎯 Specialized Models (v1beta)">
                                            <option value="gemini-2.5-flash-preview-tts" <?php echo $current_config['gemini_model'] === 'gemini-2.5-flash-preview-tts' ? 'selected' : ''; ?>>gemini-2.5-flash-preview-tts (Text-to-speech)</option>
                                            <option value="gemini-2.5-pro-preview-tts" <?php echo $current_config['gemini_model'] === 'gemini-2.5-pro-preview-tts' ? 'selected' : ''; ?>>gemini-2.5-pro-preview-tts (Pro with TTS)</option>
                                            <option value="gemini-2.0-flash-exp-image-generation" <?php echo $current_config['gemini_model'] === 'gemini-2.0-flash-exp-image-generation' ? 'selected' : ''; ?>>gemini-2.0-flash-exp-image-generation</option>
                                            <option value="gemini-2.5-flash-image" <?php echo $current_config['gemini_model'] === 'gemini-2.5-flash-image' ? 'selected' : ''; ?>>gemini-2.5-flash-image (Image processing)</option>
                                            <option value="gemini-2.5-computer-use-preview-10-2025" <?php echo $current_config['gemini_model'] === 'gemini-2.5-computer-use-preview-10-2025' ? 'selected' : ''; ?>>gemini-2.5-computer-use-preview-10-2025</option>
                                        </optgroup>
                                        <optgroup label="📦 Small Models (v1beta - Lower resources)">
                                            <option value="gemma-3-27b-it" <?php echo $current_config['gemini_model'] === 'gemma-3-27b-it' ? 'selected' : ''; ?>>gemma-3-27b-it (27B parameters)</option>
                                            <option value="gemma-3-12b-it" <?php echo $current_config['gemini_model'] === 'gemma-3-12b-it' ? 'selected' : ''; ?>>gemma-3-12b-it (12B parameters)</option>
                                            <option value="gemma-3-4b-it" <?php echo $current_config['gemini_model'] === 'gemma-3-4b-it' ? 'selected' : ''; ?>>gemma-3-4b-it (4B parameters)</option>
                                            <option value="gemma-3-1b-it" <?php echo $current_config['gemini_model'] === 'gemma-3-1b-it' ? 'selected' : ''; ?>>gemma-3-1b-it (1B parameters)</option>
                                        </optgroup>
                                    </select>
                                    <small class="text-muted">Choose the AI model for job generation. Recommended: gemini-2.5-flash</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold"><i class="fas fa-code-branch mr-1"></i>API Version</label>
                                    <select name="gemini_api_version" id="gemini_api_version" class="form-control">
                                        <option value="v1" <?php echo $current_config['gemini_api_version'] === 'v1' ? 'selected' : ''; ?>>v1 (Stable - Recommended)</option>
                                        <option value="v1beta" <?php echo $current_config['gemini_api_version'] === 'v1beta' ? 'selected' : ''; ?>>v1beta (Beta - More features, may have breaking changes)</option>
                                    </select>
                                    <small class="text-muted">v1 is stable. v1beta has more models but may change.</small>
                                </div>
                            </div>
                            
                            <!-- OpenAI Configuration -->
                            <div id="openai_config" style="display: none;">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-dollar-sign mr-2"></i>OpenAI (Paid Service)</h6>
                                    <ul class="mb-0">
                                        <li>💰 Paid service (requires credit card)</li>
                                        <li>✅ Better quality descriptions</li>
                                        <li>✅ More consistent results</li>
                                        <li>💵 Cost: ~$0.01-0.03 per job</li>
                                    </ul>
                                    <hr>
                                    <strong>Get your API key:</strong>
                                    <ol class="mb-0">
                                        <li>Visit: <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a></li>
                                        <li>Create account and add payment method</li>
                                        <li>Create new API key</li>
                                        <li>Copy and paste it below</li>
                                    </ol>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold"><i class="fas fa-key mr-1"></i>OpenAI API Key</label>
                                    <input type="text" name="openai_key" class="form-control" value="<?php echo htmlspecialchars($current_config['openai_key']); ?>" placeholder="sk-...">
                                    <small class="text-muted">Your OpenAI API key</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold"><i class="fas fa-brain mr-1"></i>Model</label>
                                    <select name="openai_model" class="form-control">
                                        <option value="gpt-3.5-turbo" <?php echo $current_config['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo (Faster, Cheaper)</option>
                                        <option value="gpt-4" <?php echo $current_config['openai_model'] === 'gpt-4' ? 'selected' : ''; ?>>GPT-4 (Best Quality, More Expensive)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="text-right mt-4">
                                <a href="job_openings.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>Back to Job Openings
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Save Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Toast Notification Function
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-times-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        const icon = iconMap[type] || iconMap['info'];
        
        const toast = $(`
            <div class="custom-toast toast-${type}" id="${toastId}">
                <div class="toast-header">
                    <i class="fas ${icon} mr-2"></i>
                    <strong class="mr-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="ml-2 mb-1 close" onclick="$('#${toastId}').fadeOut(300, function(){ $(this).remove(); })">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);
        
        $('#toastContainer').append(toast);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('#' + toastId).fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    $(document).ready(function(){
        // Show toast for success/error messages
        <?php if ($success_message): ?>
            showToast('<?php echo addslashes($success_message); ?>', 'success');
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            showToast('<?php echo addslashes($error_message); ?>', 'error');
        <?php endif; ?>
        
        function toggleProviderConfig() {
            var provider = $('#provider').val();
            $('#mock_config, #gemini_config, #openai_config').hide();
            
            if (provider === 'mock') {
                $('#mock_config').show();
            } else if (provider === 'gemini') {
                $('#gemini_config').show();
            } else {
                $('#openai_config').show();
            }
        }
        
        $('#provider').on('change', toggleProviderConfig);
        toggleProviderConfig();
    });
    </script>
</body>
</html>
