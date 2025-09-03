<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    private $s3Client;

    public function __construct()
    {
        $httpOptions = [
            'verify' => env('AWS_SSL_VERIFY', true),
        ];
        if (env('APP_ENV') === 'local' && !env('AWS_SSL_VERIFY', true)) {
            $httpOptions['verify'] = false;
        }
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('services.supabase.region'),
            'endpoint' => config('services.supabase.endpoint'),
            'credentials' => [
                'key' => config('services.supabase.access_key_id'),
                'secret' => config('services.supabase.secret_access_key'),
            ],
            'use_path_style_endpoint' => true,
            'http' => $httpOptions,
        ]);
    }
    public function index(Request $request)
    {
        $query = ServiceRequest::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $serviceRequests = $query->orderBy('created_at', 'desc')->get();

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
            'attachments' => ['sometimes','array'],
            'attachments.*' => ['file','max:204800','mimetypes:image/jpeg,image/png,image/jpg,image/gif,image/svg,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('attachments');
        $data['user_id'] = $request->user()->id;
        $attachmentsUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file->isValid()) { continue; }
                try {
                    $folder = 'service_requests';
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $key = $folder . '/' . $filename;
                    $this->s3Client->putObject([
                        'Bucket' => config('services.supabase.bucket'),
                        'Key' => $key,
                        'Body' => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/' . config('services.supabase.bucket') . '/' . $key;
                    $upload = Upload::create([
                        'user_id' => $data['user_id'],
                        'stored_name' => $filename,
                        'folder' => $folder,
                        'path' => $key,
                        'url' => $publicUrl,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ]);
                    $attachmentsUrls[] = $publicUrl;
                } catch (AwsException $e) {
                    return response()->json(['message' => 'Erro ao enviar arquivo: '.$e->getMessage()], 500);
                }
            }
        }
        if (!empty($attachmentsUrls)) { $data['attachments'] = $attachmentsUrls; }
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
            'attachments.*' => ['file','max:204800','mimetypes:image/jpeg,image/png,image/jpg,image/gif,image/svg,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska'],
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
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$file->isValid()) { continue; }
                try {
                    $folder = 'service_requests';
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $key = $folder . '/' . $filename;
                    $this->s3Client->putObject([
                        'Bucket' => config('services.supabase.bucket'),
                        'Key' => $key,
                        'Body' => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/' . config('services.supabase.bucket') . '/' . $key;
                    Upload::create([
                        'user_id' => $serviceRequest->user_id,
                        'stored_name' => $filename,
                        'folder' => $folder,
                        'path' => $key,
                        'url' => $publicUrl,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ]);
                    $newUrls[] = $publicUrl;
                } catch (AwsException $e) {
                    return response()->json(['message' => 'Erro ao enviar arquivo: '.$e->getMessage()], 500);
                }
            }
        }
        if ($request->hasFile('attachments')) {
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