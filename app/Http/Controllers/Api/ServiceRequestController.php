<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Aws\Exception\AwsException;

class ServiceRequestController extends Controller
{
    private const DEFAULT_PER_PAGE = 10;
    private const VALID_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled', 'urgent'];
    private const ATTACHMENT_MODES = ['replace', 'append'];
    private const UPLOAD_FOLDER = 'service_requests';

    private UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::query();
        
        $this->applyUserFilter($query, $request->user());
        $this->applyStatusFilter($query, $request);
        $this->applyCategoryFilter($query, $request);

        $serviceRequests = $query->orderBy('created_at', 'desc')
                                ->paginate(self::DEFAULT_PER_PAGE);

        return response()->json([
            'data' => $serviceRequests->items(),
            'pagination' => [
                'current_page' => $serviceRequests->currentPage(),
                'last_page' => $serviceRequests->lastPage(),
                'per_page' => $serviceRequests->perPage(),
                'total' => $serviceRequests->total(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->getStoreValidator($request);
        
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $this->prepareStoreData($request);
        
        try {
            $attachmentUrls = $this->processAttachments($request, $data['user_id']);
            if (!empty($attachmentUrls)) {
                $data['attachments'] = $attachmentUrls;
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar anexos: ' . $e->getMessage(), 500);
        }

        $serviceRequest = ServiceRequest::create($data);

        return response()->json($serviceRequest, 201);
    }

    public function show(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFoundResponse('Service request not found');
        }

        return response()->json($serviceRequest);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFoundResponse('Service request not found');
        }

        $validator = $this->getUpdateValidator($request);
        
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $payload = $this->prepareUpdateData($request, $serviceRequest);
            $serviceRequest->update($payload);

            return response()->json($serviceRequest);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar anexos: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return $this->notFoundResponse('Service request not found');
        }

        $serviceRequest->delete();

        return response()->json(['message' => 'Service request deleted successfully']);
    }

    private function applyUserFilter($query, $user): void
    {
        if ($user->type !== 'admin') {
            $query->where('user_id', $user->id);
        }
    }

    private function applyStatusFilter($query, Request $request): void
    {
        if (!$request->has('status')) {
            return;
        }

        $statusParam = $request->query('status');
        $statuses = $this->parseStatusParameter($statusParam);

        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }
    }

    private function parseStatusParameter($statusParam): array
    {
        if (is_array($statusParam)) {
            return array_values(array_filter(array_map('strval', $statusParam)));
        }

        $statusStr = trim((string) $statusParam);
        if ($statusStr === '') {
            return [];
        }

        if (strpos($statusStr, ',') !== false) {
            return array_values(array_filter(array_map('trim', explode(',', $statusStr))));
        }

        return [$statusStr];
    }

    private function applyCategoryFilter($query, Request $request): void
    {
        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }
    }

    private function getStoreValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'service_id' => ['required', 'integer'],
            'service_title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'request_data' => ['required', 'array'],
            'status' => ['sometimes', Rule::in(self::VALID_STATUSES)],
            'attachments' => ['sometimes', 'array']
        ]);
    }

    private function getUpdateValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'service_id' => ['sometimes', 'integer'],
            'service_title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:100'],
            'request_data' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::in(self::VALID_STATUSES)],
            'attachments' => ['sometimes', 'array'],
            'attachments_mode' => ['sometimes', Rule::in(self::ATTACHMENT_MODES)]
        ]);
    }

    private function prepareStoreData(Request $request): array
    {
        $data = $request->except('attachments');
        $data['user_id'] = $request->user()->id;
        $data['protocol_number'] = ServiceRequest::generateProtocolNumber();

        return $data;
    }

    private function prepareUpdateData(Request $request, ServiceRequest $serviceRequest): array
    {
        $payload = $request->except(['attachments', 'attachments_mode']);
        
        if ($request->has('attachments')) {
            $newUrls = $this->processAttachments($request, $serviceRequest->user_id);
            
            if (!empty($newUrls)) {
                $mode = $request->input('attachments_mode', 'replace');
                $existing = is_array($serviceRequest->attachments) ? $serviceRequest->attachments : [];

                $payload['attachments'] = $mode === 'append' 
                    ? array_values(array_merge($existing, $newUrls))
                    : $newUrls;
            }
        }

        return $payload;
    }

    private function processAttachments(Request $request, int $userId): array
    {
        $allUrls = [];

        if ($request->has('attachments') && is_array($request->input('attachments'))) {
            $allUrls = array_merge($allUrls, $this->processBase64Attachments($request->input('attachments'), $userId));
        }

        if ($request->hasFile('attachments')) {
            $allUrls = array_merge($allUrls, $this->processFileAttachments($request->file('attachments'), $userId));
        }

        return $allUrls;
    }

    private function processBase64Attachments(array $attachments, int $userId): array
    {
        $urls = [];

        foreach ($attachments as $attachment) {
            if ($this->isBase64Attachment($attachment)) {
                $result = $this->uploadService->uploadFromBase64($attachment, self::UPLOAD_FOLDER, $userId);
                $urls[] = $result['url'];
            }
        }

        return $urls;
    }

    private function processFileAttachments($files, int $userId): array
    {
        $results = $this->uploadService->uploadMany($files, self::UPLOAD_FOLDER, $userId);
        return array_map(fn($r) => $r['url'], $results);
    }

    private function isBase64Attachment($attachment): bool
    {
        return is_string($attachment) && 
               (strpos($attachment, 'data:') === 0 || strpos($attachment, '/9j/') === 0);
    }

    private function validationErrorResponse($validator): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }

    private function errorResponse(string $message, int $status = 500): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}