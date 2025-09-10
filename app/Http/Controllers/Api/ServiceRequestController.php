<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\Upload;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    private $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    public function index(Request $request)
    {
        $query = ServiceRequest::query();
        $user = $request->user();

        if ($user->type !== 'admin') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $statusParam = $request->query('status');

            if (is_array($statusParam)) {

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }
                $statuses = array_values(array_filter(array_map('strval', $statusParam)));
                if (!empty($statuses)) {
                    $query->whereIn('status', $statuses);
                }
            } else {
                $statusStr = trim((string) $statusParam);
                if ($statusStr !== '') {
                    if (strpos($statusStr, ',') !== false) {
                        $statuses = array_values(array_filter(array_map('trim', explode(',', $statusStr))));
                        if (!empty($statuses)) {
                            $query->whereIn('status', $statuses);
                        }
                    } else {
                        $query->where('status', $statusStr);
                    }
                }
            }
        }

    $serviceRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($serviceRequests);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => ['required', 'integer'],
            'service_title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'request_data' => ['required', 'array'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'attachments' => ['sometimes','array']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('attachments');
        $data['user_id'] = $request->user()->id;
        $data['protocol_number'] = ServiceRequest::generateProtocolNumber();
        
        if ($request->has('attachments') && is_array($request->input('attachments'))) {
            try {
                $attachments = $request->input('attachments');
                $allUrls = [];
                
                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && (strpos($attachment, 'data:') === 0 || strpos($attachment, '/9j/') === 0)) {
                        $result = $this->uploadService->uploadFromBase64($attachment, 'service_requests', $data['user_id']);
                        $allUrls[] = $result['url'];
                    }
                }
                
                if (!empty($allUrls)) {
                    $data['attachments'] = $allUrls;
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Erro ao processar anexos: '.$e->getMessage()], 500);
            }
        } elseif ($request->hasFile('attachments')) {
            try {
                $results = $this->uploadService->uploadMany($request->file('attachments'), 'service_requests', $data['user_id']);
                $data['attachments'] = array_map(fn($r) => $r['url'], $results);
            } catch (AwsException $e) {
                return response()->json(['message' => 'Erro ao enviar arquivo: '.$e->getMessage()], 500);
            }
        }
        $serviceRequest = ServiceRequest::create($data);

        return response()->json($serviceRequest, 201);
    }

    public function show($id)
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return response()->json(['message' => 'Service request not found'], 404);
        }

        return response()->json($serviceRequest);
    }

    public function update(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return response()->json(['message' => 'Service request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'service_id' => ['sometimes', 'integer'],
            'service_title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:100'],
            'request_data' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'attachments' => ['sometimes','array'],
            'attachments_mode' => ['sometimes','in:replace,append']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $mode = $request->input('attachments_mode', 'replace');
        $payload = $request->except(['attachments','attachments_mode']);
        $existing = is_array($serviceRequest->attachments) ? $serviceRequest->attachments : [];
        $newUrls = [];
        
        if ($request->has('attachments') && is_array($request->input('attachments'))) {
            try {
                $attachments = $request->input('attachments');
                
                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && (strpos($attachment, 'data:') === 0 || strpos($attachment, '/9j/') === 0)) {
                        $result = $this->uploadService->uploadFromBase64($attachment, 'service_requests', $serviceRequest->user_id);
                        $newUrls[] = $result['url'];
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Erro ao processar anexos: '.$e->getMessage()], 500);
            }
        } elseif ($request->hasFile('attachments')) {
            try {
                $results = $this->uploadService->uploadMany($request->file('attachments'), 'service_requests', $serviceRequest->user_id);
                $newUrls = array_merge($newUrls, array_map(fn($r) => $r['url'], $results));
            } catch (AwsException $e) {
                return response()->json(['message' => 'Erro ao enviar arquivo: '.$e->getMessage()], 500);
            }
        }
        
        if (!empty($newUrls)) {
            if ($mode === 'append') {
                $payload['attachments'] = array_values(array_merge($existing, $newUrls));
            } else {
                $payload['attachments'] = $newUrls;
            }
        }
        $serviceRequest->update($payload);

        return response()->json($serviceRequest);
    }

    public function destroy($id)
    {
        $serviceRequest = ServiceRequest::find($id);

        if (!$serviceRequest) {
            return response()->json(['message' => 'Service request not found'], 404);
        }

        $serviceRequest->delete();

        return response()->json(['message' => 'Service request deleted successfully']);
    }

}