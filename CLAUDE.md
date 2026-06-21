# Querosene Chords — CLAUDE.md

**Slogan:** Dê um gás na sua música  
**Plataforma:** API REST + Admin Web (este repo) · App Android Flutter (repo separado)

---

## Ambiente local

| Item | Valor |
|---|---|
| Servidor | WAMP (Apache 2.4.65) |
| PHP | `D:\wamp64\bin\php\php8.5.0\php.exe` — **sempre use o caminho completo** |
| URL local | `http://querosene.test` (VirtualHost configurado) |
| Document root | `D:\wamp64\www\querosene-chords\public` |
| Banco | MySQL · banco `querosene_chords` · usuário `root` sem senha |
| Admin | `http://querosene.test/admin` · `admin@querosene.test` / `password` |

> O `php` do PATH pode ser outra versão (XAMPP). Use sempre o caminho completo acima.

### Comandos frequentes

```powershell
# Queue worker (obrigatório para importações funcionarem)
D:\wamp64\bin\php\php8.5.0\php.exe artisan queue:work --queue=imports,default --timeout=300 --tries=2

# Migrations
D:\wamp64\bin\php\php8.5.0\php.exe artisan migrate

# Re-seed completo
D:\wamp64\bin\php\php8.5.0\php.exe artisan migrate:fresh --seed

# Limpar cache de config/views
D:\wamp64\bin\php\php8.5.0\php.exe artisan optimize:clear
```

> O aviso `Failed loading php_xdebug-3.4.7-8.4-ts-vs17-x86_64.dll` é ruído não-bloqueante — aparece em todo comando PHP CLI.

---

## Stack

- **Laravel 13** + **Filament 3.3** + **Livewire 3** + **MySQL**
- **Laravel Sanctum 4** para auth da API
- **Queue driver:** `database` (sem Redis) — tabela `jobs`
- **Filesystem:** `public` disk para uploads de produção; `local` para temporários de importação
- **CORS:** aberto a todas as origens (`allowed_origins=['*']`) em `config/cors.php`
- **Rate limit:** 60 req/min via `throttleApi('60,1')` em `bootstrap/app.php`
- **Paleta:** bg `#0D0D0D` · surface `#1A1A1A` · primary `#FF6D00` · secondary `#FFB300` · texto `#F5F5F5`
- **Fonte:** Outfit (Google Fonts)
- **Package Android:** `br.com.querosene.chords`

---

## Schema do banco

```
artists      id, name, slug*, bio, photo_path, country(2), genre, musicbrainz_id, timestamps
categories   id, name, slug*, color(hex), timestamps
songs        id, artist_id→artists, category_id→categories, title, slug*, key, difficulty,
             bpm, year, album, musicbrainz_id, is_published, views, timestamps
             [fulltext: title] [index: slug, views]
chords       id, song_id→songs, version_label, content(longtext ChordPro), source,
             tab_content, is_default(bool), timestamps
chord_diagrams id, song_id→songs, chord_name, positions(json), fingers(json), timestamps
imports      id, original_filename, format, status(pending|processing|completed|failed),
             total_files, imported_count, failed_count, log(json), timestamps
```

`*` = unique index

---

## API REST — `/api/v1`

Todos os endpoints são **públicos** (sem auth no estado atual). Prefixo `api/` tem CORS + rate-limit.

| Método | Rota | Controller |
|---|---|---|
| GET | `/v1/featured` | `FeaturedController` |
| GET | `/v1/search?q=` | `SearchController` |
| GET | `/v1/songs` | `SongController@index` |
| GET | `/v1/songs/{slug}` | `SongController@show` |
| GET | `/v1/songs/{slug}/suggestions` | `SongController@suggestions` |
| GET | `/v1/songs/{slug}/chord-diagrams` | `SongController@chordDiagrams` |
| GET | `/v1/artists` | `ArtistController@index` |
| GET | `/v1/artists/{slug}` | `ArtistController@show` |
| GET | `/v1/artists/{slug}/songs` | `ArtistController@songs` |
| GET | `/v1/categories` | `CategoryController@index` |
| GET | `/v1/categories/{slug}/songs` | `CategoryController@songs` |

---

## Admin Filament — `/admin`

Recursos disponíveis:

| Resource | Model | Observações |
|---|---|---|
| `SongResource` | `Song` | icon `heroicon-o-queue-list`; campo `chord_content` é `dehydrated(false)` — salvo manualmente nas pages Create/Edit |
| `ArtistResource` | `Artist` | `musicbrainz_id` exibido como somente-leitura |
| `CategoryResource` | `Category` | |
| `ImportResource` | `Import` | Página customizada `CreateImport` com wizard 3 passos |

### Importação (wizard)

1. **Upload** — detecta formato automaticamente (`FormatDetector`)
2. **Preview** — mostra primeiros 5 arquivos/título/artista
3. **Processing** — dispatcha `ProcessBatchImportJob` via queue; polling `wire:poll.3000ms`

