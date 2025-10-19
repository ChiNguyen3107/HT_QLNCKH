<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;

/**
 * Factory cho tạo test data Project
 */
class ProjectFactory
{
    protected static $faker;

    public static function create(array $attributes = []): array
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create('vi_VN');
        }

        $defaults = [
            'title' => self::$faker->sentence(6),
            'description' => self::$faker->paragraphs(3, true),
            'student_id' => 1, // Default student ID
            'supervisor_id' => 2, // Default supervisor ID
            'status' => self::$faker->randomElement(['pending', 'approved', 'rejected', 'completed']),
            'budget' => self::$faker->randomFloat(2, 1000000, 50000000),
            'start_date' => self::$faker->date('Y-m-d'),
            'end_date' => self::$faker->date('Y-m-d', '+1 year'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return array_merge($defaults, $attributes);
    }

    public static function createPending(array $attributes = []): array
    {
        return self::create(array_merge(['status' => 'pending'], $attributes));
    }

    public static function createApproved(array $attributes = []): array
    {
        return self::create(array_merge(['status' => 'approved'], $attributes));
    }

    public static function createRejected(array $attributes = []): array
    {
        return self::create(array_merge(['status' => 'rejected'], $attributes));
    }

    public static function createCompleted(array $attributes = []): array
    {
        return self::create(array_merge(['status' => 'completed'], $attributes));
    }

    public static function createMultiple(int $count, array $attributes = []): array
    {
        $projects = [];
        for ($i = 0; $i < $count; $i++) {
            $projects[] = self::create($attributes);
        }
        return $projects;
    }
}
