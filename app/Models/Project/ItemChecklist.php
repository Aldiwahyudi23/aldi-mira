<?php

namespace App\Models\Project;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_item_id',
        'task_description',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relasi: Checklist terikat pada satu Project Item (misal: Item "Dokumen Nikah")
    public function item()
    {
        return $this->belongsTo(ProjectItem::class, 'project_item_id');
    }
}
