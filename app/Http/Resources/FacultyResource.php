<?php

namespace App\Http\Resources;

/**
 * Faculty Resource cho API responses
 */
class FacultyResource extends BaseResource
{
    public function toArray()
    {
        $response = parent::toArray();
        
        if ($this->data) {
            if (is_array($this->data) && isset($this->data[0])) {
                // Multiple faculties
                $response['data'] = array_map([$this, 'formatFaculty'], $this->data);
            } else {
                // Single faculty
                $response['data'] = $this->formatFaculty($this->data);
            }
        }
        
        return $response;
    }

    private function formatFaculty($faculty)
    {
        return [
            'id' => $faculty['DV_MADV'] ?? $faculty['id'] ?? null,
            'code' => $faculty['DV_MADV'] ?? null,
            'name' => $faculty['DV_TENDV'] ?? $faculty['name'] ?? null,
            'description' => $faculty['DV_MOTA'] ?? $faculty['description'] ?? null,
            'dean' => [
                'id' => $faculty['dean']['id'] ?? null,
                'name' => $faculty['dean']['name'] ?? null,
                'email' => $faculty['dean']['email'] ?? null
            ],
            'student_count' => (int)($faculty['student_count'] ?? 0),
            'teacher_count' => (int)($faculty['teacher_count'] ?? 0),
            'project_count' => (int)($faculty['project_count'] ?? 0),
            'created_at' => $faculty['created_at'] ?? null,
            'updated_at' => $faculty['updated_at'] ?? null
        ];
    }
}
