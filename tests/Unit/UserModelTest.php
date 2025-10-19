<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Factories\UserFactory;

/**
 * Unit tests cho User Model
 */
class UserModelTest extends TestCase
{
    public function testCanCreateUser(): void
    {
        $userData = UserFactory::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'full_name' => 'Test User'
        ]);

        $userId = $this->insertTestData('users', $userData);

        $this->assertGreaterThan(0, $userId);
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);
    }

    public function testCanCreateAdminUser(): void
    {
        $adminData = UserFactory::createAdmin([
            'username' => 'admin',
            'email' => 'admin@example.com'
        ]);

        $userId = $this->insertTestData('users', $adminData);

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'role' => 'admin'
        ]);
    }

    public function testCanCreateTeacherUser(): void
    {
        $teacherData = UserFactory::createTeacher([
            'username' => 'teacher',
            'email' => 'teacher@example.com'
        ]);

        $this->insertTestData('users', $teacherData);

        $this->assertDatabaseHas('users', [
            'username' => 'teacher',
            'role' => 'teacher'
        ]);
    }

    public function testCanCreateStudentUser(): void
    {
        $studentData = UserFactory::createStudent([
            'username' => 'student',
            'email' => 'student@example.com'
        ]);

        $this->insertTestData('users', $studentData);

        $this->assertDatabaseHas('users', [
            'username' => 'student',
            'role' => 'student'
        ]);
    }

    public function testPasswordHashIsGenerated(): void
    {
        $userData = UserFactory::create();
        
        $this->assertNotEmpty($userData['password_hash']);
        $this->assertStringStartsWith('$2y$', $userData['password_hash']);
    }

    public function testUserDefaults(): void
    {
        $userData = UserFactory::create();

        $this->assertTrue($userData['is_active']);
        $this->assertNotEmpty($userData['created_at']);
        $this->assertNotEmpty($userData['updated_at']);
    }

    public function testCanCreateMultipleUsers(): void
    {
        $users = UserFactory::createMultiple(5);

        $this->assertCount(5, $users);
        
        foreach ($users as $user) {
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('password_hash', $user);
        }
    }

    public function testUserRoleValidation(): void
    {
        $validRoles = ['admin', 'teacher', 'student'];
        
        foreach ($validRoles as $role) {
            $userData = UserFactory::create(['role' => $role]);
            $this->assertEquals($role, $userData['role']);
        }
    }
}
