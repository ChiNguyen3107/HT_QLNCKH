<?php

namespace App\Http\Requests;

/**
 * Student Request validation
 */
class StudentRequest extends BaseRequest
{
    protected $rules = [
        'student_code' => 'required|string|max:20',
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'email' => 'email|max:100',
        'phone' => 'string|max:15',
        'class_code' => 'required|string|max:20',
        'department_code' => 'required|string|max:20'
    ];

    /**
     * Rules for creating student
     */
    public function createRules()
    {
        return [
            'student_code' => 'required|string|max:20',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'email|max:100',
            'phone' => 'string|max:15',
            'class_code' => 'required|string|max:20',
            'department_code' => 'required|string|max:20'
        ];
    }

    /**
     * Rules for updating student
     */
    public function updateRules()
    {
        return [
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'email' => 'email|max:100',
            'phone' => 'string|max:15',
            'class_code' => 'string|max:20',
            'department_code' => 'string|max:20'
        ];
    }

    /**
     * Rules for filtering students
     */
    public function filterRules()
    {
        return [
            'department' => 'string|max:20',
            'school_year' => 'string|max:10',
            'class' => 'string|max:20',
            'research_status' => 'in:active,completed,none',
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100'
        ];
    }

    /**
     * Validate for create
     */
    public function validateForCreate()
    {
        $this->rules = $this->createRules();
        return $this->validate();
    }

    /**
     * Validate for update
     */
    public function validateForUpdate()
    {
        $this->rules = $this->updateRules();
        return $this->validate();
    }

    /**
     * Validate for filter
     */
    public function validateForFilter()
    {
        $this->rules = $this->filterRules();
        return $this->validate();
    }
}
