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

# Popular chord_list nas músicas existentes (rodar após migrate:fresh)
D:\wamp64\bin\php\php8.5.0\php.exe artisan songs:backfill-chords
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
- **barryvdh/laravel-dompdf** para exportação de PDF (cifras e cadernos)
- **Queue driver:** `database` (sem Redis) — tabela `jobs`
- **Filesystem:** `public` disk para uploads de produção; `local` para temporários de importação
- **CORS:** aberto a todas as origens (`allowed_origins=['*']`) em `config/cors.php`
- **Rate limit:** 60 req/min via `throttleApi('60,1')` em `bootstrap/app.php`
- **Paleta:** bg `#0D0D0D` · surface `#1A1A1A` · primary `#FF6D00` · secondary `#FFB300` · texto `#F5F5F5`
- **Fonte:** Outfit (Google Fonts)
- **Flags:** `flag-icons@7.2.3` via CDN (JSDelivr) — classes `fi fi-{iso2}`
- **Package Android:** `br.com.querosene.chords`

---

## Schema do banco

```
artists      id, name, slug*, bio, bio_en, bio_es, bio_fr, photo_path, country(2),
             genre, musicbrainz_id, timestamps
categories   id, name, slug*, color(hex), timestamps
songs        id, artist_id→artists, category_id→categories, title, slug*, key, difficulty,
             bpm, year, album, musicbrainz_id, youtube_id, is_published, views,
             chord_list(json nullable), timestamps
             [fulltext: title] [index: slug, views, created_at, is_published]
chords       id, song_id→songs, version_label, content(longtext ChordPro), source,
             tab_content, is_default(bool), timestamps
chord_diagrams id, chord_name*, strings_pattern, fingering(json), fingers(json), barre, timestamps
imports      id, original_filename, format, status(pending|processing|completed|failed),
             total_files, imported_count, failed_count, log(json), timestamps
             [index: created_at, status]
setlists     id, user_id→users, name, is_public(bool), timestamps
             [index: user_id]
setlist_songs id, setlist_id→setlists, song_id→songs, position(smallint), timestamps
             [unique: setlist_id+song_id] [index: setlist_id+position]
mfa_codes    id, user_id→users, code(hash), expires_at, timestamps
             [index: user_id]
mfa_trusted_devices id, user_id→users, token_hash(sha256,64), expires_at, timestamps
             [index: user_id+token_hash]
```

`*` = unique index

---

## Autenticação pública (site)

Separada do admin Filament. Usa a mesma tabela `users` — o acesso ao painel é restrito via `canAccessPanel()` que verifica `email === 'admin@querosene.test'`.

### Rotas de auth

| Rota | Descrição |
|---|---|
| `GET /entrar` | Formulário de login |
| `POST /entrar` | Valida credenciais → envia código MFA → redireciona para /verificar |
| `GET /cadastrar` | Formulário de cadastro |
| `POST /cadastrar` | Cria conta + login direto (sem MFA no cadastro) |
| `POST /sair` | Logout |
| `GET /verificar` | Formulário do código MFA |
| `POST /verificar` | Valida código → faz login → opcionalmente confia no navegador por 30 dias |
| `POST /verificar/reenviar` | Gera novo código e reenvia |

### MFA por email

- **Fluxo**: credenciais válidas → verifica cookie de dispositivo confiável → se não confiável, envia código de 6 dígitos para o email → `/verificar`
- **Código**: 6 dígitos, expira em 10 minutos, armazenado como `bcrypt()` em `mfa_codes`
- **Dev bypass**: `app()->isProduction()` falso → código fixo `123456` (não envia email)
- **Dispositivo confiável**: checkbox "não pedir por 30 dias" → salva `hash('sha256', $token)` em `mfa_trusted_devices` + cookie httponly `mfa_device_token` por 30 dias
- **Rate limit**: 5 tentativas erradas / 5 min por usuário; 3 reenvios / 5 min
- **Email**: `App\Mail\MfaCodeMail` → view `resources/views/emails/mfa_code.blade.php`
- **Config**: definir `MAIL_MAILER`, `MAIL_HOST`, etc. no `.env`. Para desenvolvimento local usar `MAIL_MAILER=log` — código aparece em `storage/logs/laravel.log`

