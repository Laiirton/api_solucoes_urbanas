<x-layouts.app :title="__('Uploads de Arquivos')">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Uploads de Arquivos</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Visualize todos os arquivos enviados pelos usuários</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <form method="GET" action="{{ route('backoffice.uploads.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Nome do arquivo ou usuário..." 
                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>

                <div>
                    <label for="mime_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Arquivo</label>
                    <select id="mime_type" 
                            name="mime_type" 
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todos os tipos</option>
                        <option value="image" {{ request('mime_type') === 'image' ? 'selected' : '' }}>Imagens</option>
                        <option value="video" {{ request('mime_type') === 'video' ? 'selected' : '' }}>Vídeos</option>
                        <option value="audio" {{ request('mime_type') === 'audio' ? 'selected' : '' }}>Áudios</option>
                        <option value="application/pdf" {{ request('mime_type') === 'application/pdf' ? 'selected' : '' }}>PDF</option>
                        <option value="text" {{ request('mime_type') === 'text' ? 'selected' : '' }}>Texto</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Inicial</label>
                    <input type="date" 
                           id="date_from" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Final</label>
                    <input type="date" 
                           id="date_to" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>

                <div class="sm:col-span-2 lg:col-span-4 flex gap-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                        Filtrar
                    </button>
                    <a href="{{ route('backoffice.uploads.index') }}" 
                       class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-md text-sm font-medium">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {{ $uploads->firstItem() ?? 0 }} a {{ $uploads->lastItem() ?? 0 }} 
                de {{ $uploads->total() }} uploads
            </p>
        </div>

        <!-- Uploads Grid -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            @if($uploads->count() > 0)
                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($uploads as $upload)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <div class="flex flex-col h-full">
                                <!-- File Preview -->
                                <div class="flex-shrink-0 mb-4">
                                    @if(Str::startsWith($upload->mime_type, 'image/'))
                                        <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden">
                                            @if($upload->url)
                                                <img src="{{ $upload->url }}" 
                                                     alt="Preview" 
                                                     class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif(Str::startsWith($upload->mime_type, 'video/'))
                                        <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @elseif(Str::startsWith($upload->mime_type, 'audio/'))
                                        <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                            </svg>
                                        </div>
                                    @elseif($upload->mime_type === 'application/pdf')
                                        <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-full h-32 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- File Info -->
                                <div class="flex-grow">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate mb-2">
                                        {{ $upload->stored_name }}
                                    </h3>
                                    
                                    <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                        <p>Tipo: {{ $upload->mime_type }}</p>
                                        <p>Tamanho: {{ number_format($upload->size / 1024, 2) }} KB</p>
                                        <p>Upload: {{ $upload->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-6 h-6 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                                {{ $upload->user->initials() }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-900 dark:text-white">
                                                    {{ Str::limit($upload->user->full_name, 15) }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        @if($upload->url)
                                            <a href="{{ $upload->url }}" 
                                               target="_blank" 
                                               class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-xs">
                                                Ver
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($uploads->hasPages())
                    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                        {{ $uploads->links() }}
                    </div>
                @endif
            @else
                <div class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhum upload encontrado</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Não há uploads que correspondam aos filtros aplicados.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>