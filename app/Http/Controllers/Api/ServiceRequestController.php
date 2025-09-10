<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        
        if ($request->user()->type !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }
        
        if ($request->filled('status')) {
            $statuses = is_array($request->status) 
                ? $request->status 
                : explode(',', $request->status);
            $query->whereIn('status', array_filter(array_map('trim', $statuses)));
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

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
        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
            'service_title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'request_data' => ['required', 'array'],
            'status' => ['sometimes', Rule::in(self::VALID_STATUSES)],
            'attachments' => ['sometimes', 'array']
        ]);

        $data = $validated;
        $data['user_id'] = $request->user()->id;
        $data['protocol_number'] = ServiceRequest::generateProtocolNumber();
        
        try {
            $attachmentUrls = $this->processAttachments($request, $data['user_id']);
            if (!empty($attachmentUrls)) {
                $data['attachments'] = $attachmentUrls;
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao processar anexos: ' . $e->getMessage()], 500);
        }

        $serviceRequest = ServiceRequest::create($data);

        return response()->json($serviceRequest, 201);
    }

    public function show(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        return response()->json($serviceRequest);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);

        $validated = $request->validate([
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'service_id' => ['sometimes', 'integer'],
            'service_title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:100'],
            'request_data' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::in(self::VALID_STATUSES)],
            'attachments' => ['sometimes', 'array'],
            'attachments_mode' => ['sometimes', Rule::in(self::ATTACHMENT_MODES)]
        ]);

        try {
            $payload = $validated;
            unset($payload['attachments'], $payload['attachments_mode']);
            
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

            $serviceRequest->update($payload);
            return response()->json($serviceRequest);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao processar anexos: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        $serviceRequest->delete();

        return response()->json(['message' => 'Service request deleted successfully']);
    }



    private function processAttachments(Request $request, int $userId): array
    {
        $allUrls = [];

        if ($request->has('attachments') && is_array($request->input('attachments'))) {
            foreach ($request->input('attachments') as $attachment) {
                if (is_string($attachment) && (str_starts_with($attachment, 'data:') || str_starts_with($attachment, '/9j/'))) {
                    $result = $this->uploadService->uploadFromBase64($attachment, self::UPLOAD_FOLDER, $userId);
                    $allUrls[] = $result['url'];
                }
            }
        }

        if ($request->hasFile('attachments')) {
            $results = $this->uploadService->uploadMany($request->file('attachments'), self::UPLOAD_FOLDER, $userId);
            $allUrls = array_merge($allUrls, array_map(fn($r) => $r['url'], $results));
        }

        return $allUrls;
    }


}