### Cadernos (`/caderno`)

Requer autenticação. Todas as rotas prefixadas com `/caderno` e agrupadas com middleware `auth`.

| Rota | Descrição |
|---|---|
| `GET /caderno` | Lista cadernos do usuário |
| `POST /caderno` | Cria novo caderno |
| `GET /caderno/{setlist}` | Detalhe do caderno |
| `DELETE /caderno/{setlist}` | Exclui caderno |
| `PATCH /caderno/{setlist}/renomear` | Renomeia caderno |
| `POST /caderno/{setlist}/toggle` | Adiciona/remove música (JSON, sem reload) |
| `DELETE /caderno/{setlist}/musica/{song}` | Remove música |
| `GET /caderno/{setlist}/pdf` | Exporta caderno completo como PDF |

Na página de cada cifra: botão **Salvar** (ícone marcador) → dropdown com cadernos do usuário. Usuário não logado vê botão cinza que redireciona para login.

**Limite de músicas**: máximo 30 por caderno. O `toggle` retorna `{added: false, error: 'limit'}` com HTTP 422 quando o limite é atingido; o JS do player exibe `alert()` com a mensagem traduzida (`ui.setlist.limit_reached`).

**Detalhe do caderno** (`setlists/show.blade.php`): lista de músicas em colunas de largura fixa — título+artista (`flex-1`), tom (`w-10`), badges categoria+dificuldade (`w-52`), botão remover. Clicar em uma música abre o modal global de cifra (não navega para outra página). Botão **Exportar PDF** aparece no header quando o caderno tem ao menos uma música.

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

Acesso restrito: apenas `admin@querosene.test` via `canAccessPanel()` no `User` model.

Recursos disponíveis:

| Resource | Model | Observações |
|---|---|---|
| `SongResource` | `Song` | Botão **Enriquecer** no rodapé do card Informações (só na edição); `chord_content` é `dehydrated(false)` — salvo manualmente nas pages Create/Edit; popula `chord_list` ao salvar; lista usa `select()` explícito + `with(['artist','category'])` para evitar N+1 |
| `ArtistResource` | `Artist` | Botão **Enriquecer** no rodapé do card Informações (só na edição); `musicbrainz_id` exibido como somente-leitura; bio em 4 idiomas via Tabs |
| `CategoryResource` | `Category` | |
| `ImportResource` | `Import` | Página customizada `CreateImport` com wizard 3 passos; lista exclui coluna `log` da query para performance |

### Botão Enriquecer (SongResource — edição)

1. Invalida os caches MusicBrainz (`mb_recording_*`, `mb_artist_*`) e TheAudioDB (`tadb_artist_*`)
2. Consulta MusicBrainz: ano, álbum, MBID da gravação
3. Consulta MusicBrainz + TheAudioDB: gênero, bio PT/EN/ES/FR, país, MBID, foto do artista
4. Baixa e salva a foto se o artista ainda não tiver uma (`photo_path`)
5. Busca YouTube ID (sempre — independente de já existir)
6. Atualiza o banco e redireciona para recarregar o form
7. Notificação lista os campos atualizados

### Botão Enriquecer (ArtistResource — edição)

1. Invalida os caches `mb_artist_*` e `tadb_artist_*`
2. Consulta MusicBrainz + TheAudioDB: gênero, bio PT/EN/ES/FR, país, MBID, foto
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
| `MusicMetadataService` | Consulta **MusicBrainz API** + **Wikipedia/Wikidata** + **TheAudioDB** para enriquecer metadados; baixa foto do artista |
| `YouTubeSearchService` | Busca YouTube Data API v3 pelo primeiro vídeo correspondente (chave em `YOUTUBE_API_KEY`) |

### MusicMetadataService

