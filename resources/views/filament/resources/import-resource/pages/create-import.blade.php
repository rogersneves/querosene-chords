<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Step: Upload --}}
        @if ($step === 'upload')
            <x-filament::section heading="Nova Importação">
                <form wire:submit.prevent="preview">
                    {{ $this->form }}
                    <div class="mt-6">
                        <x-filament::button type="submit" icon="heroicon-o-eye">
                            Pré-visualizar
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        @endif

        {{-- Step: Preview --}}
        @if ($step === 'preview' && $previewData)
            <x-filament::section heading="Pré-visualização">
                <p class="text-sm text-gray-500 mb-4">
                    Total de arquivos detectados: <strong>{{ $previewData['total'] }}</strong>
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Arquivo</th>
                                <th class="text-left p-2">Formato</th>
                                <th class="text-left p-2">Título</th>
                                <th class="text-left p-2">Artista</th>
                                <th class="text-left p-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewData['preview'] as $item)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="p-2 font-mono text-xs">{{ $item['filename'] }}</td>
                                    <td class="p-2">
                                        <x-filament::badge>{{ $item['format'] }}</x-filament::badge>
                                    </td>
                                    <td class="p-2">{{ $item['title'] }}</td>
                                    <td class="p-2">{{ $item['artist'] }}</td>
                                    <td class="p-2">
                                        @if ($item['error'])
                                            <x-filament::badge color="danger">Erro</x-filament::badge>
                                            <p class="text-xs text-red-500 mt-1">{{ $item['error'] }}</p>
                                        @else
                                            <x-filament::badge color="success">OK</x-filament::badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex gap-3">
                    <x-filament::button wire:click="confirm" icon="heroicon-o-check">
                        Confirmar e Importar
                    </x-filament::button>
                    <x-filament::button color="gray" wire:click="$set('step', 'upload')" icon="heroicon-o-arrow-left">
                        Voltar
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        {{-- Step: Processing --}}
        @if ($step === 'processing')
            <x-filament::section heading="Processando importação...">
                @php $status = $this->getImportStatus() @endphp

                <div class="space-y-4" wire:poll.3000ms>
                    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                        <div class="bg-primary-600 h-3 rounded-full transition-all duration-500"
                             style="width: {{ $status['progress'] }}%"></div>
                    </div>

                    <p class="text-sm text-gray-600">
                        {{ $status['imported'] ?? 0 }} importados •
                        {{ $status['failed'] ?? 0 }} falhas •
                        {{ $status['total'] ?? 0 }} total
                    </p>

                    @if (in_array($status['status'], ['completed', 'failed']))
                        <x-filament::badge color="{{ $status['status'] === 'completed' ? 'success' : 'danger' }}">
                            {{ $status['status'] === 'completed' ? 'Concluído!' : 'Falhou' }}
                        </x-filament::badge>

                        <div class="mt-4">
                            <x-filament::button
                                :href="route('filament.admin.resources.imports.view', $importId)"
                                tag="a"
                                icon="heroicon-o-document-text">
                                Ver log completo
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
