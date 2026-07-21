<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'customer_id', 'source_table', 'source_id', 'source_reference', 'link_type', 'match_method', 'confidence', 'status', 'linked_at', 'metadata'])]
class CustomerIdentityLink extends Model
{
    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
