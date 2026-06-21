<?php

namespace App\Filament\Resources\ImportResource\Pages;

use App\Filament\Resources\ImportResource;
use App\Jobs\ProcessBatchImportJob;
use App\Models\Import;
use App\Services\Import\CifraClubConverter;
use App\Services\Import\ChordProImporter;
use App\Services\Import\FormatDetector;
use App\Services\Import\GuitarProConverter;
use App\Services\Import\MusicXmlConverter;
use App\Services\Import\ZipBatchImporter;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class CreateImport extends Page
{
    protected static string $resource = ImportResource::class;
    protected static string $view = 'filament.resources.import-resource.pages.create-import';

    public ?array $data = [];
    public ?array $previewData = null;
    public ?string $tempDir = null;
    public ?string $tempFilePath = null;
    public string $step = 'upload';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\FileUpload::make('file')
                    ->label('Arquivo')
                    ->required()
                    ->maxSize(50 * 1024)
                    ->disk('local')
                    ->directory('temp/uploads'),

                Forms\Components\Select::make('default_category_id')
                    ->label('Categoria padrão')
                    ->options(\App\Models\Category::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Forms\Components\Toggle::make('publish_by_default')
                    ->label('Publicar imediatamente')
                    ->default(true),

                Forms\Components\Toggle::make('overwrite_duplicates')
                    ->label('Sobrescrever duplicatas')
                    ->default(false),
            ]);
    }

    private function resolveUploadedFilePath(): string
    {
        $file = $this->data['file'];

        // Filament 3 FileUpload stores state as ['uuid' => TemporaryUploadedFile]
        if (is_array($file)) {
            $file = collect($file)->first();
        }

        // Livewire TemporaryUploadedFile or standard UploadedFile
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $relativePath = $file->store('temp/uploads', 'local');
            return Storage::disk('local')->path($relativePath);
        }

        // Already a string path
        $path = (string) $file;
        if (file_exists($path)) {
            return $path;
        }

        return Storage::disk('local')->path($path);
    }

    public function preview(): void
    {
        $this->validate();

        $filePath = $this->resolveUploadedFilePath();
        $detector = new FormatDetector();
        $format = $detector->detect($filePath);

        if ($format === 'zip') {
            $batchImporter = app(ZipBatchImporter::class);
            $this->tempDir = $batchImporter->extract($filePath);
            $this->previewData = $batchImporter->preview($this->tempDir);
        } else {
            $converter = match ($format) {
                'cifraclub' => app(CifraClubConverter::class),
                'chordpro' => app(ChordProImporter::class),
                'guitarpro' => app(GuitarProConverter::class),
                'musicxml' => app(MusicXmlConverter::class),
                default => null,
            };

            if (!$converter) {
                Notification::make()->danger()->title('Formato não suportado')->send();
                return;
            }

            try {
                $result = $format === 'guitarpro'
                    ? $converter->convert($filePath)
                    : $converter->convert(file_get_contents($filePath));

                $this->previewData = [
                    'total' => 1,
                    'format' => $format,
                    'preview' => [[
                        'filename' => basename($filePath),
                        'format' => $format,
                        'title' => $result['title'] ?? '(sem título)',
                        'artist' => $result['artist'] ?? '(desconhecido)',
                        'preview_lines' => implode("\n", array_slice(
                            explode("\n", $result['content']), 0, 20
                        )),
                        'error' => null,
                    ]],
                ];
                $this->tempFilePath = $filePath;
            } catch (\Throwable $e) {
                Notification::make()->danger()->title('Erro na conversão')->body($e->getMessage())->send();
                return;
            }
        }

        $this->step = 'preview';
    }

    public function confirm(): void
    {
        $import = Import::create([
            'original_filename' => basename($this->resolveUploadedFilePath()),
            'format' => $this->previewData['format'] ?? 'zip',
            'total_files' => $this->previewData['total'] ?? 1,
            'status' => 'pending',
        ]);

        if ($this->tempDir) {
            ProcessBatchImportJob::dispatch(
                $import->id,
                $this->tempDir,
                $this->data['default_category_id'] ?? null,
                $this->data['overwrite_duplicates'] ?? false,
                $this->data['publish_by_default'] ?? true,
            );

            $this->step = 'processing';
            $this->importId = $import->id;
        } else {
            // Single file: process inline
            $this->processSingleFile($import);
        }
    }

    public int $importId = 0;

    private function processSingleFile(Import $import): void
    {
        $import->update(['status' => 'processing']);

        try {
            $filePath = $this->tempFilePath ?? $this->resolveUploadedFilePath();
            $detector = new FormatDetector();
            $format = $detector->detect($filePath);

            $batchImporter = app(ZipBatchImporter::class);
            $data = $batchImporter->convertFile($filePath, $format);

            $job = new ProcessBatchImportJob(
                $import->id,
                dirname($filePath),
                $this->data['default_category_id'] ?? null,
                $this->data['overwrite_duplicates'] ?? false,
                $this->data['publish_by_default'] ?? true,
            );

            // For single files, create a temp dir with just this file
            $tempDir = storage_path('app/temp/imports/' . \Illuminate\Support\Str::uuid());
            mkdir($tempDir, 0755, true);
            copy($filePath, $tempDir . '/' . basename($filePath));

            ProcessBatchImportJob::dispatch(
                $import->id,
                $tempDir,
                $this->data['default_category_id'] ?? null,
                $this->data['overwrite_duplicates'] ?? false,
                $this->data['publish_by_default'] ?? true,
            );

            $this->step = 'processing';
            $this->importId = $import->id;
        } catch (\Throwable $e) {
            $import->update(['status' => 'failed', 'log' => [['status' => 'error', 'message' => $e->getMessage()]]]);
            Notification::make()->danger()->title('Erro na importação')->body($e->getMessage())->send();
        }
    }

    public function getImportStatus(): array
    {
        if (!$this->importId) {
            return ['status' => 'pending', 'progress' => 0];
        }

        $import = Import::find($this->importId);

        if (!$import) {
            return ['status' => 'failed', 'progress' => 0];
        }

        $progress = $import->total_files > 0
            ? (int) (($import->imported_count + $import->failed_count) / $import->total_files * 100)
            : 0;

        return [
            'status' => $import->status,
            'progress' => $progress,
            'imported' => $import->imported_count,
            'failed' => $import->failed_count,
            'total' => $import->total_files,
        ];
    }
}
