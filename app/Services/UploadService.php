<?php
namespace App\Services;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Models\Upload;
class UploadService
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
    public function upload(UploadedFile $file, string $folder, ?int $userId): array
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $key = $folder.'/'.$filename;
        $this->s3Client->putObject([
            'Bucket' => config('services.supabase.bucket'),
            'Key' => $key,
            'Body' => fopen($file->getPathname(), 'r'),
            'ContentType' => $file->getMimeType(),
        ]);
        $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/'.config('services.supabase.bucket').'/'.$key;
        $upload = Upload::create([
            'user_id' => $userId,
            'stored_name' => $filename,
            'folder' => $folder,
            'path' => $key,
            'url' => $publicUrl,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ]);
        return [
            'model' => $upload,
            'url' => $publicUrl,
            'path' => $key,
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ];
    }
    public function uploadMany(array $files, string $folder, ?int $userId): array
    {
        $results = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $results[] = $this->upload($file, $folder, $userId);
            }
        }
        return $results;
    }

    public function uploadFromBase64(string $base64Data, string $folder, ?int $userId, string $extension = 'jpg'): array
    {
        if (strpos($base64Data, 'data:') === 0) {
            preg_match('/data:([a-zA-Z0-9]+\/[a-zA-Z0-9-.+]+).*,(.*)/', $base64Data, $matches);
            if (count($matches) >= 3) {
                $mimeType = $matches[1];
                $base64Data = $matches[2];
                $extension = $this->getExtensionFromMimeType($mimeType);
            }
        }

        $decodedData = base64_decode($base64Data);
        if ($decodedData === false) {
            throw new \InvalidArgumentException('Invalid base64 data');
        }

        $filename = Str::uuid().'.'.$extension;
        $key = $folder.'/'.$filename;

        $this->s3Client->putObject([
            'Bucket' => config('services.supabase.bucket'),
            'Key' => $key,
            'Body' => $decodedData,
            'ContentType' => $this->getMimeTypeFromExtension($extension),
        ]);

        $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/'.config('services.supabase.bucket').'/'.$key;

        $upload = Upload::create([
            'user_id' => $userId,
            'stored_name' => $filename,
            'folder' => $folder,
            'path' => $key,
            'url' => $publicUrl,
            'size' => strlen($decodedData),
            'mime_type' => $this->getMimeTypeFromExtension($extension)
        ]);

        return [
            'model' => $upload,
            'url' => $publicUrl,
            'path' => $key,
            'filename' => $filename,
            'size' => strlen($decodedData),
            'mime_type' => $this->getMimeTypeFromExtension($extension)
        ];
    }

    public function uploadManyFromBase64(array $base64Images, string $folder, ?int $userId): array
    {
        $results = [];
        foreach ($base64Images as $base64Image) {
            if (is_string($base64Image) && !empty($base64Image)) {
                try {
                    $results[] = $this->uploadFromBase64($base64Image, $folder, $userId);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return $results;
    }

    private function getExtensionFromMimeType(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv'
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }

    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska'
        ];

        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
    public function delete(string $path): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => config('services.supabase.bucket'),
            'Key' => $path
        ]);
    }
    public function list(string $folder): array
    {
        $result = $this->s3Client->listObjects([
            'Bucket' => config('services.supabase.bucket'),
            'Prefix' => $folder.'/'
        ]);
        $items = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                $publicUrl = 'https://fhvalhsxiyqlauxqfibe.supabase.co/storage/v1/object/public/'.config('services.supabase.bucket').'/'.$object['Key'];
                $items[] = [
                    'key' => $object['Key'],
                    'size' => $object['Size'],
                    'last_modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                    'url' => $publicUrl
                ];
            }
        }
        return $items;
    }
}