<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;

/**
 * Unit tests cho Project Model
 */
class ProjectModelTest extends TestCase
{
    public function testCanCreateProject(): void
    {
        // Tạo user trước
        $studentData = UserFactory::createStudent(['username' => 'student1']);
        $studentId = $this->insertTestData('users', $studentData);

        $supervisorData = UserFactory::createTeacher(['username' => 'teacher1']);
        $supervisorId = $this->insertTestData('users', $supervisorData);

        $projectData = ProjectFactory::create([
            'title' => 'Test Project',
            'student_id' => $studentId,
            'supervisor_id' => $supervisorId
        ]);

        $projectId = $this->insertTestData('projects', $projectData);

        $this->assertGreaterThan(0, $projectId);
        $this->assertDatabaseHas('projects', [
            'title' => 'Test Project',
            'student_id' => $studentId,
            'supervisor_id' => $supervisorId
        ]);
    }

    public function testCanCreatePendingProject(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projectData = ProjectFactory::createPending([
            'student_id' => $studentId
        ]);

        $this->insertTestData('projects', $projectData);

        $this->assertDatabaseHas('projects', [
            'status' => 'pending'
        ]);
    }

    public function testCanCreateApprovedProject(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projectData = ProjectFactory::createApproved([
            'student_id' => $studentId
        ]);

        $this->insertTestData('projects', $projectData);

        $this->assertDatabaseHas('projects', [
            'status' => 'approved'
        ]);
    }

    public function testProjectHasValidBudget(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projectData = ProjectFactory::create([
            'student_id' => $studentId,
            'budget' => 5000000
        ]);

        $this->insertTestData('projects', $projectData);

        $this->assertDatabaseHas('projects', [
            'budget' => 5000000
        ]);
    }

    public function testProjectHasValidDates(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $startDate = '2024-01-01';
        $endDate = '2024-12-31';

        $projectData = ProjectFactory::create([
            'student_id' => $studentId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $this->insertTestData('projects', $projectData);

        $this->assertDatabaseHas('projects', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function testCanCreateMultipleProjects(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projects = ProjectFactory::createMultiple(3, ['student_id' => $studentId]);

        $this->assertCount(3, $projects);

        foreach ($projects as $project) {
            $this->assertArrayHasKey('title', $project);
            $this->assertArrayHasKey('description', $project);
            $this->assertArrayHasKey('status', $project);
            $this->assertEquals($studentId, $project['student_id']);
        }
    }

    public function testProjectStatusValidation(): void
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'completed'];
        
        foreach ($validStatuses as $status) {
            $projectData = ProjectFactory::create(['status' => $status]);
            $this->assertEquals($status, $projectData['status']);
        }
    }

    public function testProjectHasRequiredFields(): void
    {
        $projectData = ProjectFactory::create();

        $requiredFields = ['title', 'description', 'student_id', 'status'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $projectData);
            $this->assertNotEmpty($projectData[$field]);
        }
    }
}
