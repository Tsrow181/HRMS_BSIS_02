<?php
// AI Configuration File
// Switch between different AI providers easily

// AI Provider: 'mock', 'gemini' or 'openai'
// Use 'mock' for testing without API keys
define('AI_PROVIDER', 'mock');

// Load API keys from secure config file (not tracked in git)
$apiKeysFile = __DIR__ . '/ai_keys.php';
if (file_exists($apiKeysFile)) {
    require_once $apiKeysFile;
} else {
    // Default empty keys if file doesn't exist
    define('GEMINI_API_KEY', '');
    define('OPENAI_API_KEY', '');
}

// Available Gemini Models (all support generateContent):
// 
// RECOMMENDED FOR PRODUCTION (v1 API - Stable):
//   - gemini-2.5-flash (Latest, fastest, best for most tasks) ⭐ CURRENT
//   - gemini-2.5-pro (Latest, highest quality, slower)
//   - gemini-2.0-flash (Stable, fast)
//   - gemini-2.0-flash-001 (Specific version)
// 
// LIGHTWEIGHT OPTIONS (v1 API):
//   - gemini-2.5-flash-lite (Lighter version of 2.5 flash)
//   - gemini-2.0-flash-lite (Lighter version of 2.0 flash)
//   - gemini-2.0-flash-lite-001 (Specific lite version)
// 
// LATEST POINTERS (v1beta API - Auto-updates):
//   - gemini-flash-latest (Always points to latest flash model)
//   - gemini-flash-lite-latest (Always points to latest lite model)
//   - gemini-pro-latest (Always points to latest pro model)
// 
// EXPERIMENTAL/PREVIEW (v1beta API - Cutting edge features):
//   - gemini-3-pro-preview (Next generation pro)
//   - gemini-3-flash-preview (Next generation flash)
//   - gemini-exp-1206 (Experimental build from Dec 6)
//   - gemini-2.5-flash-preview-09-2025 (September 2025 preview)
//   - gemini-2.5-flash-lite-preview-09-2025 (Lite preview)
//   - deep-research-pro-preview-12-2025 (Research-focused)
// 
// SPECIALIZED MODELS (v1beta API):
//   - gemini-2.5-flash-preview-tts (Text-to-speech support)
//   - gemini-2.5-pro-preview-tts (Pro with TTS)
//   - gemini-2.0-flash-exp-image-generation (Image generation)
//   - gemini-2.5-flash-image (Image processing)
//   - gemini-3-pro-image-preview / nano-banana-pro-preview (Image models)
//   - gemini-2.5-computer-use-preview-10-2025 (Computer interaction)
//   - gemini-robotics-er-1.5-preview (Robotics applications)
// 
// SMALL MODELS (v1beta API - Lower resource usage):
//   - gemma-3-27b-it (27 billion parameters)
//   - gemma-3-12b-it (12 billion parameters)
//   - gemma-3-4b-it (4 billion parameters)
//   - gemma-3-1b-it (1 billion parameters)
//   - gemma-3n-e4b-it (Efficient 4B)
//   - gemma-3n-e2b-it (Efficient 2B)
// 
// AUDIO MODELS (v1beta API - Native audio support):
//   - gemini-2.5-flash-native-audio-latest
//   - gemini-2.5-flash-native-audio-preview-09-2025
//   - gemini-2.5-flash-native-audio-preview-12-2025
// 
// NOTE: Models in v1 API are more stable. Models in v1beta may have breaking changes.
//       For production use, stick with v1 API models (gemini-2.5-flash, gemini-2.5-pro, etc.)

define('GEMINI_MODEL', 'gemini-2.5-flash'); // Using latest stable model
define('GEMINI_API_VERSION', 'v1'); // v1 or v1beta
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/' . GEMINI_API_VERSION . '/models/' . GEMINI_MODEL . ':generateContent');

// OpenAI Configuration (Paid - better quality)
// Keys are now stored in ai_keys.php (not tracked in git)
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-3.5-turbo'); // or 'gpt-4' for better quality

/**
 * Generate job description using AI
 */
function generateJobWithAI($jobRoleTitle, $jobRoleDescription, $departmentName, $employmentType = 'Full-time', $salaryMin = null, $salaryMax = null) {
    $prompt = buildJobPrompt($jobRoleTitle, $jobRoleDescription, $departmentName, $employmentType, $salaryMin, $salaryMax);
    
    if (AI_PROVIDER === 'mock') {
        return generateMockJob($jobRoleTitle, $jobRoleDescription, $departmentName, $employmentType);
    } elseif (AI_PROVIDER === 'gemini') {
        return callGeminiAPI($prompt);
    } else {
        return callOpenAI($prompt);
    }
}

/**
 * Generate mock job data (no API needed - for testing)
 */
