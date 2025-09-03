<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Aws\Exception\AwsException;
use App\Services\UploadService;

class VideoController extends Controller
{
    private $uploadService;
    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska|max:51200',
            'folder' => 'string|max:255'
        ]);
        try {
            $file = $request->file('video');
            $folder = $request->input('folder', 'videos');
            $userId = auth()->id();
            if (!$userId && method_exists(auth(), 'guard')) { try { $g = auth()->guard('jwt')->user(); if ($g) { $userId = $g->id; } } catch (\Throwable $t) {} }
            if (!$userId && $request->user()) { $userId = $request->user()->id; }
            $result = $this->uploadService->upload($file, $folder, $userId);
            return response()->json([
                'success' => true,
                'message' => 'Video enviado com sucesso',
                'data' => [
                    'id' => $result['model']->id,
                    'filename' => $result['filename'],
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'size' => $result['size'],
                    'mime_type' => $result['mime_type']
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
            $this->uploadService->delete($request->input('path'));
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
            $items = $this->uploadService->list($folder);
            return response()->json(['success' => true,'data' => $items]);
        } catch (AwsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar videos: ' . $e->getMessage()
            ], 500);
        }
    }
}
