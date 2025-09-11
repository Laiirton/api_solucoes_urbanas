<x-layouts.app :title="__('Detalhes da Solicitação')">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('backoffice.service-requests.index') }}" 
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Detalhes da Solicitação</h1>
                </div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Protocolo: {{ $serviceRequest->protocol_number }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Service Request Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Informações da Solicitação</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título do Serviço</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $serviceRequest->service_title }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $serviceRequest->category ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ID do Serviço</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $serviceRequest->service_id ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Criação</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $serviceRequest->created_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Data -->
                @if($serviceRequest->request_data)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dados da Solicitação</h2>
                        </div>
                        <div class="p-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($serviceRequest->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Attachments -->
                @if($serviceRequest->attachments)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Anexos</h2>
                        </div>
                        <div class="p-6">
                            @if(is_array($serviceRequest->attachments) && count($serviceRequest->attachments) > 0)
                                <div class="space-y-3">
                                    @foreach($serviceRequest->attachments as $attachment)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ is_string($attachment) ? basename($attachment) : (isset($attachment['name']) ? $attachment['name'] : 'Arquivo') }}
                                                    </p>
                                                    @if(is_array($attachment) && isset($attachment['size']))
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ number_format($attachment['size'] / 1024, 2) }} KB
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(is_string($attachment))
                                                <a href="{{ $attachment }}" 
                                                   target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm">
                                                    Visualizar
                                                </a>
                                            @elseif(is_array($attachment) && isset($attachment['url']))
                                                <a href="{{ $attachment['url'] }}" 
                                                   target="_blank" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm">
                                                    Visualizar
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Nenhum anexo encontrado.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Status</h2>
                    </div>
                    <div class="p-6">
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
                        <div class="mb-4">
                            <span class="inline-flex px-3 py-2 text-sm font-semibold rounded-full {{ $statusColors[$serviceRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$serviceRequest->status] ?? ucfirst($serviceRequest->status) }}
                            </span>
                        </div>

                        <!-- Status Update Form -->
                        <form action="{{ route('backoffice.service-requests.update-status', $serviceRequest->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="space-y-3">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alterar Status</label>
                                <select id="status" 
                                        name="status" 
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    <option value="pending" {{ $serviceRequest->status === 'pending' ? 'selected' : '' }}>Pendente</option>
                                    <option value="in_progress" {{ $serviceRequest->status === 'in_progress' ? 'selected' : '' }}>Em Andamento</option>
                                    <option value="completed" {{ $serviceRequest->status === 'completed' ? 'selected' : '' }}>Concluída</option>
                                    <option value="cancelled" {{ $serviceRequest->status === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                                    <option value="urgent" {{ $serviceRequest->status === 'urgent' ? 'selected' : '' }}>Urgente</option>
                                </select>
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                                    Atualizar Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- User Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Usuário</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center space-x-4 mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center text-white text-lg font-semibold">
                                {{ $serviceRequest->user->initials() }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $serviceRequest->user->full_name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $serviceRequest->user->email }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            @if($serviceRequest->user->username)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Username:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $serviceRequest->user->username }}</span>
                                </div>
                            @endif
                            @if($serviceRequest->user->cpf)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">CPF:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $serviceRequest->user->cpf }}</span>
                                </div>
                            @endif
                            @if($serviceRequest->user->birth_date)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Nascimento:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $serviceRequest->user->birth_date->format('d/m/Y') }}</span>
                                </div>
                            @endif
                            @if($serviceRequest->user->type)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Tipo:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $serviceRequest->user->type }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Membro desde:</span>
                                <span class="text-gray-900 dark:text-white">{{ $serviceRequest->user->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('backoffice.users.show', $serviceRequest->user->id) }}" 
                               class="w-full inline-flex justify-center px-4 py-2 border border-blue-600 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900 rounded-md text-sm font-medium">
                                Ver perfil completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>