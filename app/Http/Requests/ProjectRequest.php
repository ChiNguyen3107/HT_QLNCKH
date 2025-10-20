<?php

namespace App\Http\Requests;

/**
 * Project Request validation
 */
class ProjectRequest extends BaseRequest
{
    /**
     * Rules for creating project
     */
    public function createRules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'string|max:1000',
            'supervisor_id' => 'required|string|max:20',
            'start_date' => 'required|string',
            'end_date' => 'required|string',
            'budget' => 'numeric|min:0',
            'status' => 'in:Đang thực hiện,Đã hoàn thành,Tạm dừng,Hủy bỏ'
        ];
    }

    /**
     * Rules for updating project
     */
    public function updateRules()
    {
        return [
            'title' => 'string|max:255',
            'description' => 'string|max:1000',
            'supervisor_id' => 'string|max:20',
            'start_date' => 'string',
            'end_date' => 'string',
            'budget' => 'numeric|min:0',
            'status' => 'in:Đang thực hiện,Đã hoàn thành,Tạm dừng,Hủy bỏ'
        ];
    }

    /**
     * Rules for filtering projects
     */
    public function filterRules()
    {
        return [
            'status' => 'in:Đang thực hiện,Đã hoàn thành,Tạm dừng,Hủy bỏ',
            'supervisor_id' => 'string|max:20',
            'student_id' => 'string|max:20',
            'department' => 'string|max:20',
            'year' => 'integer|min:2000|max:2100',
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100'
        ];
    }

    /**
     * Rules for adding member
     */
    public function addMemberRules()
    {
        return [
            'student_id' => 'required|string|max:20',
            'role' => 'string|max:50'
        ];
    }

    /**
     * Rules for evaluation
     */
    public function evaluationRules()
    {
        return [
            'score' => 'required|numeric|min:0|max:10',
            'comment' => 'string|max:1000',
            'criteria' => 'array'
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

    /**
     * Validate for add member
     */
    public function validateForAddMember()
    {
        $this->rules = $this->addMemberRules();
        return $this->validate();
    }

    /**
     * Validate for evaluation
     */
    public function validateForEvaluation()
    {
        $this->rules = $this->evaluationRules();
        return $this->validate();
    }
}
