<?php

namespace App\Http\Resources;

/**
 * Student Resource cho API responses
 */
class StudentResource extends BaseResource
{
    public function toArray()
    {
        $response = parent::toArray();
        
        if ($this->data) {
            if (is_array($this->data) && isset($this->data[0])) {
                // Multiple students
                $response['data'] = array_map([$this, 'formatStudent'], $this->data);
            } else {
                // Single student
                $response['data'] = $this->formatStudent($this->data);
            }
        }
        
        return $response;
    }

    private function formatStudent($student)
    {
        return [
            'id' => $student['SV_MASV'] ?? $student['id'] ?? null,
            'student_code' => $student['SV_MASV'] ?? null,
            'full_name' => $student['SV_HOTEN'] ?? $student['full_name'] ?? null,
            'first_name' => $student['SV_TENSV'] ?? $student['first_name'] ?? null,
            'last_name' => $student['SV_HOSV'] ?? $student['last_name'] ?? null,
            'class_name' => $student['LOP_TEN'] ?? $student['class_name'] ?? null,
            'class_code' => $student['LOP_MA'] ?? $student['class_code'] ?? null,
            'department_name' => $student['DV_TENDV'] ?? $student['department_name'] ?? null,
            'department_code' => $student['DV_MADV'] ?? $student['department_code'] ?? null,
            'email' => $student['SV_EMAIL'] ?? $student['email'] ?? null,
            'phone' => $student['SV_SDT'] ?? $student['phone'] ?? null,
            'project_count' => (int)($student['project_count'] ?? 0),
            'completed_project_count' => (int)($student['completed_project_count'] ?? 0),
            'research_status' => $this->getResearchStatus($student),
            'created_at' => $student['created_at'] ?? null,
            'updated_at' => $student['updated_at'] ?? null
        ];
    }

    private function getResearchStatus($student)
    {
        $projectCount = (int)($student['project_count'] ?? 0);
        $completedCount = (int)($student['completed_project_count'] ?? 0);
        
        if ($projectCount === 0) {
            return 'none';
        } elseif ($completedCount > 0) {
            return 'completed';
        } else {
            return 'active';
        }
    }
}
