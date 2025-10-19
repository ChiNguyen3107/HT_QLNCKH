<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;

/**
 * Factory cho tạo test data Notification
 */
class NotificationFactory
{
    protected static $faker;

    public static function create(array $attributes = []): array
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create('vi_VN');
        }

        $defaults = [
            'user_id' => 1, // Default user ID
            'title' => self::$faker->sentence(4),
            'message' => self::$faker->paragraphs(2, true),
            'type' => self::$faker->randomElement(['info', 'warning', 'error', 'success']),
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return array_merge($defaults, $attributes);
    }

    public static function createInfo(array $attributes = []): array
    {
        return self::create(array_merge(['type' => 'info'], $attributes));
    }

    public static function createWarning(array $attributes = []): array
    {
        return self::create(array_merge(['type' => 'warning'], $attributes));
    }

    public static function createError(array $attributes = []): array
    {
        return self::create(array_merge(['type' => 'error'], $attributes));
    }

    public static function createSuccess(array $attributes = []): array
    {
        return self::create(array_merge(['type' => 'success'], $attributes));
    }

    public static function createRead(array $attributes = []): array
    {
        return self::create(array_merge(['is_read' => true], $attributes));
    }

    public static function createUnread(array $attributes = []): array
    {
        return self::create(array_merge(['is_read' => false], $attributes));
    }

    public static function createMultiple(int $count, array $attributes = []): array
    {
        $notifications = [];
        for ($i = 0; $i < $count; $i++) {
            $notifications[] = self::create($attributes);
        }
        return $notifications;
    }
}
