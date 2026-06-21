# Querosene Chords
> *Dê um gás na sua música*

Plataforma de cifras musicais com API REST, painel administrativo e app Android.

## Stack
- **Backend:** Laravel 13 + MySQL
- **Admin:** Filament 3
- **Web público:** Livewire + TailwindCSS
- **App:** Flutter (Android) — repositório separado
- **Queue:** Laravel Jobs com driver `database`

## Requisitos
- PHP 8.3+
- MySQL 8.0+
- Composer 2.x
- Node.js 20+ e NPM

## Instalação

```bash
# 1. Clonar e instalar dependências
composer install
npm install

# 2. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 3. Criar banco de dados MySQL
# CREATE DATABASE querosene_chords CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Configurar .env com credenciais do banco
# DB_DATABASE=querosene_chords / DB_USERNAME=root / DB_PASSWORD=

# 5. Migrations + tabela de jobs + seeders
php artisan migrate
php artisan queue:table
php artisan migrate
php artisan db:seed

# 6. Storage link
php artisan storage:link

# 7. Build dos assets
npm run build
```

## Executar em desenvolvimento

```bash
# Worker de filas (necessário para importações em lote)
php artisan queue:work --queue=imports,default --timeout=300
```

## Acesso

| URL | Descrição |
|-----|-----------|
| `http://querosene.test` | Site público |
| `http://querosene.test/admin` | Painel Filament |
| `http://querosene.test/api/v1/songs` | API REST |

**Credenciais admin padrão (seed):**
- Email: `admin@querosene.test`
- Senha: `password`

## API REST

Base URL: `/api/v1` — Rate limit: 60 req/min por IP — CORS aberto para todas as origens.

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/songs` | Lista com filtros: `?q=`, `?artist_id=`, `?category_id=`, `?key=`, `?difficulty=`, `?sort=` |
| GET | `/songs/{slug}` | Cifra completa (incrementa views) |
| GET | `/songs/{slug}/suggestions` | 5 sugestões relacionadas |
| GET | `/songs/{slug}/chord-diagrams` | Diagramas dos acordes |
| GET | `/artists` | Lista paginada |
| GET | `/artists/{slug}` | Detalhe do artista |
| GET | `/artists/{slug}/songs` | Músicas do artista |
| GET | `/categories` | Todas as categorias |
| GET | `/categories/{slug}/songs` | Músicas por categoria |
| GET | `/search?q=&type=songs\|artists\|all` | Busca fulltext |
| GET | `/featured` | Top 10 mais vistas |

## Importação de Cifras

| Formato | Extensões | Status |
|---------|-----------|--------|
| Cifra Club TXT | `.txt` | Disponível |
| ChordPro | `.cho`, `.chopro`, `.chordpro` | Disponível |
| MusicXML | `.xml`, `.mxl` | Disponível |
| ZIP (lote) | `.zip` | Disponível |
| GuitarPro | `.gp`, `.gp4`, `.gp5` | v1.1 |

Acesse **Admin → Importações → Nova Importação** para importar arquivos.

## Estrutura

```
app/Services/Import/    # Conversores de formato
app/Jobs/               # ProcessBatchImportJob
app/Http/Controllers/Api/  # API REST
app/Filament/           # Painel admin
database/migrations/    # 6 migrations
database/seeders/       # 8 categorias, 5 artistas, 10 músicas
```
