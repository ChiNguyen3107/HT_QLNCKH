<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class StudentApiTest extends TestCase
{
    private $apiUrl = 'http://localhost/api/v2';
    private $token = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiUrl = $_ENV['API_URL'] ?? 'http://localhost/api/v2';
        
        // Login to get token
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123'
        ]);
        
        if ($loginResponse->status() === 200) {
            $this->token = $loginResponse->json('data.token');
        }
    }

    /**
     * Test get students list
     */
    public function testGetStudents()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson($this->apiUrl . '/students');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'student_code',
                            'full_name',
                            'class_name',
                            'department_name',
                            'project_count',
                            'completed_project_count',
                            'research_status'
                        ]
                    ],
                    'meta' => [
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'total_pages',
                            'has_next',
                            'has_prev'
                        ]
                    ],
                    'timestamp'
                ]);
    }

    /**
     * Test get students with filters
     */
    public function testGetStudentsWithFilters()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson($this->apiUrl . '/students', [
            'department' => 'CNTT',
            'research_status' => 'active',
            'page' => 1,
            'limit' => 5
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    /**
     * Test get student by ID
     */
    public function testGetStudentById()
    {
        // First get students list to get an ID
        $studentsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson($this->apiUrl . '/students?limit=1');

        if ($studentsResponse->status() === 200 && !empty($studentsResponse->json('data'))) {
            $studentId = $studentsResponse->json('data.0.id');

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token
            ])->getJson($this->apiUrl . '/students/' . $studentId);

            $response->assertStatus(200)
                    ->assertJsonStructure([
                        'success',
                        'status',
                        'message',
                        'data' => [
                            'id',
                            'student_code',
                            'full_name',
                            'class_name',
                            'department_name',
                            'project_count',
                            'completed_project_count',
                            'research_status'
                        ],
                        'timestamp'
                    ]);
        }
    }

    /**
     * Test get non-existent student
     */
    public function testGetStudentNotFound()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson($this->apiUrl . '/students/NONEXISTENT');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'status' => 404
                ]);
    }

    /**
     * Test create student
     */
    public function testCreateStudent()
    {
        $studentData = [
            'student_code' => 'TEST001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'class_code' => 'CNTT01'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson($this->apiUrl . '/students', $studentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'student_code',
                        'full_name',
                        'class_name',
                        'department_name'
                    ],
                    'timestamp'
                ])
                ->assertJson([
                    'success' => true,
                    'status' => 201
                ]);
    }

    /**
     * Test create student with validation error
     */
    public function testCreateStudentValidationError()
    {
        $studentData = [
            'student_code' => 'TEST002'
            // Missing required fields
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson($this->apiUrl . '/students', $studentData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'status' => 422
                ]);
    }

    /**
     * Test update student
     */
    public function testUpdateStudent()
    {
        // First create a student
        $studentData = [
            'student_code' => 'TEST003',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'test3@example.com',
            'phone' => '0123456789',
            'class_code' => 'CNTT01'
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson($this->apiUrl . '/students', $studentData);

        if ($createResponse->status() === 201) {
            $studentId = $createResponse->json('data.id');

            $updateData = [
                'first_name' => 'Updated',
                'last_name' => 'Student',
                'email' => 'updated@example.com'
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token
            ])->putJson($this->apiUrl . '/students/' . $studentId, $updateData);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'status' => 200
                    ]);
        }
    }

    /**
     * Test delete student
     */
    public function testDeleteStudent()
    {
        // First create a student
        $studentData = [
            'student_code' => 'TEST004',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'test4@example.com',
            'phone' => '0123456789',
            'class_code' => 'CNTT01'
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson($this->apiUrl . '/students', $studentData);

        if ($createResponse->status() === 201) {
            $studentId = $createResponse->json('data.id');

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token
            ])->deleteJson($this->apiUrl . '/students/' . $studentId);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'message' => 'Xóa sinh viên thành công'
                    ]);
        }
    }

    /**
     * Test unauthorized access
     */
    public function testUnauthorizedAccess()
    {
        $response = $this->getJson($this->apiUrl . '/students');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'status' => 401
                ]);
    }

    /**
     * Test forbidden access
     */
    public function testForbiddenAccess()
    {
        // Login as student (if such user exists)
        $loginResponse = $this->postJson($this->apiUrl . '/auth/login', [
            'username' => 'student',
            'password' => 'student123'
        ]);

        if ($loginResponse->status() === 200) {
            $token = $loginResponse->json('data.token');

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->postJson($this->apiUrl . '/students', [
                'student_code' => 'TEST005',
                'first_name' => 'Test',
                'last_name' => 'Student'
            ]);

            $response->assertStatus(403)
                    ->assertJson([
                        'success' => false,
                        'status' => 403
                    ]);
        }
    }
}
