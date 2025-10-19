<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;

/**
 * Factory cho tạo test data User
 */
class UserFactory
{
    protected static $faker;

    public static function create(array $attributes = []): array
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create('vi_VN');
        }

        $defaults = [
            'username' => self::$faker->unique()->userName,
            'email' => self::$faker->unique()->safeEmail,
            'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
            'full_name' => self::$faker->name,
            'role' => self::$faker->randomElement(['admin', 'teacher', 'student']),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return array_merge($defaults, $attributes);
    }

    public static function createAdmin(array $attributes = []): array
    {
        return self::create(array_merge(['role' => 'admin'], $attributes));
    }

    public static function createTeacher(array $attributes = []): array
    {
        return self::create(array_merge(['role' => 'teacher'], $attributes));
    }

    public static function createStudent(array $attributes = []): array
    {
        return self::create(array_merge(['role' => 'student'], $attributes));
    }

    public static function createMultiple(int $count, array $attributes = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::create($attributes);
        }
        return $users;
    }
}
