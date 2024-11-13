<?php
/**
 * Web Content Fetcher
 * 
 * This script fetches web content with optional XPath extraction and caching capabilities.
 * 
 * @author Your Name
 * @license MIT License
 */

// Load configuration
$config = require_once 'config.php';
if (!file_exists($config['CACHE_DIR'])) {
    mkdir($config['CACHE_DIR'], 0755, true);
}

// Initialize logging
ini_set('log_errors', 1);
ini_set('error_log', $config['LOG_FILE']);
error_reporting($config['DEBUG'] ? E_ALL : E_ERROR | E_PARSE);
ini_set('display_errors', $config['DEBUG'] ? 1 : 0);

/**
 * Writes a debug log message with proper sanitization
 */
function writeDebugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $sanitizedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $logMessage = "[{$timestamp}] {$sanitizedMessage}";
    
    if ($data !== null) {
        $logMessage .= " Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    error_log($logMessage . "\n");
}

/**
 * Validates the API key using secure comparison
 */
function validateApiKey($providedKey, $validKey) {
    return hash_equals($validKey, $providedKey);
}

/**
 * Sanitizes a filename by removing or replacing unsafe characters
 */
function sanitizeFileName($str) {
    $safe = preg_replace('/[^\w.-]/u', '_', $str);
    return substr($safe, 0, 255); // Limit filename length
}

/**
 * Handles error responses in a consistent format
 */
function sendErrorResponse($status, $message) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'Method Not Allowed');
}

// Get and validate headers
$headers = getallheaders();
writeDebugLog('Received request headers', array_intersect_key($headers, array_flip(['User-Agent', 'Accept'])));

// Get parameters with validation
$apiKey = $_POST['API-KEY'] ?? $headers['API-KEY'] ?? '';
$url = filter_var($_POST['URL'] ?? $headers['URL'] ?? '', FILTER_VALIDATE_URL);
$xpath = $_POST['XPATH'] ?? $headers['XPATH'] ?? '';
$force = filter_var($_POST['FORCE'] ?? $headers['FORCE'] ?? '0', FILTER_VALIDATE_BOOLEAN);

// Validate essential parameters
if (!validateApiKey($apiKey, $config['API_KEY'])) {
    writeDebugLog('Invalid API key attempt');
    sendErrorResponse(403, 'Invalid API Key');
}

if (!$url) {
    sendErrorResponse(400, 'Invalid URL provided');
}

// Validate XPath if provided
if (!empty($xpath) && !preg_match('/^[\/\w\[\]@=\s.*()]+$/', $xpath)) {
    sendErrorResponse(400, 'Invalid XPath expression');
}

/**
 * Finds a recent cached file
 */
function findRecentFile($baseFileName, $cacheDir, $cacheTime) {
    $files = glob($cacheDir . '/*-' . $baseFileName);
    if (empty($files)) {
        return null;
    }
    
    $timeThreshold = time() - $cacheTime;
    rsort($files);
    
    foreach ($files as $file) {
        if (filemtime($file) >= $timeThreshold) {
            return $file;
        }
    }
    
    return null;
}

/**
 * Extracts content using XPath with error handling
 */
function extractByXPath($html, $xpath) {
    libxml_use_internal_errors(true);
    
    $dom = new DOMDocument();
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    
    if (!@$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        writeDebugLog('DOM loading error', $errors);
        return null;
    }
    
    $xpath_obj = new DOMXPath($dom);
    $elements = @$xpath_obj->query($xpath);
    
    if ($elements === false || $elements->length === 0) {
        return null;
    }
    
    return $dom->saveHTML($elements->item(0));
}

try {
    // Generate base filename
    $baseFileName = sanitizeFileName($url) . 
                   (empty($xpath) ? '' : '_' . sanitizeFileName($xpath)) . 
                   '.txt';
    
    // Check cache unless force refresh is requested
    if (!$force) {
        $cachedFile = findRecentFile($baseFileName, $config['CACHE_DIR'], $config['CACHE_TIME']);
        if ($cachedFile !== null) {
            header('Content-Type: text/html; charset=utf-8');
            header('X-Cache: HIT');
            readfile($cachedFile);
            exit;
        }
    }
    
    // Initialize cURL with secure defaults
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $config['CURL_TIMEOUT'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => $config['USER_AGENT'],
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP
    ]);
    
    $output = curl_exec($ch);
    
    if ($output === false) {
        throw new Exception("Failed to fetch URL: " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        throw new Exception("HTTP error: " . $httpCode);
    }
    
    // Process XPath if specified
    if (!empty($xpath)) {
        $extracted = extractByXPath($output, $xpath);
        if ($extracted !== null) {
            $output = $extracted;
        }
    }
    
    // Save to cache
    $filename = $config['CACHE_DIR'] . '/' . date('Ymd-His') . '-' . $baseFileName;
    if (file_put_contents($filename, $output) === false) {
        throw new Exception("Failed to save cache file");
    }
    
    // Output response
    header('Content-Type: text/html; charset=utf-8');
    header('X-Cache: MISS');
    echo $output;
    
} catch (Exception $e) {
    writeDebugLog('Error occurred', ['message' => $e->getMessage()]);
    sendErrorResponse(500, $e->getMessage());
}
