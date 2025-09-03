<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Str;
use App\Models\Upload;

class ImageController extends Controller
{
    private $s3Client;
    
    public function __construct()
    {
        $httpOptions = [
            'verify' => env('AWS_SSL_VERIFY', true),
        ];

        // Para desenvolvimento local, pode ser necessário desabilitar SSL verify
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:50240',
            'folder' => 'string|max:255'
        ]);

        try {
            $image = $request->file('image');
            $folder = $request->input('folder', 'images');
            
            $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
            
            $key = $folder . '/' . $filename;
            
            $result = $this->s3Client->putObject([
                'Bucket' => config('services.supabase.bucket'),
                'Key' => $key,
                'Body' => fopen($image->getPathname(), 'r'),
                'ContentType' => $image->getMimeType(),
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
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'data' => [
                    'id' => $upload->id,
                    'filename' => $filename,
                    'path' => $key,
                    'url' => $publicUrl,
                    'size' => $image->getSize(),
                    'mime_type' => $image->getMimeType()
                ]
            ], 201);

        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar imagem: ' . $e->getMessage()
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
                'message' => 'Imagem excluída com sucesso'
            ]);

        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $folder = $request->input('folder', 'images');

        try {
            $result = $this->s3Client->listObjects([
                'Bucket' => config('services.supabase.bucket'),
                'Prefix' => $folder . '/'
            ]);

            $images = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/' . config('services.supabase.bucket') . '/' . $object['Key'];
                    
                    $images[] = [
                        'key' => $object['Key'],
                        'size' => $object['Size'],
                        'last_modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                        'url' => $publicUrl
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $images
            ]);

        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

}