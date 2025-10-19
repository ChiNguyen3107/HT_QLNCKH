<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;

/**
 * Feature tests cho Project management
 */
class ProjectTest extends TestCase
{
    public function testCanCreateProject(): void
    {
        // Tạo student và teacher
        $studentData = UserFactory::createStudent(['username' => 'student1']);
        $studentId = $this->insertTestData('users', $studentData);

        $teacherData = UserFactory::createTeacher(['username' => 'teacher1']);
        $teacherId = $this->insertTestData('users', $teacherData);

        $projectData = ProjectFactory::create([
            'title' => 'Test Research Project',
            'description' => 'This is a test research project',
            'student_id' => $studentId,
            'supervisor_id' => $teacherId,
            'budget' => 10000000
        ]);

        $projectId = $this->insertTestData('projects', $projectData);

        $this->assertGreaterThan(0, $projectId);
        $this->assertDatabaseHas('projects', [
            'title' => 'Test Research Project',
            'student_id' => $studentId,
            'supervisor_id' => $teacherId
        ]);
    }

    public function testCanViewProjectList(): void
    {
        // Tạo multiple projects
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projects = ProjectFactory::createMultiple(5, ['student_id' => $studentId]);
        
        foreach ($projects as $project) {
            $this->insertTestData('projects', $project);
        }

        // Verify projects exist
        $projectCount = $this->getTableCount('projects');
        $this->assertEquals(5, $projectCount);
    }

    public function testCanUpdateProject(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projectData = ProjectFactory::create([
            'title' => 'Original Title',
            'student_id' => $studentId,
            'status' => 'pending'
        ]);

        $projectId = $this->insertTestData('projects', $projectData);

        // Update project
        $updateData = [
            'title' => 'Updated Title',
            'status' => 'approved'
        ];

        $this->executeQuery(
            "UPDATE projects SET title = ?, status = ? WHERE id = ?",
            [$updateData['title'], $updateData['status'], $projectId]
        );

        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'title' => 'Updated Title',
            'status' => 'approved'
        ]);
    }

    public function testCanDeleteProject(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $projectData = ProjectFactory::create(['student_id' => $studentId]);
        $projectId = $this->insertTestData('projects', $projectData);

        // Delete project
        $this->executeQuery("DELETE FROM projects WHERE id = ?", [$projectId]);

        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
    }

    public function testCanFilterProjectsByStatus(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        // Tạo projects với different statuses
        $pendingProject = ProjectFactory::createPending(['student_id' => $studentId]);
        $approvedProject = ProjectFactory::createApproved(['student_id' => $studentId]);
        $rejectedProject = ProjectFactory::createRejected(['student_id' => $studentId]);

        $this->insertTestData('projects', $pendingProject);
        $this->insertTestData('projects', $approvedProject);
        $this->insertTestData('projects', $rejectedProject);

        // Test filtering
        $pendingCount = $this->executeQuery("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn();
        $approvedCount = $this->executeQuery("SELECT COUNT(*) FROM projects WHERE status = 'approved'")->fetchColumn();
        $rejectedCount = $this->executeQuery("SELECT COUNT(*) FROM projects WHERE status = 'rejected'")->fetchColumn();

        $this->assertEquals(1, $pendingCount);
        $this->assertEquals(1, $approvedCount);
        $this->assertEquals(1, $rejectedCount);
    }

    public function testCanSearchProjectsByTitle(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        $project1 = ProjectFactory::create([
            'title' => 'Machine Learning Research',
            'student_id' => $studentId
        ]);
        $project2 = ProjectFactory::create([
            'title' => 'Web Development Project',
            'student_id' => $studentId
        ]);
        $project3 = ProjectFactory::create([
            'title' => 'Machine Learning Applications',
            'student_id' => $studentId
        ]);

        $this->insertTestData('projects', $project1);
        $this->insertTestData('projects', $project2);
        $this->insertTestData('projects', $project3);

        // Search for "Machine Learning"
        $searchResults = $this->executeQuery(
            "SELECT * FROM projects WHERE title LIKE ?",
            ['%Machine Learning%']
        )->fetchAll();

        $this->assertCount(2, $searchResults);
    }

    public function testProjectBudgetValidation(): void
    {
        $studentData = UserFactory::createStudent();
        $studentId = $this->insertTestData('users', $studentData);

        // Test với budget hợp lệ
        $validProject = ProjectFactory::create([
            'student_id' => $studentId,
            'budget' => 5000000
        ]);

        $this->insertTestData('projects', $validProject);

        $this->assertDatabaseHas('projects', [
            'budget' => 5000000
        ]);
    }
}
