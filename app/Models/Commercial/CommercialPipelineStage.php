<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;

class CommercialPipelineStage extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'display_order', 'default_probability', 'required_fields',
        'required_documents', 'requires_approval', 'exit_criteria', 'maximum_days',
        'color', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
            'required_documents' => 'array',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
            'default_probability' => 'integer',
        ];
    }
}
