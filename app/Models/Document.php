<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plot_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'notes',
        'review_status',
        'authenticity_score',
        'authenticity_notes',
        'file_hash',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'authenticity_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plot()
    {
        return $this->belongsTo(Plot::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeForPlot($query, int $plotId)
    {
        return $query->where('plot_id', $plotId);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    public function sizeFormatted(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