- Rate limit interno: 1,3 s entre chamadas MusicBrainz (MusicBrainz permite 1 req/s); TheAudioDB sem rate limit
- Cache: 7 dias — chaves `mb_artist_*`, `mb_recording_*` (MusicBrainz + TheAudioDB fundidos), `tadb_artist_*` (TheAudioDB isolado)
- **MusicBrainz**: país (ISO-2), gênero, MBID do artista; ano (fallback para `releases[].date`), álbum, MBID da gravação; bio via Wikipedia
- **TheAudioDB** (`theaudiodb.com/api/v1/json/2/search.php?s=`): foto do artista (`strArtistThumb`), bio em português (`strBiographyPT`) com fallback inglês, gênero como fallback
- **Bio multilíngue (PT/EN/ES/FR)**: estratégia em dois níveis:
  1. Relação `wikipedia` no MusicBrainz → Wikipedia REST + langlinks API
  2. Relação `wikidata` no MusicBrainz (mais comum hoje) → Wikidata sitelinks API → Wikipedia REST em cada idioma
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
- Fluxo: lista arquivos → detecta formato → converte → split `"Título - Artista"` (antes do enriquecimento) → enriquece via MusicBrainz + TheAudioDB → baixa foto do artista → busca YouTube → persiste Artist + Song + Chord + ChordDiagrams → popula `chord_list`
- Se artista já existe, preenche silenciosamente campos nulos (genre, bio, bio_en, bio_es, bio_fr, musicbrainz_id, country, photo_path)
- **Slug de músicas**: calculado após o título final (pós-split) e após o artista ser resolvido
  - Colisão com **mesmo artista** → "Duplicata ignorada" (respeita flag `overwriteDuplicates`)
  - Colisão com **artista diferente** → slug `{titulo}-{slug-do-artista}` (unicidade garantida por `uniqueSlug()`)
- `failed()` marca import como `failed` no banco

---

## Renderizador ChordPro (`app/Services/ChordProRenderer.php`)

- Converte conteúdo ChordPro em HTML para a view pública e para o PDF
- Suporta seções (`{start_of_verse}`, `{start_of_chorus}`, `{start_of_tab}`, etc.)
- Tablaturas (`E|---`) renderizadas em `<pre class="cp-tab">` com borda âmbar
- Linhas de anotação (`Intro: [Am] [G]`) renderizadas inline
- `[Chord]` em colchetes só é tratado como acorde se passar por `isChordToken()` — letras entre colchetes que não sejam acordes válidos são renderizadas como letra
- Transposição e diagramas de acordes via JavaScript (Alpine.js) na view pública; nos PDFs os diagramas são gerados server-side por `ChordDiagramSvg`

## Exportação PDF (`app/Services/ChordDiagramSvg.php` + `app/Http/Controllers/Web/PdfController.php`)

### Rotas PDF

| Rota | Auth | Descrição |
|---|---|---|
| `GET /cifras/{song:slug}/pdf` | pública | PDF da cifra individual |
| `GET /caderno/{setlist}/pdf` | auth + dono | PDF do caderno completo |

### ChordDiagramSvg

- Renderiza diagramas de acordes como **HTML table** (não SVG) — DomPDF renderiza tabelas `display:inline-block` corretamente
- Entrada: nome do acorde, padrão de 6 chars do `ChordDictionary` (ex.: `x32010`), número da cejilha
- Saída: `<div style="display:inline-block">` contendo `<table style="width:66pt">` com linhas para nome, indicadores x/o, cásca (nut) e 4 casas
- Pontos de dedo: `background-color:#1a1a1a` no `<td>` — **não** usar caracteres Unicode (U+25CF não está no encoding das fontes embutidas do DomPDF)
- Cejilha (barre): todas as células da linha recebem fundo escuro
- Posições altas (casa > 4): grid desloca e exibe número da casa à esquerda em texto `{n}a`

### PdfController

- `song(Song $song)`: usa `defaultChord` (ou primeiro acorde disponível); se não houver acorde → 404
- `setlist(Setlist $setlist)`: protegido por `abort_unless(user_id)` + `auth` middleware; eager-load `songs.artist`, `songs.category`, `songs.chords`; usa `chords->firstWhere('is_default', true)` para evitar lazy load no loop
- Ambos usam `Pdf::loadView()->setPaper('a4', 'portrait')` e devolvem download com nome `{artista}-{titulo}.pdf` / `{caderno}.pdf`

