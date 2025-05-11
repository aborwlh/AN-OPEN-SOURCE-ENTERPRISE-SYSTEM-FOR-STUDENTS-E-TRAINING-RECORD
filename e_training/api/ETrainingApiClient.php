<?php
/**
 * API Client for your e-training API
 * 
 * This class provides methods to interact with your API endpoints
 */
class ETrainingApiClient {
    private $apiUrl;
    private $apiKey;
    
    /**
     * Constructor
     * 
     * @param string $apiUrl The URL to your API
     * @param string $apiKey Your API key
     */
    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make an API request
     * 
     * @param string $endpoint The API endpoint to call
     * @param array $params Additional query parameters
     * @return array The API response as an associative array
     * @throws Exception If the API request fails
     */
    public function request($endpoint, $params = []) {
        // Build the URL with query parameters
        $url = $this->apiUrl . '?endpoint=' . urlencode($endpoint);
        
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
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('API request failed: ' . $error);
        }
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL
        curl_close($ch);
        
        // Parse the JSON response
        $data = json_decode($response, true);
        
        // Check for JSON parsing errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse API response: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Test the API connection
     * 
     * @return array The test response
     */
    public function testConnection() {
        return $this->request('test');
    }
    
    /**
     * Get completed courses for a user
     * 
     * @param string $email The user's email address
     * @return array The completed courses data
     */
    public function getCompletedCourses($email) {
        return $this->request('completed_courses', ['email' => $email]);
    }
    
    /**
     * Verify a certificate
     * 
     * @param string $code The certificate code to verify
     * @return array The certificate verification data
     */
    public function verifyCertificate($code) {
        return $this->request('verify_certificate', ['code' => $code]);
    }
}
?>