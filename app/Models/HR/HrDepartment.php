<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrDepartment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'parent_id', 'code', 'name', 'short_name', 'type', 'description',
        'mandate', 'responsibilities', 'cost_centre', 'head_position_id', 'status',
        'effective_from', 'review_date', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'review_date' => 'date',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(HrPosition::class, 'department_id');
    }

    public function headPosition(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'head_position_id');
    }
}
