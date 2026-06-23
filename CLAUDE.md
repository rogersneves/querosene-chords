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

### SSL em ambiente Windows/WAMP

O WAMP não tem certificados SSL configurados por padrão, causando `cURL error 60` em qualquer requisição HTTPS (MusicBrainz, Wikipedia, YouTube). A solução está no código: todos os serviços HTTP carregam `storage/app/cacert.pem` via `withOptions(['verify' => ...])`. **Não depende de `php.ini` nem de `phpForApache.ini`.**

Se precisar regenerar o cacert:
```powershell
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "storage\app\cacert.pem" -UseBasicParsing
```

> O queue worker deve ser **reiniciado sempre que o código PHP mudar** — é um processo de longa duração que carrega os arquivos em memória.

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
             bpm, year, album, musicbrainz_id, youtube_id, is_published, views, timestamps
             [fulltext: title] [index: slug, views, created_at, is_published]
chords       id, song_id→songs, version_label, content(longtext ChordPro), source,
             tab_content, is_default(bool), timestamps
chord_diagrams id, chord_name*, strings_pattern, fingering(json), fingers(json), barre, timestamps
imports      id, original_filename, format, status(pending|processing|completed|failed),
             total_files, imported_count, failed_count, log(json), timestamps
             [index: created_at, status]
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
| `SongResource` | `Song` | Botão **Enriquecer** no rodapé do card Informações (só na edição); `chord_content` é `dehydrated(false)` — salvo manualmente nas pages Create/Edit; lista usa `select()` explícito + `with(['artist','category'])` para evitar N+1 |
| `ArtistResource` | `Artist` | Botão **Enriquecer** no rodapé do card Informações (só na edição); `musicbrainz_id` exibido como somente-leitura |
| `CategoryResource` | `Category` | |
| `ImportResource` | `Import` | Página customizada `CreateImport` com wizard 3 passos; lista exclui coluna `log` da query para performance |

### Botão Enriquecer (SongResource — edição)

Localizado no canto inferior direito do card "Informações". Ao clicar:
1. Invalida os caches MusicBrainz (`mb_recording_*`, `mb_artist_*`) e TheAudioDB (`tadb_artist_*`)
2. Consulta MusicBrainz: ano, álbum, MBID da gravação
3. Consulta MusicBrainz + TheAudioDB: gênero, bio em português, país, MBID, foto do artista
4. Baixa e salva a foto se o artista ainda não tiver uma (`photo_path`)
5. Busca YouTube ID (sempre — independente de já existir)
6. Atualiza o banco e redireciona para recarregar o form
7. Notificação lista os campos atualizados

### Botão Enriquecer (ArtistResource — edição)

Localizado no canto inferior direito do card "Informações". Ao clicar:
1. Invalida os caches `mb_artist_*` e `tadb_artist_*`
2. Consulta MusicBrainz + TheAudioDB: gênero, bio em português, país, MBID, foto
3. Baixa e salva a foto se o artista ainda não tiver uma
4. Atualiza o banco e redireciona para recarregar o form
5. Notificação lista os campos atualizados

### Importação (wizard)

1. **Upload** — detecta formato automaticamente (`FormatDetector`); aceita qualquer extensão (validação pelo conteúdo)
2. **Preview** — mostra primeiros 5 arquivos/título/artista
3. **Processing** — dispatcha `ProcessBatchImportJob` via queue; polling `wire:poll.3000ms`

O arquivo de upload chega como `['uuid' => TemporaryUploadedFile]` no Filament 3 — lógica de resolução em `CreateImport::resolveUploadedFilePath()`.

---

## Sistema de Importação

### Serviços em `app/Services/Import/`

| Serviço | Função |
|---|---|
| `FormatDetector` | Detecta formato pelos primeiros 512 bytes (magic bytes + regex); fallback por extensão para `.pro .cho .chopro .crd .chord .chordpro` |
| `CifraClubConverter` | TXT Cifra Club → ChordPro; suporta tablaturas (`{start_of_tab}`), seções, diagramas no rodapé, linhas consecutivas de acordes |
| `ChordProImporter` | Passthrough ChordPro com parse de headers `{title:}` `{artist:}` |
| `MusicXmlConverter` | SimpleXML; suporta `.mxl` (ZIP) e `.xml` |
| `GuitarProConverter` | **Stub** — lança `RuntimeException` (implementação v1.1) |
| `ZipBatchImporter` | Extrai ZIP → `storage/app/temp/imports/{uuid}/`; lista, preview, converte, cleanup |
| `MusicMetadataService` | Consulta **MusicBrainz API** + **Wikipedia** + **TheAudioDB** para enriquecer metadados; baixa foto do artista |
| `YouTubeSearchService` | Busca YouTube Data API v3 pelo primeiro vídeo correspondente (chave em `YOUTUBE_API_KEY`) |

### MusicMetadataService

- Rate limit interno: 1,3 s entre chamadas MusicBrainz (MusicBrainz permite 1 req/s); TheAudioDB sem rate limit
- Cache: 7 dias — chaves `mb_artist_*`, `mb_recording_*` (MusicBrainz + TheAudioDB fundidos), `tadb_artist_*` (TheAudioDB isolado)
- **MusicBrainz**: país (ISO-2), gênero, MBID do artista; ano (fallback para `releases[].date`), álbum, MBID da gravação; bio via Wikipedia
- **TheAudioDB** (`theaudiodb.com/api/v1/json/2/search.php?s=`): foto do artista (`strArtistThumb`), bio em português (`strBiographyPT`) com fallback inglês, gênero como fallback
- `downloadArtistPhoto(url, slug)`: baixa a foto e salva em `storage/app/public/artists/{slug}.{ext}`; retorna path relativo ao disco `public`
- **Nunca lança exceção** — falhas são logadas com `Log::warning` e retornam `[]`/`null`
- User-Agent obrigatório: `QuerosenoChords/1.0 (rogersneves@gmail.com)`
- SSL configurado via `withOptions(['verify' => storage_path('app/cacert.pem')])`