O arquivo de upload chega como `['uuid' => TemporaryUploadedFile]` no Filament 3 — lógica de resolução em `CreateImport::resolveUploadedFilePath()`.

---

## Sistema de Importação

### Serviços em `app/Services/Import/`

| Serviço | Função |
|---|---|
| `FormatDetector` | Detecta formato pelos primeiros 512 bytes (magic bytes + regex) |
| `CifraClubConverter` | TXT Cifra Club → ChordPro; extrai diagramas de acordes do rodapé |
| `ChordProImporter` | Passthrough ChordPro com parse de headers `{title:}` `{artist:}` |
| `MusicXmlConverter` | SimpleXML; suporta `.mxl` (ZIP) e `.xml` |
| `GuitarProConverter` | **Stub** — lança `RuntimeException` (implementação v1.1) |
| `ZipBatchImporter` | Extrai ZIP → `storage/app/temp/imports/{uuid}/`; lista, preview, converte, cleanup |
| `MusicMetadataService` | Consulta **MusicBrainz API** + **Wikipedia** para enriquecer metadados |

### MusicMetadataService

- Rate limit interno: 1,3 s entre chamadas (MusicBrainz permite 1 req/s)
- Cache: 7 dias por artista/música (chave `mb_artist_*` e `mb_recording_*`)
- Busca: país, gênero, MBID do artista; ano, álbum, MBID da gravação; bio via Wikipedia
- **Nunca lança exceção** — falhas são logadas com `Log::warning` e retornam `[]`
- User-Agent obrigatório: `QuerosenoChords/1.0 (rogersneves@gmail.com)`

### Job: `ProcessBatchImportJob`

- Queue: `imports` · timeout: 300 s · tries: 2
- Fluxo: lista arquivos → detecta formato → converte → enriquece via MusicBrainz → persiste Artist + Song + Chord
- Se artista já existe, preenche silenciosamente campos nulos (genre, bio, musicbrainz_id, country)
- `failed()` marca import como `failed` no banco

---

## Modelos — pontos importantes

### `Artist`
- `booted()`: auto-gera `slug` no `creating` se vazio
- `$fillable`: name, slug, bio, photo_path, country, genre, musicbrainz_id

### `Song`
- `booted()`: auto-gera `slug` no `creating` se vazio
- `defaultChord()`: `HasOne` filtrado por `is_default = true`
- `incrementViews()`: incrementa o contador atomicamente
- `$fillable`: artist_id, category_id, title, slug, key, difficulty, bpm, year, album, musicbrainz_id, is_published, views

### `Chord`
- `booted()` `saving`: garante no máximo um `is_default = true` por `song_id` (zera os outros)

---

## Seeders

```
DatabaseSeeder
  ├── Cria user: admin@querosene.test / password
  ├── CategorySeeder  — 8 categorias (Rock Nacional, MPB, Sertanejo…)
  ├── ArtistSeeder    — 5 artistas BR (Legião, Raul, Roberto Carlos, Djavan, Skank)
  └── SongSeeder      — 10 músicas com ChordPro completo (2 por artista)
```

> **Atenção:** Os seeders definem `slug` explicitamente via `Str::slug()` — não dependem do `booted()` para evitar rejeição do MySQL no modo strict (NOT NULL sem default).

---

## Gotchas conhecidos

| Problema | Solução |
|---|---|
| `php` no PATH ≠ PHP do WAMP | Use sempre `D:\wamp64\bin\php\php8.5.0\php.exe` |
| Filament FileUpload retorna `['uuid' => TemporaryUploadedFile]` | Ver `CreateImport::resolveUploadedFilePath()` |
| `Select::relationship()` falha em Page sem Model | Usar `->options(Model::pluck('name','id'))` |
| `rename(): Acesso negado` em `storage/framework/views` | `icacls storage /grant "*S-1-1-0:(OI)(CI)F" /T` |
| Import travado em "Processando" | Queue worker não está rodando — iniciar com o comando acima |
| Filament audit advisory `PKSA-n7tx-gkfb-14yj` | Ignorado em `composer.json > audit.ignore` |

---

## O que está pronto / pendente

### ✅ Concluído (PROMPT 1)
- Migrations, Models, Seeders
- API REST (11 endpoints)
- Admin Filament (4 Resources + wizard de importação)
- Importadores: Cifra Club TXT, ChordPro, MusicXML, ZIP batch
- Queue job com `ProcessBatchImportJob`
- Enriquecimento automático via MusicBrainz + Wikipedia

### ⏳ Pendente
- Interface Web pública (Livewire + TailwindCSS) — player web
- App Flutter (repo separado) — estrutura, telas, player, auto-scroll
- GuitarPro GP5 converter (v1.1)
- Auto-scroll por BPM (v1.1)
- Auth Sanctum para endpoints da API (atualmente públicos)
