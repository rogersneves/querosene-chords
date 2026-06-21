<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Chord;
use App\Models\Song;
use Illuminate\Database\Seeder;

class SongSeeder extends Seeder
{
    public function run(): void
    {
        $rockNacional = Category::where('name', 'Rock Nacional')->first();
        $mpb = Category::where('name', 'MPB')->first();

        $songs = [
            // Legião Urbana
            [
                'artist' => 'Legião Urbana',
                'category' => 'Rock Nacional',
                'title' => 'Tempo Perdido',
                'key' => 'C',
                'difficulty' => 'intermediário',
                'bpm' => 75,
                'year' => 1987,
                'content' => <<<'CHORDPRO'
{title: Tempo Perdido}
{artist: Legião Urbana}
{key: C}
{tempo: 75}

{start_of_verse}
[C]Todos os dias quando a[Am7]cordo
[Bm]Não tenho mais o tempo que pas[Em]sou
[C]Mas tenho muito [Am7]tempo
[Bm]Temos todo o tempo do [Em]mundo
[C]Todos os dias antes de dor[Am7]mir
[Bm]Lembro e esqueço como foi o [Em]dia
[C]Sempre em [Am7]frente
[Bm]Não temos tempo a per[Em]der
[C]Nosso suor sa[Am7]grado
[Bm]É bem mais belo que esse
[Em]Sangue amargo
[C]E tão sé[Am7]rio
{end_of_verse}

{start_of_chorus}
[Bm]E sel[Em]va__gem
[Bm]E sel[Em]va__gem
[Bm]E selvagem
{end_of_chorus}
CHORDPRO,
            ],
            [
                'artist' => 'Legião Urbana',
                'category' => 'Rock Nacional',
                'title' => 'Pais e Filhos',
                'key' => 'E',
                'difficulty' => 'intermediário',
                'bpm' => 80,
                'year' => 1989,
                'content' => <<<'CHORDPRO'
{title: Pais e Filhos}
{artist: Legião Urbana}
{key: E}
{tempo: 80}

{start_of_verse}
[E]Quem tem um sonho não dan[A]ça
[E]Quem não dança não sabe[A] o que quer
[E]Eu fico com a pu[A]reza
Da resposta das crian[E]ças[A]
É a vida[E], é bonita[A]
E é bo[E]nita[A][E]
{end_of_verse}

{start_of_chorus}
[A]Viver e não ter a ver[E]gonha de ser feliz
[A]Cantar e cantar e cantar
A beleza de ser um e[E]terno aprendiz
[A]Ah meu Deus! Eu sei, eu sei
[A]Que a vida devia ser bem melhor e será
Mas isso não impede que eu repita
É bonita, é bo[E]nita
E é bo[E]nita
{end_of_chorus}
CHORDPRO,
            ],

            // Raul Seixas
            [
                'artist' => 'Raul Seixas',
                'category' => 'Rock Nacional',
                'title' => 'Metamorfose Ambulante',
                'key' => 'A',
                'difficulty' => 'iniciante',
                'bpm' => 120,
                'year' => 1973,
                'content' => <<<'CHORDPRO'
{title: Metamorfose Ambulante}
{artist: Raul Seixas}
{key: A}
{tempo: 120}

{start_of_verse}
[A]Eu prefiro ser essa metamor[D]fose ambulante
Do que ter aquela [E]velha opinião formada sobre [A]tudo
[A]Eu prefiro ser essa metamor[D]fose ambulante
Do que ter aquela [E]velha opinião formada sobre [A]tudo
{end_of_verse}

{start_of_verse}
[A]Sobre o que é o [D]amor
Sobre o que eu nem [E]sei quem sou
Se hoje eu sou estre[A]la
Amanhã já se a[D]pagou
Se hoje eu te o[E]deio
Amanhã lhe tenho a[A]mor
Lhe tenho a[D]mor
Lhe tenho a[E]mor
{end_of_verse}
CHORDPRO,
            ],
            [
                'artist' => 'Raul Seixas',
                'category' => 'Rock Nacional',
                'title' => 'Ouro de Tolo',
                'key' => 'D',
                'difficulty' => 'iniciante',
                'bpm' => 90,
                'year' => 1973,
                'content' => <<<'CHORDPRO'
{title: Ouro de Tolo}
{artist: Raul Seixas}
{key: D}
{tempo: 90}

{start_of_verse}
[D]Eu devia estar feliz
Porque eu tenho [G]tudo que um homem quer
Tenho dinheiro, [A]saúde e fama
Mas não me [D]sinto bem
{end_of_verse}

{start_of_chorus}
[G]Meu [D]Deus do céu!
Eu precisei ser [A]isso que eu sou
Para entender que [D]esse trem
Que eu perdi[A] um dia
Pra mim foi [D]bom
{end_of_chorus}
CHORDPRO,
            ],

            // Roberto Carlos
            [
                'artist' => 'Roberto Carlos',
                'category' => 'MPB',
                'title' => 'Emoções',
                'key' => 'G',
                'difficulty' => 'iniciante',
                'bpm' => 70,
                'year' => 1977,
                'content' => <<<'CHORDPRO'
{title: Emoções}
{artist: Roberto Carlos}
{key: G}
{tempo: 70}

{start_of_verse}
[G]Quantas emoções eu vivi
[Em]Quantos sonhos realizei
[C]Quando cantei pra você
[D]Quando em seus braços me a[G]ninei
{end_of_verse}

{start_of_chorus}
[C]Eu só quero te a[G]mar
[Am]Meu amor é assim
[D]E o que você[G] me fez
Não foi por a[Em]caso
[C]Você chegou pra ficar
[D]Para ficar em mim
{end_of_chorus}
CHORDPRO,
            ],
            [
                'artist' => 'Roberto Carlos',
                'category' => 'MPB',
                'title' => 'Jesus Cristo',
                'key' => 'C',
                'difficulty' => 'iniciante',
                'bpm' => 65,
                'year' => 1970,
                'content' => <<<'CHORDPRO'
{title: Jesus Cristo}
{artist: Roberto Carlos}
{key: C}
{tempo: 65}

{start_of_verse}
[C]Jesus Cristo, que morreu na cruz
[Am]E que nos deu a re[F]denção
[G]Jesus Cristo, que é a grande [C]luz
Que nos guia nessa i[G]lu[C]são
{end_of_verse}

{start_of_chorus}
[F]A vida é sagra[C]da
Cada amor é sa[G]grado
Cada flor nasce[Am] bela
Para ser ado[F]rada
{end_of_chorus}
CHORDPRO,
            ],

            // Djavan
            [
                'artist' => 'Djavan',
                'category' => 'MPB',
                'title' => 'Flor de Lis',
                'key' => 'F',
                'difficulty' => 'avançado',
                'bpm' => 85,
                'year' => 1976,
                'content' => <<<'CHORDPRO'
{title: Flor de Lis}
{artist: Djavan}
{key: F}
{tempo: 85}

{start_of_verse}
[Fmaj7]Você, que tem a alma mais bela
[Em7b5][A7]Que eu já [Dm7]vi
[Gm7]Você que ilumina estrela
[C7]Pro meu [Fmaj7]país
{end_of_verse}

{start_of_chorus}
[Bbmaj7]Flor de[Am7] lis
[Dm7]Você tem o perfume das rosas
[Gm7]E a cor[C7] do anis
[Fmaj7]Flor de lis
{end_of_chorus}
CHORDPRO,
            ],
            [
                'artist' => 'Djavan',
                'category' => 'MPB',
                'title' => 'Oceano',
                'key' => 'D',
                'difficulty' => 'avançado',
                'bpm' => 78,
                'year' => 1989,
                'content' => <<<'CHORDPRO'
{title: Oceano}
{artist: Djavan}
{key: D}
{tempo: 78}

{start_of_verse}
[Dmaj7]A distância[F#m7] que nos separa
[Gmaj7]Tão longe, [F#m7]tão perto
[Em7]Eu sinto seu per[A7]fume
Mesmo sem você a[Dmaj7]qui
{end_of_verse}

{start_of_chorus}
[Gmaj7]Que oceano é es[F#m7]se
[Em7]Que faz de você
[A7]A minha meta[Dmaj7]de
{end_of_chorus}
CHORDPRO,
            ],

            // Skank
            [
                'artist' => 'Skank',
                'category' => 'Rock Nacional',
                'title' => 'É Uma Partida de Futebol',
                'key' => 'G',
                'difficulty' => 'iniciante',
                'bpm' => 95,
                'year' => 1994,
                'content' => <<<'CHORDPRO'
{title: É Uma Partida de Futebol}
{artist: Skank}
{key: G}
{tempo: 95}

{start_of_verse}
[G]E aí meu amor
Quando eu voltar[D]
Vou te trazer um pôs[Em]ter
Se o nosso[C] time
[G]Ganhar mais uma vez
Eu prometo[D] não[Em] beber[C]
{end_of_verse}

{start_of_chorus}
[G]É uma partida[D] de futebol
[Em]Mas pra mim[C] é muito mais do que isso
[G]Uma partida[D] de futebol
[Em]Com abraços[C] e muito sorriso
{end_of_chorus}
CHORDPRO,
            ],
            [
                'artist' => 'Skank',
                'category' => 'Rock Nacional',
                'title' => 'Garota Nacional',
                'key' => 'A',
                'difficulty' => 'iniciante',
                'bpm' => 100,
                'year' => 1994,
                'content' => <<<'CHORDPRO'
{title: Garota Nacional}
{artist: Skank}
{key: A}
{tempo: 100}

{start_of_verse}
[A]Você sabia que você me en[D]louquece
[E]Com esse sorriso[A]
[A]Eu fico louco quan[D]do você aparece
[E]Com esse sorriso[A]
{end_of_verse}

{start_of_chorus}
[D]Garota nacio[A]nal
[E]Você é tudo[A] que eu quero
[D]Me faz tão espe[A]cial
[E]O meu amor[A] é verdadeiro
{end_of_chorus}
CHORDPRO,
            ],
        ];

        foreach ($songs as $data) {
            $artist = Artist::where('name', $data['artist'])->first();
            $category = Category::where('name', $data['category'])->first();

            if (!$artist) continue;

            $song = Song::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['title'])],
                [
                    'artist_id' => $artist->id,
                    'category_id' => $category?->id,
                    'title' => $data['title'],
                    'key' => $data['key'],
                    'difficulty' => $data['difficulty'],
                    'bpm' => $data['bpm'],
                    'year' => $data['year'],
                    'is_published' => true,
                ]
            );

            if ($song->wasRecentlyCreated) {
                Chord::create([
                    'song_id' => $song->id,
                    'content' => $data['content'],
                    'version_label' => 'Padrão',
                    'source' => 'manual',
                    'is_default' => true,
                ]);
            }
        }
    }
}
