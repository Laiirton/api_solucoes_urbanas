<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;
use App\Models\Upload;

class VideoController extends Controller
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

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska|max:204800',
            'folder' => 'string|max:255'
        ]);
        try {
            $file = $request->file('video');
            $folder = $request->input('folder', 'videos');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $key = $folder . '/' . $filename;
            $this->s3Client->putObject([
                'Bucket' => config('services.supabase.bucket'),
                'Key' => $key,
                'Body' => fopen($file->getPathname(), 'r'),
                'ContentType' => $file->getMimeType(),
            ]);
            $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/' . config('services.supabase.bucket') . '/' . $key;
            $userId = auth()->id();
            if (!$userId && method_exists(auth(), 'guard')) {
                try { $guardUser = auth()->guard('jwt')->user(); if ($guardUser) { $userId = $guardUser->id; } } catch (\Throwable $t) {}
            }
            if (!$userId && $request->user()) { $userId = $request->user()->id; }
            $upload = Upload::create([
                'user_id' => $userId,
                'stored_name' => $filename,
                'folder' => $folder,
                'path' => $key,
                'url' => $publicUrl,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Video enviado com sucesso',
                'data' => [
                    'id' => $upload->id,
                    'filename' => $filename,
                    'path' => $key,
                    'url' => $publicUrl,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]
            ], 201);
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar video: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);
        try {
            $this->s3Client->deleteObject([
                'Bucket' => config('services.supabase.bucket'),
                'Key' => $request->input('path')
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Video excluido com sucesso'
            ]);
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir video: ' . $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $folder = $request->input('folder', 'videos');
        try {
            $result = $this->s3Client->listObjects([
                'Bucket' => config('services.supabase.bucket'),
                'Prefix' => $folder . '/'
            ]);
            $items = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/' . config('services.supabase.bucket') . '/' . $object['Key'];
                    $items[] = [
                        'key' => $object['Key'],
                        'size' => $object['Size'],
                        'last_modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                        'url' => $publicUrl
                    ];
                }
            }
            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar videos: ' . $e->getMessage()
            ], 500);
        }
    }
}
