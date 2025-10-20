<?php
/**
 * NCKH API Client SDK
 * PHP SDK để tương tác với NCKH RESTful API v2
 */

class NckhApiClient
{
    private $baseUrl;
    private $token;
    private $timeout;
    private $headers;

    public function __construct($baseUrl = 'http://localhost/api/v2', $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    /**
     * Set authentication token
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->headers['Authorization'] = 'Bearer ' . $token;
    }

    /**
     * Get authentication token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set custom header
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Remove header
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
    }

    /**
     * Make HTTP request
     */
    private function request($method, $endpoint, $data = null, $params = [])
    {
        $url = $this->baseUrl . $endpoint;
        
        // Add query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        // Add request data
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . $response);
        }

        return [
            'data' => $decodedResponse,
            'http_code' => $httpCode
        ];
    }

    /**
     * Build headers array
     */
    private function buildHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        return $headers;
    }

    /**
     * Handle API response
     */
    private function handleResponse($response)
    {
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            return $response['data'];
        }

        $message = $response['data']['message'] ?? 'Unknown error';
        throw new Exception($message, $response['http_code']);
    }

    // ==================== AUTHENTICATION METHODS ====================

    /**
     * Login to API
     */
    public function login($username, $password)
    {
        $response = $this->request('POST', '/auth/login', [
            'username' => $username,
            'password' => $password
        ]);

        $data = $this->handleResponse($response);
        
        if ($data['success'] && isset($data['data']['token'])) {
            $this->setToken($data['data']['token']);
        }

        return $data;
    }

    /**
     * Logout from API
     */
    public function logout()
    {
        $response = $this->request('POST', '/auth/logout');
        $this->token = null;
        unset($this->headers['Authorization']);
        return $this->handleResponse($response);
    }

    /**
     * Refresh token
     */
    public function refreshToken()
    {
        $response = $this->request('POST', '/auth/refresh');
        $data = $this->handleResponse($response);
        
        if ($data['success'] && isset($data['data']['token'])) {
            $this->setToken($data['data']['token']);
        }

        return $data;
    }

    /**
     * Get current user info
     */
    public function getCurrentUser()
    {
        $response = $this->request('GET', '/auth/me');
        return $this->handleResponse($response);
    }

    /**
     * Change password
     */
    public function changePassword($currentPassword, $newPassword, $confirmPassword)
    {
        $response = $this->request('POST', '/auth/change-password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword
        ]);

        return $this->handleResponse($response);
    }

    // ==================== STUDENT METHODS ====================

    /**
     * Get students list
     */
    public function getStudents($params = [])
    {
        $response = $this->request('GET', '/students', null, $params);
        return $this->handleResponse($response);
    }

    /**
     * Get student by ID
     */
    public function getStudent($id)
    {
        $response = $this->request('GET', '/students/' . $id);
        return $this->handleResponse($response);
    }

    /**
     * Create student
     */
    public function createStudent($data)
    {
        $response = $this->request('POST', '/students', $data);
        return $this->handleResponse($response);
    }

    /**
     * Update student
     */
    public function updateStudent($id, $data)
    {
        $response = $this->request('PUT', '/students/' . $id, $data);
        return $this->handleResponse($response);
    }

    /**
     * Delete student
     */
    public function deleteStudent($id)
    {
        $response = $this->request('DELETE', '/students/' . $id);
        return $this->handleResponse($response);
    }

    // ==================== PROJECT METHODS ====================

    /**
     * Get projects list
     */
    public function getProjects($params = [])
    {
        $response = $this->request('GET', '/projects', null, $params);
        return $this->handleResponse($response);
    }

    /**
     * Get project by ID
     */
    public function getProject($id)
    {
        $response = $this->request('GET', '/projects/' . $id);
        return $this->handleResponse($response);
    }

    /**
     * Create project
     */
    public function createProject($data)
    {
        $response = $this->request('POST', '/projects', $data);
        return $this->handleResponse($response);
    }

    /**
     * Update project
     */
    public function updateProject($id, $data)
    {
        $response = $this->request('PUT', '/projects/' . $id, $data);
        return $this->handleResponse($response);
    }

    /**
     * Delete project
     */
    public function deleteProject($id)
    {
        $response = $this->request('DELETE', '/projects/' . $id);
        return $this->handleResponse($response);
    }

    /**
     * Add member to project
     */
    public function addProjectMember($projectId, $studentId, $role = 'member')
    {
        $response = $this->request('POST', '/projects/' . $projectId . '/members', [
            'student_id' => $studentId,
            'role' => $role
        ]);
        return $this->handleResponse($response);
    }

    /**
     * Remove member from project
     */
    public function removeProjectMember($projectId, $studentId)
    {
        $response = $this->request('DELETE', '/projects/' . $projectId . '/members/' . $studentId);
        return $this->handleResponse($response);
    }

    /**
     * Evaluate project
     */
    public function evaluateProject($projectId, $score, $comment = '', $criteria = [])
    {
        $response = $this->request('POST', '/projects/' . $projectId . '/evaluation', [
            'score' => $score,
            'comment' => $comment,
            'criteria' => $criteria
        ]);
        return $this->handleResponse($response);
    }

    // ==================== FACULTY METHODS ====================

    /**
     * Get faculties list
     */
    public function getFaculties($params = [])
    {
        $response = $this->request('GET', '/faculties', null, $params);
        return $this->handleResponse($response);
    }

    /**
     * Get faculty by ID
     */
    public function getFaculty($id)
    {
        $response = $this->request('GET', '/faculties/' . $id);
        return $this->handleResponse($response);
    }

    /**
     * Create faculty
     */
    public function createFaculty($data)
    {
        $response = $this->request('POST', '/faculties', $data);
        return $this->handleResponse($response);
    }

    /**
     * Update faculty
     */
    public function updateFaculty($id, $data)
    {
        $response = $this->request('PUT', '/faculties/' . $id, $data);
        return $this->handleResponse($response);
    }

    /**
     * Delete faculty
     */
    public function deleteFaculty($id)
    {
        $response = $this->request('DELETE', '/faculties/' . $id);
        return $this->handleResponse($response);
    }

    /**
     * Get faculty statistics
     */
    public function getFacultyStatistics($id)
    {
        $response = $this->request('GET', '/faculties/' . $id . '/statistics');
        return $this->handleResponse($response);
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Health check
     */
    public function healthCheck()
    {
        $response = $this->request('GET', '/health');
        return $this->handleResponse($response);
    }

    /**
     * Check if authenticated
     */
    public function isAuthenticated()
    {
        return !empty($this->token);
    }

    /**
     * Get base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get timeout
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
}
