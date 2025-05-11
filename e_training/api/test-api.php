<?php
// API configuration
$apiUrl = 'https://oceanlearn.ct.ws/Home/e_training/api/working.php'; // Replace with your actual API URL
$apiKey = 'ab451737ef49bdf783cce0556a3e75edc7dd7feafb6ae6a92fc3792387676fa1';

// Function to call your API
function callApi($endpoint, $params = []) {
    global $apiUrl, $apiKey;
    
    // Build the URL with query parameters
    $url = $apiUrl . '?endpoint=' . urlencode($endpoint);
    
    // Add any additional parameters
    foreach ($params as $key => $value) {
        $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    // For debugging - output request details
    echo "Request URL: $url\n";
    echo "Authorization: Bearer " . substr($apiKey, 0, 10) . "...\n\n";
    
    // Execute the request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "cURL Error: " . $error;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse the JSON response
    $data = json_decode($response, true);
    
    // Return formatted JSON for readability
    return json_encode($data, JSON_PRETTY_PRINT);
}

// Test the API endpoints
echo "=== Testing API Connection ===\n";
echo callApi('test');
echo "\n\n";

echo "=== Testing Completed Courses ===\n";
echo callApi('completed_courses', ['email' => 'student1@student1.com']);
echo "\n\n";

echo "=== Testing Certificate Verification ===\n";
echo callApi('verify_certificate', ['code' => 'CERT-1-1']);
?>