### Templates PDF

- `resources/views/pdf/song.blade.php`: header (título, artista, tom/álbum/ano/dificuldade), conteúdo ChordPro via `{!! $html !!}`, seção de diagramas no rodapé, watermark
- `resources/views/pdf/setlist.blade.php`: capa com índice de duas colunas (número, tom, título, artista) + uma página A4 por música com `page-break-after: always`; mesma estrutura de conteúdo do template de cifra
- CSS dos templates: Arial/Helvetica, fundo branco, acordes em laranja `#e65c00`, seções com borda laranja; usa `display:inline-block` para pares acorde+letra e para diagramas; sem flexbox/grid (suporte limitado em DomPDF)
- **Margens**: `body { margin: 1.8cm 2.2cm; }` — `@page { margin }` não funciona no DomPDF mesmo com `default_media_type=print` (ver gotchas). Páginas 2+ do caderno usam `padding-top: 1.8cm` na `.song-page`
- **Diagramas**: cada diagrama tem `page-break-inside: avoid` no wrapper `<div>` e `.diagrams-section` também tem `page-break-inside: avoid` para evitar cortes entre páginas
- `config/dompdf.php` publicado com `default_media_type => 'print'`

### Limitações conhecidas do DomPDF (v2 / barryvdh v3)

| Limitação | Workaround |
|---|---|
| `@page { margin }` ignorado | Usar `body { margin }` para todas as páginas |
| Tabelas aninhadas (table dentro de td) não renderizam | Usar tabelas como filhos diretos de `<div>` |
| Tabela plana com muitas colunas (6+ por acorde) não renderiza | Quebrar em tabelas individuais por diagrama |
| `float:left` em divs que contêm tabelas não renderiza | Usar `display:inline-block` no wrapper |
| `display:inline-table` não suportado | Usar `display:inline-block` com tabela interna |
| `border-spacing` em tabelas ignorado | Usar `margin` ou `padding` nas células |
| U+25CF (●) e outros símbolos Unicode não aparecem | Usar `background-color` em `<td>` para pontos |
| Fontes externas (Google Fonts) não carregam | Usar Arial/Helvetica (fontes PDF padrão) |

---

## Modelos — pontos importantes

### `Artist`
- `booted()`: auto-gera `slug` no `creating` via `artist_slug()` (normaliza `&`/`+` → `e`)
- `$fillable`: name, slug, bio, bio_en, bio_es, bio_fr, photo_path, country, genre, musicbrainz_id

### `Song`
- `booted()`: auto-gera `slug` no `creating` se vazio
- `defaultChord()`: `HasOne` filtrado por `is_default = true`
- `setlists()`: `BelongsToMany` via `setlist_songs`
- `incrementViews()`: incrementa o contador atomicamente
- `extractChordList(string $content): array` — método estático; extrai acordes únicos ordenados do conteúdo ChordPro
- `$fillable`: artist_id, category_id, title, slug, key, difficulty, bpm, year, album, musicbrainz_id, youtube_id, is_published, views, chord_list
- `chord_list` cast `array`; populado automaticamente na importação, ao salvar pelo admin, e via `songs:backfill-chords`

### `Chord`
- `booted()` `saving`: garante no máximo um `is_default = true` por `song_id` (zera os outros)

### `Setlist`
- `$fillable`: user_id, name, is_public
- `user()`: `BelongsTo(User)`
- `songs()`: `BelongsToMany(Song)` via `setlist_songs`, ordenado por `position`

### `User`
- Implementa `FilamentUser` — `canAccessPanel()` retorna `true` apenas para `admin@querosene.test`
- `setlists()`: `HasMany(Setlist)`

### `MfaCode`
- `$fillable`: user_id, code (bcrypt hash), expires_at
- `isExpired()`: verifica se `expires_at` é passado

### `MfaTrustedDevice`
- `$fillable`: user_id, token_hash (SHA-256), expires_at
- `isValid(int $userId, string $rawToken): bool` — método estático
- `issue(int $userId): string` — cria registro e retorna token bruto para o cookie
- `pruneExpired(): int` — remove registros expirados

