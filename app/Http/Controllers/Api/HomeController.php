<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $baseQuery = ServiceRequest::query();
        if ($request->user()->type !== 'admin') {
            $baseQuery->where('user_id', $request->user()->id);
        }

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $inProgress = (clone $baseQuery)->where('status', 'in_progress')->count();
        $unresolved = (clone $baseQuery)->whereIn('status', ['pending', 'in_progress', 'urgent'])->count();

        $pct = function (int $value, int $den) {
            return $den > 0 ? round(($value / $den) * 100) : 0;
        };

        $byStatus = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'urgent'];

        $stats = [
            'total_requests' => [
                'total' => $total,
                'percent' => 100
            ],
        ];

        foreach ($statuses as $st) {
            $count = (int) ($byStatus[$st] ?? 0);
            $key = match ($st) {
                'pending' => 'pending_requests',
                'in_progress' => 'in_progress_requests',
                'completed' => 'completed_requests',
                'cancelled' => 'cancelled_requests',
                'urgent' => 'urgent_requests',
                default => $st,
            };
            $stats[$key] = [
                'total' => $count,
                'percent' => $pct($count, $total)
            ];
        }

        $stats['unresolved_requests'] = [
            'total' => $unresolved,
            'percent' => $pct($unresolved, $total)
        ];

        $categoryQuery = ServiceRequest::query();
        if ($request->user()->type !== 'admin') {
            $categoryQuery->where('user_id', $request->user()->id);
        }
        $categoriesRaw = $categoryQuery
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $categories = $categoriesRaw->map(function ($row) use ($total, $pct) {
            return [
                'category' => $row->category,
                'percent' => $pct((int)$row->total, $total)
            ];
        });

        $recentQuery = ServiceRequest::query();
        if ($request->user()->type !== 'admin') {
            $recentQuery->where('user_id', $request->user()->id);
        }
        $recent = $recentQuery
            ->with(['user:id,full_name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (ServiceRequest $sr) {
                $data = is_array($sr->request_data) ? $sr->request_data : (array) ($sr->getAttribute('request_data') ?? []);
                $address = $data['address'] ?? $data['endereco'] ?? null;
                return [
                    'id' => $sr->id,
                    'name' => optional($sr->user)->full_name,
                    'service' => $sr->service_title,
                    'address' => $address,
                    'status' => $sr->status,
                    'date' => optional($sr->created_at)->format('Y-m-d')
                ];
            });

        return response()->json([
            'stats' => $stats,
            'categories' => $categories,
            'recent_requests' => $recent,
        ]);
    }
}