### YouTubeSearchService

- Chave de API em `YOUTUBE_API_KEY` no `.env` → `config('services.youtube.api_key')`
- Endpoint: `googleapis.com/youtube/v3/search` · `maxResults=1` · `type=video`
- Retorna `videoId` ou `null` (nunca lança exceção)
- SSL configurado via `withOptions(['verify' => storage_path('app/cacert.pem')])`

### Job: `ProcessBatchImportJob`

- Queue: `imports` · timeout: 300 s · tries: 2
- Fluxo: lista arquivos → detecta formato → converte → split `"Título - Artista"` (antes do enriquecimento) → enriquece via MusicBrainz + TheAudioDB → baixa foto do artista → busca YouTube → persiste Artist + Song + Chord + ChordDiagrams
- Se artista já existe, preenche silenciosamente campos nulos (genre, bio, musicbrainz_id, country, photo_path)
- **Slug de músicas**: calculado após o título final (pós-split) e após o artista ser resolvido
  - Colisão com **mesmo artista** → "Duplicata ignorada" (respeita flag `overwriteDuplicates`)
  - Colisão com **artista diferente** → slug `{titulo}-{slug-do-artista}` (unicidade garantida por `uniqueSlug()`)
- `failed()` marca import como `failed` no banco

---

## Renderizador ChordPro (`app/Services/ChordProRenderer.php`)

- Converte conteúdo ChordPro em HTML para a view pública
- Suporta seções (`{start_of_verse}`, `{start_of_chorus}`, `{start_of_tab}`, etc.)
- Tablaturas (`E|---`) renderizadas em `<pre class="cp-tab">` com borda âmbar
- Linhas de anotação (`Intro: [Am] [G]`) renderizadas inline
- `[Chord]` em colchetes só é tratado como acorde se passar por `isChordToken()` — letras entre colchetes que não sejam acordes válidos são renderizadas como letra
- Transposição e diagramas de acordes via JavaScript (Alpine.js) na view pública

---

## Modelos — pontos importantes

### `Artist`
- `booted()`: auto-gera `slug` no `creating` se vazio
- `$fillable`: name, slug, bio, photo_path, country, genre, musicbrainz_id

### `Song`
- `booted()`: auto-gera `slug` no `creating` se vazio
- `defaultChord()`: `HasOne` filtrado por `is_default = true`
- `incrementViews()`: incrementa o contador atomicamente
- `$fillable`: artist_id, category_id, title, slug, key, difficulty, bpm, year, album, musicbrainz_id, youtube_id, is_published, views

### `Chord`
- `booted()` `saving`: garante no máximo um `is_default = true` por `song_id` (zera os outros)

---

## Interface Web pública

- **Home** (`resources/views/home.blade.php`): ordem das seções — Novidades → Mais tocadas → Categorias; todas as seções usam `grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4`
- **Song card** (`resources/views/partials/song-card.blade.php`): exibe badge do YouTube (ícone vermelho) quando `youtube_id` está preenchido
- **Player** (`resources/views/song/show.blade.php`): transposição, auto-scroll, tamanho de fonte, player YouTube flutuante e arrastável, diagramas de acordes em popup
- **Fotos de artistas**: salvas em `storage/app/public/artists/{slug}.{ext}` · acessíveis via `Storage::disk('public')->url($artist->photo_path)` · symlink `public/storage` já criado
- Controllers web usam eager loading `with(['artist', 'category'])` em todas as listagens

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
| `cURL error 60` (SSL) no WAMP | Resolvido no código via `withOptions(['verify' => storage_path('app/cacert.pem')])` — não editar php.ini |
| Queue worker não pega mudanças de código | Reiniciar o worker — processo de longa duração cacheia PHP em memória |
| MusicBrainz/YouTube retorna dados mas form não atualiza | O botão Enriquecer redireciona automaticamente após salvar |

---

## O que está pronto / pendente

### ✅ Concluído
- Migrations, Models, Seeders
- API REST (11 endpoints)
- Admin Filament (4 Resources + wizard de importação)
- Importadores: Cifra Club TXT (com tablaturas), ChordPro (extensões `.pro .cho .chopro .crd .chord .chordpro`), MusicXML, ZIP batch
- Queue job com `ProcessBatchImportJob` (MusicBrainz + TheAudioDB + YouTube + inferência de tom)
- Enriquecimento automático na importação + botão Enriquecer manual em músicas e artistas
- Foto do artista: baixada automaticamente do TheAudioDB na importação e no botão Enriquecer
- Bio do artista em português via TheAudioDB (`strBiographyPT`)
- Slug de músicas com desambiguação por artista (versões cover não conflitam)
- Renderizador ChordPro com tablatura, seções, anotações e validação de acordes
- Interface Web pública: player com transposição, auto-scroll, YouTube, diagramas
- Home com grid responsivo (Novidades → Mais tocadas → Categorias)
- Badge de vídeo nos cards de músicas
- Indexes de performance no banco (`created_at`, `is_published`, `status`)
- SSL configurado no código para funcionar independente do ambiente Windows

### ⏳ Pendente
- App Flutter (repo separado) — estrutura, telas, player, auto-scroll
- GuitarPro GP5 converter (v1.1)
- Auto-scroll por BPM (v1.1)
- Auth Sanctum para endpoints da API (atualmente públicos)