---

## Helpers globais (`app/helpers.php`)

Registrados via `composer.json > autoload > files`.

| Função | Descrição |
|---|---|
| `artist_slug(string $name): string` | Slug normalizado: substitui `&`/`+` por ` e ` antes de `Str::slug()`. Garante que "Bruno & Marrone" e "Bruno e Marrone" gerem o mesmo slug |
| `genre_title(string $genre): string` | Title case com exceções (preposições/artigos PT e EN). Usa `mb_ucfirst()` (PHP 8.3+) |
| `country_flag(string $code): string` | Converte código ISO-3166-1 (2 letras) em emoji de bandeira Unicode. Retorna o código original se inválido |

---

## Interface Web pública

- **Home** (`resources/views/home.blade.php`): ordem das seções — Novidades → Mais tocadas → Categorias; todas as seções usam `grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4`
- **Explorar** (`/explorar`): chord picker com 32 acordes comuns; selecionar acordes e buscar retorna apenas músicas cujo `chord_list` é subconjunto dos acordes selecionados
- **Song card** (`resources/views/partials/song-card.blade.php`): exibe badge do YouTube (ícone vermelho) quando `youtube_id` está preenchido; tem `data-title="{{ $song->title }}"` para o modal global ler o título
- **Player** (`resources/views/song/show.blade.php`): transposição, auto-scroll, tamanho de fonte, player YouTube flutuante e arrastável, diagramas de acordes em popup, botão **Salvar no Caderno**; barra de controles à direita com **PDF** (visível em todos os modos, auth-aware) + **Vídeo** + **Foco** (Foco oculto em embed)
- **Botão PDF auth-aware**: usuários autenticados recebem `<a target="_blank">` direto para o PDF; guests recebem botão com popover Alpine.js mostrando mensagem e links **Entrar** / **Criar conta** com `target="_top"` (navega o frame pai quando dentro do modal iframe)
- **Modo embed** (`?embed=1`): player sem nav/footer, `sticky top-0`, sem botão Salvar, sem sugestões, sem botão Foco; botão PDF visível (com gate de auth); layout `layouts/embed.blade.php` com `@livewireScripts` explícito (necessário para Alpine.js sem componente Livewire na página)
- **Modal global de cifra** (`layouts/app.blade.php`): qualquer link `/cifras/` no site é interceptado por um listener JS global; a cifra abre em iframe fullscreen com `?embed=1`; `z-index:200` garante sobreposição ao header; `overflow-hidden` no `<html>` evita dupla barra de rolagem; Ctrl/Cmd+clique abre normalmente (nova aba)
- **Calculadora de Capo** (`/calculadora-de-capo`, `resources/views/tools/capo.blade.php`): dois selects (tonalidade desejada + tonalidade que o músico sabe), botão inverter, card de resultado com badge fret + título/desc dinâmicos, tabela com mapa de acordes diatônicos (forma no braço → como soa com o capo), seção de dicas; totalmente i18n em PT/EN/ES/FR; link no nav (ícone de capo) e no footer
- **Artista** (`resources/views/artist/show.blade.php`): bandeira do país via `fi fi-{iso2}`, bio multilíngue com expand/collapse Alpine.js (botão oculto quando texto não é cortado), gênero via `genre_title()`
- **Fotos de artistas**: salvas em `storage/app/public/artists/{slug}.{ext}` · acessíveis via `Storage::disk('public')->url($artist->photo_path)` · symlink `public/storage` já criado
- Controllers web usam eager loading `with(['artist', 'category'])` em todas as listagens
- **CSS global**: `a, button { cursor: pointer }` via `@layer base` em `resources/css/app.css`
- **meta CSRF**: `<meta name="csrf-token">` no layout (usado pelo fetch do toggle de caderno)

---

## Internacionalização (i18n)

Arquivos em `lang/{pt,en,es,fr}/ui.php`. O locale é detectado automaticamente pelo browser/Accept-Language ou pela rota de idioma.

### Padrão de uso

