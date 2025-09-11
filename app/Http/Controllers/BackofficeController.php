<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\Request;

class BackofficeController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_service_requests' => ServiceRequest::count(),
            'total_uploads' => Upload::count(),
            'pending_requests' => ServiceRequest::where('status', 'pending')->count(),
            'in_progress_requests' => ServiceRequest::where('status', 'in_progress')->count(),
            'completed_requests' => ServiceRequest::where('status', 'completed')->count(),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_requests' => ServiceRequest::with('user')->latest()->take(10)->get(),
        ];

        return view('backoffice.dashboard', compact('stats'));
    }

    public function serviceRequests(Request $request)
    {
        $query = ServiceRequest::with('user');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('protocol_number', 'like', "%{$search}%")
                  ->orWhere('service_title', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('full_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $serviceRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('backoffice.service-requests.index', compact('serviceRequests'));
    }

    public function showServiceRequest($id)
    {
        $serviceRequest = ServiceRequest::with('user')->findOrFail($id);
        return view('backoffice.service-requests.show', compact('serviceRequest'));
    }

    public function updateServiceRequestStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled,urgent'
        ]);

        $serviceRequest = ServiceRequest::findOrFail($id);
        $serviceRequest->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status atualizado com sucesso!');
    }

    public function uploads(Request $request)
    {
        $query = Upload::with('user');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('stored_name', 'like', "%{$search}%")
                  ->orWhere('mime_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('full_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('mime_type')) {
            $query->where('mime_type', 'like', $request->mime_type . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $uploads = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('backoffice.uploads.index', compact('uploads'));
    }

    public function users(Request $request)
    {
        $query = User::withCount(['serviceRequests']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('backoffice.users.index', compact('users'));
    }

    public function showUser($id)
    {
        $user = User::with(['serviceRequests' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        $uploads = Upload::where('user_id', $id)->orderBy('created_at', 'desc')->get();

        return view('backoffice.users.show', compact('user', 'uploads'));
    }

    public function createUser()
    {
        return view('backoffice.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'full_name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14|unique:users',
            'birth_date' => 'nullable|date',
            'type' => 'nullable|string|max:255',
        ]);

        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'full_name' => $request->full_name,
            'cpf' => $request->cpf,
            'birth_date' => $request->birth_date,
            'type' => $request->type,
        ]);

        return redirect()->route('backoffice.users.index')->with('success', 'Usuário criado com sucesso!');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('backoffice.users.edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'full_name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14|unique:users,cpf,' . $id,
            'birth_date' => 'nullable|date',
            'type' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'username' => $request->username,
            'email' => $request->email,
            'full_name' => $request->full_name,
            'cpf' => $request->cpf,
            'birth_date' => $request->birth_date,
            'type' => $request->type,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $user->update($updateData);

        return redirect()->route('backoffice.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar se o usuário tem solicitações de serviço
        if ($user->serviceRequests()->count() > 0) {
            return redirect()->back()->with('error', 'Não é possível excluir um usuário que possui solicitações de serviço.');
        }

        $user->delete();

        return redirect()->route('backoffice.users.index')->with('success', 'Usuário excluído com sucesso!');
    }
}