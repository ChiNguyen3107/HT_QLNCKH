<?php

namespace App\Http\Resources;

/**
 * Project Resource cho API responses
 */
class ProjectResource extends BaseResource
{
    public function toArray()
    {
        $response = parent::toArray();
        
        if ($this->data) {
            if (is_array($this->data) && isset($this->data[0])) {
                // Multiple projects
                $response['data'] = array_map([$this, 'formatProject'], $this->data);
            } else {
                // Single project
                $response['data'] = $this->formatProject($this->data);
            }
        }
        
        return $response;
    }

    private function formatProject($project)
    {
        return [
            'id' => $project['DT_MADT'] ?? $project['id'] ?? null,
            'project_code' => $project['DT_MADT'] ?? null,
            'title' => $project['DT_TEN'] ?? $project['title'] ?? null,
            'description' => $project['DT_MOTA'] ?? $project['description'] ?? null,
            'status' => $project['DT_TRANGTHAI'] ?? $project['status'] ?? null,
            'start_date' => $project['DT_NGAYBD'] ?? $project['start_date'] ?? null,
            'end_date' => $project['DT_NGAYKT'] ?? $project['end_date'] ?? null,
            'budget' => (float)($project['DT_KINHPHI'] ?? $project['budget'] ?? 0),
            'supervisor' => [
                'id' => $project['GV_MAGV'] ?? $project['supervisor']['id'] ?? null,
                'name' => $project['GV_HOTEN'] ?? $project['supervisor']['name'] ?? null,
                'email' => $project['GV_EMAIL'] ?? $project['supervisor']['email'] ?? null
            ],
            'members' => $this->formatMembers($project),
            'evaluation' => $this->formatEvaluation($project),
            'files' => $this->formatFiles($project),
            'created_at' => $project['created_at'] ?? null,
            'updated_at' => $project['updated_at'] ?? null
        ];
    }

    private function formatMembers($project)
    {
        if (isset($project['members']) && is_array($project['members'])) {
            return array_map(function($member) {
                return [
                    'id' => $member['SV_MASV'] ?? $member['id'] ?? null,
                    'name' => $member['SV_HOTEN'] ?? $member['name'] ?? null,
                    'role' => $member['role'] ?? 'member',
                    'joined_at' => $member['joined_at'] ?? null
                ];
            }, $project['members']);
        }
        
        return [];
    }

    private function formatEvaluation($project)
    {
        if (isset($project['evaluation'])) {
            return [
                'score' => (float)($project['evaluation']['score'] ?? 0),
                'status' => $project['evaluation']['status'] ?? 'pending',
                'evaluated_at' => $project['evaluation']['evaluated_at'] ?? null,
                'evaluator' => $project['evaluation']['evaluator'] ?? null
            ];
        }
        
        return null;
    }

    private function formatFiles($project)
    {
        if (isset($project['files']) && is_array($project['files'])) {
            return array_map(function($file) {
                return [
                    'id' => $file['id'] ?? null,
                    'name' => $file['name'] ?? null,
                    'type' => $file['type'] ?? null,
                    'size' => (int)($file['size'] ?? 0),
                    'url' => $file['url'] ?? null,
                    'uploaded_at' => $file['uploaded_at'] ?? null
                ];
            }, $project['files']);
        }
        
        return [];
    }
}