- **Strings estáticas**: `{{ __('ui.section.key') }}` diretamente no Blade
- **Strings dinâmicas para Alpine.js**: montar array PHP em bloco `@php`, serializar no `<script>` com `{!! json_encode($data, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!}` e ler dentro da função Alpine — **não passar via atributo `x-data`**

```blade
@php
    $i18n = [
        'chave' => __('ui.secao.chave'),
        'lista' => __('ui.secao.lista'),   {{-- arrays PHP viram arrays JS --}}
    ];
@endphp
<div x-data="minhaFunc()" ...>...</div>

@push('scripts')
<script>
function minhaFunc() {
    const i18n = {!! json_encode($i18n, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) !!};
    // usar i18n.chave, i18n.lista etc.
}
</script>
@endpush
```

### Substituição de placeholders em strings dinâmicas

Strings com placeholders (`:source`, `:fret`, `:fretLabel`) são substituídas em JS com `split/join` para suportar múltiplas ocorrências:

```js
function tpl(str, vars) {
    return Object.entries(vars).reduce((s, [k, v]) => s.split(':' + k).join(String(v)), str);
}
// Atenção: passar fretLabel ANTES de fret no objeto vars — fretLabel contém :fret como substring
tpl(str, { fretLabel: '5ª', fret: 5, source: 'G', target: 'C' })
```

### Calculadora de Capo — detalhes de i18n

- `keys`: array de 12 labels por idioma (ex: PT `'C (Dó)'`, EN `'C'`, ES `'C (Do)'`)
- `fret_ordinals`: array de 12 ordinais `['', '1ª', '2ª', ...]` — índice 0 nunca usado (fret 0 = sem capo)
- `badge_suffix`: só o substantivo da unidade (`CASA` / `FRET` / `TRASTE` / `CASE`) — o marcador ordinal (ª, th, º, e/re) é extraído de `fret_ordinals[fret]` removendo os dígitos: `(i18n.fret_ordinals[fret] ?? '').replace(/^\d+/, '')`

---

## Seeders

```
DatabaseSeeder
  ├── Cria user: admin@querosene.test / password
  ├── CategorySeeder  — 8 categorias (Rock Nacional, MPB, Sertanejo…)
  ├── ArtistSeeder    — 5 artistas BR (Legião, Raul, Roberto Carlos, Djavan, Skank)
  └── SongSeeder      — 10 músicas com ChordPro completo (2 por artista)
```

> **Atenção:** Os seeders definem `slug` explicitamente via `Str::slug()` — não dependem do `booted()` para evitar rejeição do MySQL no modo strict (NOT NULL sem default). Após `migrate:fresh --seed`, rodar `songs:backfill-chords` para popular `chord_list`.

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
| Bio EN/ES/FR não gerada pelo Enriquecer | MusicBrainz usa relação `wikidata` (não `wikipedia`) — `biosFromWikidata()` trata este caso |
| Código MFA não chega por email | Verificar `MAIL_MAILER` no `.env`; em dev usar `log` e ler em `storage/logs/laravel.log` |
| Usuário público consegue acessar `/admin` | `canAccessPanel()` no `User` model restringe por email — não remover |
| Alpine.js não funciona no embed (`?embed=1`) | `layouts/embed.blade.php` tem `@livewireScripts` explícito — Livewire só injeta Alpine em páginas com componente `@livewire()` |
| Modal de cifra aparece atrás do header | Modal usa `style="z-index:200"` inline — não substituir por classe Tailwind (pode não compilar corretamente) |
| Dupla barra de rolagem no modal | `overflow-hidden` é aplicado ao `<html>` ao abrir e removido ao fechar — os três caminhos de fechar (X, Esc, `open=false`) devem todos remover a classe |
| Botão PDF abre no modal | O link usa `target="_blank"`, que já impede a interceptação do listener JS (só intercepta sem `target="_blank"`) |
| DomPDF não renderiza flexbox/grid | Usar `display:inline-block` nos templates PDF — DomPDF v2+ suporta inline-block |
| `@page { margin }` ignorado no DomPDF | Usar `body { margin: 1.8cm 2.2cm; }` — funciona em todas as páginas; `padding-top` por página nos cadernos |
| Diagramas de acorde cortados entre páginas no PDF | `page-break-inside:avoid` na `.diagrams-section` e em cada wrapper `<div>` de diagrama |
| Pontos de acorde aparecem como `?` no PDF | U+25CF não está no encoding DomPDF — usar `background-color:#1a1a1a` no `<td>` em vez de `&#9679;` |
| SVG de diagrama não aparece no PDF | ChordDiagramSvg usa HTML tables (não SVG) — não tentar refatorar para SVG |
| `@json()` quebra Alpine.js em `x-data` | `@json` usa `JSON_HEX_QUOT` por padrão → `"` vira `"`, que é JavaScript inválido fora de string literal; usar `{!! json_encode($data, JSON_HEX_TAG \| JSON_UNESCAPED_UNICODE) !!}` dentro do `<script>` e ler via variável na função Alpine |
| `@json([...])` multilinha em atributo HTML causa erro de parse Blade | Hoistear para bloco `@php $var = [...]; @endphp` e usar `@json($var)` — ou melhor, usar o padrão de script acima |