function generateMockJob($jobRoleTitle, $jobRoleDescription, $departmentName, $employmentType) {
    // Generate realistic job data without calling any API
    $title = $jobRoleTitle . " - " . $departmentName;
    
    $description = "We are seeking a qualified {$jobRoleTitle} to join our {$departmentName}. " .
                   "This {$employmentType} position offers an excellent opportunity to contribute to our municipal operations. " .
                   "The ideal candidate will work collaboratively with our team to deliver high-quality services to our community. " .
                   "{$jobRoleDescription}";
    
    $requirements = "• Bachelor's degree in relevant field or equivalent experience\n" .
                   "• Minimum 2 years of professional experience in related role\n" .
                   "• Strong communication and interpersonal skills\n" .
                   "• Proficiency in Microsoft Office Suite (Word, Excel, PowerPoint)\n" .
                   "• Excellent organizational and time management abilities\n" .
                   "• Ability to work independently and as part of a team\n" .
                   "• Strong problem-solving and analytical skills\n" .
                   "• Commitment to public service and community development";
    
    $responsibilities = "• Perform daily tasks and responsibilities as assigned by the department head\n" .
                       "• Collaborate with team members to achieve departmental goals\n" .
                       "• Maintain accurate records and documentation\n" .
                       "• Prepare reports and presentations as required\n" .
                       "• Respond to inquiries from the public and other departments\n" .
                       "• Participate in meetings and training sessions\n" .
                       "• Ensure compliance with municipal policies and procedures\n" .
                       "• Contribute to continuous improvement initiatives";
    
    $experienceLevel = "Mid-Level";
    $educationRequirements = "Bachelor's degree in relevant field or equivalent combination of education and experience";
    
    return [
        'success' => true,
        'data' => [
            'title' => $title,
            'description' => $description,
            'requirements' => $requirements,
            'responsibilities' => $responsibilities,
            'experience_level' => $experienceLevel,
            'education_requirements' => $educationRequirements
        ]
    ];
}

/**
 * Build the prompt for AI
 */
function buildJobPrompt($jobRoleTitle, $jobRoleDescription, $departmentName, $employmentType, $salaryMin, $salaryMax) {
    $salaryInfo = '';
    if ($salaryMin && $salaryMax) {
        $salaryInfo = "\nSalary Range: ₱" . number_format($salaryMin) . " - ₱" . number_format($salaryMax);
    }
    
    $prompt = "You are an expert HR professional creating a job opening for a government/municipal office.

Job Role: {$jobRoleTitle}
Department: {$departmentName}
Employment Type: {$employmentType}
Role Description: {$jobRoleDescription}{$salaryInfo}

CRITICAL: You must respond with ONLY a valid JSON object. Do not include any markdown formatting, code blocks, or explanatory text. Start your response with { and end with }.

Create a comprehensive job opening with the following JSON structure:

{
  \"title\": \"Professional job title (e.g., 'Senior Software Developer - IT Department')\",
  \"description\": \"Compelling 2-3 paragraph job description that explains the role, its importance, and what the candidate will do\",
  \"requirements\": \"Detailed bullet-point list of qualifications, skills, and experience needed. Include education, certifications, technical skills, and soft skills. Format as bullet points with • symbol\",
  \"responsibilities\": \"Detailed bullet-point list of key duties and day-to-day responsibilities. Be specific and actionable. Format as bullet points with • symbol\",
  \"experience_level\": \"Entry Level, Mid-Level, Senior Level, or Executive\",
  \"education_requirements\": \"Specific educational qualifications needed (e.g., 'Bachelor's degree in Computer Science or related field')\"
}

Make it professional, clear, and attractive to qualified candidates. Use proper grammar and formatting. Remember: respond with ONLY the JSON object, nothing else.";

    return $prompt;
}

/**
 * Call Google Gemini API
 */
function callGeminiAPI($prompt) {
    $apiKey = GEMINI_API_KEY;
    $url = GEMINI_API_URL . '?key=' . $apiKey;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'Gemini API Error: ' . $response];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // More aggressive cleanup of markdown and extra formatting
        $text = preg_replace('/```json\s*/i', '', $text);  // Remove ```json
        $text = preg_replace('/```\s*$/s', '', $text);      // Remove trailing ```
        $text = preg_replace('/^```\s*/s', '', $text);      // Remove leading ```
        $text = trim($text);
        
        // Try to extract JSON if it's embedded in other text
        if (preg_match('/\{[\s\S]*\}/s', $text, $matches)) {
            $text = $matches[0];
        }
        
        $jobData = json_decode($text, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['success' => true, 'data' => $jobData];
        } else {
            // Return more detailed error with the actual text for debugging
            return ['error' => 'Failed to parse AI response: ' . json_last_error_msg() . '. Raw text: ' . substr($text, 0, 200)];
        }
    }
    
    return ['error' => 'Invalid response from Gemini API'];
}

/**
 * Call OpenAI API
 */
function callOpenAI($prompt) {
    $apiKey = OPENAI_API_KEY;
    $url = OPENAI_API_URL;
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert HR professional. Always respond with valid JSON only, no markdown formatting.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'OpenAI API Error: ' . $response];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        $text = $result['choices'][0]['message']['content'];
        // Clean up markdown code blocks if present
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*$/', '', $text);
        $text = trim($text);
        
        $jobData = json_decode($text, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['success' => true, 'data' => $jobData];
        } else {
            return ['error' => 'Failed to parse AI response: ' . json_last_error_msg()];
        }
    }
    
    return ['error' => 'Invalid response from OpenAI API'];
}
?>