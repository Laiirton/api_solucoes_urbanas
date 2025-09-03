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