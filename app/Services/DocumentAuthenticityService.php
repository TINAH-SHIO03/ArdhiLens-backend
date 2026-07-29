<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentAuthenticityService
{
    /**
     * Score document authenticity heuristics and set review status.
     *
     * @return array{score: int, notes: string, review_status: string, file_hash: string}
     */
    public function evaluate(UploadedFile $file, string $documentType): array
    {
        $bytes = file_get_contents($file->getRealPath()) ?: '';
        $hash = hash('sha256', $bytes);
        $score = 40;
        $notes = [];

        $mime = (string) $file->getMimeType();
        $allowed = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (in_array($mime, $allowed, true)) {
            $score += 20;
            $notes[] = 'Accepted MIME type.';
        } else {
            $notes[] = 'Unusual MIME type.';
        }

        $size = $file->getSize() ?: 0;
        if ($size >= 20_000 && $size <= 8_000_000) {
            $score += 15;
            $notes[] = 'File size within expected range.';
        } elseif ($size < 5_000) {
            $score -= 15;
            $notes[] = 'File suspiciously small.';
        }

        $duplicate = Document::query()->where('file_hash', $hash)->exists();
        if ($duplicate) {
            $score -= 25;
            $notes[] = 'Duplicate file hash previously uploaded.';
        } else {
            $score += 10;
            $notes[] = 'Unique content hash.';
        }

        if (in_array($documentType, ['certificate_of_occupancy', 'survey_plan', 'sale_agreement'], true)) {
            $score += 10;
            $notes[] = 'High-value document type submitted.';
        }

        $score = max(0, min(100, $score));
        $reviewStatus = match (true) {
            $score >= 75 => 'auto_approved',
            $score >= 45 => 'pending',
            default => 'flagged',
        };

        return [
            'score' => $score,
            'notes' => implode(' ', $notes),
            'review_status' => $reviewStatus,
            'file_hash' => $hash,
        ];
    }

    public function markReviewed(Document $document, int $adminId, string $status, ?string $notes = null): Document
    {
        $document->update([
            'review_status' => $status,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'authenticity_notes' => $notes ?? $document->authenticity_notes,
        ]);

        return $document->refresh();
    }
}