---

## O que está pronto / pendente

### ✅ Concluído
- Migrations, Models, Seeders
- API REST (11 endpoints)
- Admin Filament (4 Resources + wizard de importação) — restrito a admin por `canAccessPanel()`
- Importadores: Cifra Club TXT (com tablaturas), ChordPro, MusicXML, ZIP batch
- Queue job com `ProcessBatchImportJob` (MusicBrainz + TheAudioDB + YouTube + inferência de tom + `chord_list`)
- Enriquecimento automático na importação + botão Enriquecer manual em músicas e artistas
- Bio do artista em 4 idiomas (PT/EN/ES/FR) via TheAudioDB + Wikipedia/Wikidata
- Foto do artista: baixada automaticamente do TheAudioDB
- Slug de músicas com desambiguação por artista (versões cover não conflitam)
- Renderizador ChordPro com tablatura, seções, anotações e validação de acordes
- Interface Web pública: player com transposição, auto-scroll, YouTube, diagramas
- Home com grid responsivo (Novidades → Mais tocadas → Categorias)
- **Explorar cifras** (`/explorar`): filtro por acordes conhecidos (chord picker)
- **Cadernos** (`/caderno`): criar, renomear, excluir cadernos; adicionar/remover músicas (limite 30 por caderno); lista com tom, categoria e dificuldade por linha
- **Auth pública**: cadastro, login com MFA por email (código fixo `123456` em dev), dispositivo confiável por 30 dias
- **Modal global de cifra**: todas as cifras do site abrem em iframe fullscreen ao clicar; Ctrl+clique abre em nova aba normalmente
- **Modo embed** (`?embed=1`): layout mínimo sem nav/footer para o iframe do modal
- **Exportação PDF**: cifra individual (`GET /cifras/{slug}/pdf`, pública) e caderno completo (`GET /caderno/{id}/pdf`, requer auth); diagramas de acordes gerados server-side por `ChordDiagramSvg` (HTML tables, não SVG); capa com índice de 2 colunas no PDF de caderno; margens via `body { margin }` (não `@page`)
- **Botão PDF no modal**: visível em todos os modos (embed inclusive); autenticados → download direto; guests → popover com links para login/cadastro (`target="_top"` para navegar o frame pai)
- **Calculadora de Capo** (`/calculadora-de-capo`): selects de tonalidade, mapa de acordes diatônicos, dicas de uso; i18n PT/EN/ES/FR; link no nav e footer
- Bandeira do país na página do artista (via `flag-icons`)
- Indexes de performance no banco
- SSL configurado no código para funcionar independente do ambiente Windows

### ⏳ Pendente
- App Flutter (repo separado) — estrutura, telas, player, auto-scroll
- GuitarPro GP5 converter (v1.1)
- Auto-scroll por BPM (v1.1)
- Auth Sanctum para endpoints da API (atualmente públicos)
- Limpeza periódica de `mfa_codes` e `mfa_trusted_devices` expirados (scheduled command)
