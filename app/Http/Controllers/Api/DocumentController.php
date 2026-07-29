<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.documents.unauthenticated'), [], 401);
        }

        $query = $user->documents();

        if ($request->has('document_type')) {
            $query->byType($request->input('document_type'));
        }

        if ($request->has('plot_id')) {
            $query->forPlot((int) $request->input('plot_id'));
        }

        $documents = $query->orderByDesc('created_at')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'original_name' => $doc->original_name,
                'mime_type' => $doc->mime_type,
                'size' => $doc->size,
                'size_formatted' => $doc->sizeFormatted(),
                'notes' => $doc->notes,
                'plot_id' => $doc->plot_id,
                'review_status' => $doc->review_status,
                'authenticity_score' => $doc->authenticity_score,
                'authenticity_notes' => $doc->authenticity_notes,
                'created_at' => $doc->created_at->toIso8601String(),
            ];
        });

        return $this->success(__('api.documents.list_fetched'), [
            'documents' => $documents,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'],
            'document_type' => ['required', 'string', 'in:sale_agreement,transfer_form,certificate_of_occupancy,survey_plan,identification,other'],
            'plot_id' => ['nullable', 'integer', 'exists:plots,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.documents.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.documents.unauthenticated'), [], 401);
        }

        $file = $request->file('file');
        $authenticity = app(\App\Services\DocumentAuthenticityService::class)
            ->evaluate($file, (string) $request->input('document_type'));

        $fileName = time() . '_' . $user->id . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('documents', $fileName, 'local');

        $document = Document::create([
            'user_id' => $user->id,
            'plot_id' => $request->input('plot_id'),
            'document_type' => $request->input('document_type'),
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'notes' => $request->input('notes'),
            'review_status' => $authenticity['review_status'],
            'authenticity_score' => $authenticity['score'],
            'authenticity_notes' => $authenticity['notes'],
            'file_hash' => $authenticity['file_hash'],
        ]);

        app(\App\Services\AuditLogService::class)->log(
            'document.upload',
            $user,
            'document',
            $document->id,
            ['score' => $authenticity['score'], 'status' => $authenticity['review_status']],
            $request,
        );

        return $this->success(__('api.documents.uploaded'), [
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'size_formatted' => $document->sizeFormatted(),
                'review_status' => $document->review_status,
                'authenticity_score' => $document->authenticity_score,
                'authenticity_notes' => $document->authenticity_notes,
                'created_at' => $document->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function download(Request $request, int $id): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.documents.unauthenticated'), [], 401);
        }

        $document = Document::query()
            ->where('id', $id)
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $document) {
            return $this->error(__('api.documents.not_found'), [], 404);
        }

        if (! Storage::disk('local')->exists($document->file_path)) {
            return $this->error(__('api.documents.file_not_found'), [], 404);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.documents.unauthenticated'), [], 401);
        }

        $document = Document::query()
            ->where('id', $id)
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $document) {
            return $this->error(__('api.documents.not_found'), [], 404);
        }

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return $this->success(__('api.documents.deleted'));
    }

    public function review(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return $this->error('Admin access only.', [], 403);
        }

        $validator = \Validator::make($request->all(), [
            'review_status' => ['required', 'string', 'in:approved,rejected,flagged,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.documents.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $document = Document::query()->find($id);
        if (! $document) {
            return $this->error(__('api.documents.not_found'), [], 404);
        }

        $document = app(\App\Services\DocumentAuthenticityService::class)->markReviewed(
            $document,
            (int) $user->id,
            (string) $request->input('review_status'),
            $request->input('notes'),
        );

        app(\App\Services\AuditLogService::class)->log(
            'document.review',
            $user,
            'document',
            $document->id,
            ['status' => $document->review_status],
            $request,
        );

        return $this->success('Document review updated.', [
            'document' => [
                'id' => $document->id,
                'review_status' => $document->review_status,
                'authenticity_notes' => $document->authenticity_notes,
                'reviewed_at' => $document->reviewed_at?->toIso8601String(),
            ],
        ]);
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => (object) [],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    private function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
