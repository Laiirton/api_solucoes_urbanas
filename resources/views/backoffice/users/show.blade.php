<x-layouts.app :title="__('Perfil do Usuário')">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('backoffice.users.index') }}" 
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Perfil do Usuário</h1>
                </div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $user->full_name }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('backoffice.users.edit', $user->id) }}" 
                   class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md text-sm font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Editar
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- User Details -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informações Pessoais</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->full_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->username }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->cpf ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Nascimento</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->birth_date ? $user->birth_date->format('d/m/Y') : 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->type ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Registro</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Última Atualização</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->updated_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Requests -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Solicitações de Serviço ({{ $user->serviceRequests->count() }})</h2>
                    </div>
                    <div class="p-6">
                        @if($user->serviceRequests->count() > 0)
                            <div class="space-y-4">
                                @foreach($user->serviceRequests->take(10) as $request)
                                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $request->service_title }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                Protocolo: {{ $request->protocol_number }}
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                    'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                    'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                ];
                                                $statusLabels = [
                                                    'pending' => 'Pendente',
                                                    'in_progress' => 'Em Andamento',
                                                    'completed' => 'Concluída',
                                                    'cancelled' => 'Cancelada',
                                                    'urgent' => 'Urgente',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $statusLabels[$request->status] ?? ucfirst($request->status) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $request->created_at->diffForHumans() }}
                                            </span>
                                            <a href="{{ route('backoffice.service-requests.show', $request->id) }}" 
                                               class="text-blue-600 dark:text-blue-400 hover:underline text-xs">
                                                Ver detalhes
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                                @if($user->serviceRequests->count() > 10)
                                    <div class="text-center pt-4">
                                        <a href="{{ route('backoffice.service-requests.index', ['search' => $user->email]) }}" 
                                           class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                            Ver todas as {{ $user->serviceRequests->count() }} solicitações
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-8">Nenhuma solicitação encontrada.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- User Avatar -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 text-center">
                        <div class="w-24 h-24 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full mx-auto flex items-center justify-center text-white text-2xl font-bold mb-4">
                            {{ $user->initials() }}
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $user->full_name }}</h3>
                        <p class="text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        @if($user->type)
                            <span class="inline-flex px-2 py-1 mt-2 text-xs font-semibold rounded-full 
                                {{ $user->type === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                {{ ucfirst($user->type) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Estatísticas</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total de Solicitações:</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->serviceRequests->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Pendentes:</span>
                                <span class="text-sm font-medium text-yellow-600">{{ $user->serviceRequests->where('status', 'pending')->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Em Andamento:</span>
                                <span class="text-sm font-medium text-blue-600">{{ $user->serviceRequests->where('status', 'in_progress')->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Concluídas:</span>
                                <span class="text-sm font-medium text-green-600">{{ $user->serviceRequests->where('status', 'completed')->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Uploads Recentes</h2>
                    </div>
                    <div class="p-6">
                        @if($uploads->count() > 0)
                            <div class="space-y-3">
                                @foreach($uploads->take(5) as $upload)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                            <div>
                                                <p class="text-xs font-medium text-gray-900 dark:text-white">
                                                    {{ Str::limit($upload->stored_name, 20) }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $upload->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                @if($uploads->count() > 5)
                                    <div class="text-center pt-2">
                                        <a href="{{ route('backoffice.uploads.index', ['search' => $user->email]) }}" 
                                           class="text-blue-600 dark:text-blue-400 hover:underline text-xs">
                                            Ver todos ({{ $uploads->count() }})
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center text-sm">Nenhum upload encontrado.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>