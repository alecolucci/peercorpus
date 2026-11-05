<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleImportController extends Controller
{
    /**
     * Passo 1: Recebe o arquivo e converte para um JSON temporário
     */
    public function uploadFile(Request $request, Project $project)
    {
        $request->validate([
            'bibliography_file' => 'required|file|max:10240',
        ]);

        $content = file_get_contents($request->file('bibliography_file')->getRealPath());
        
        // Faz o parse do arquivo na memória RAM (rápido)
        $parsedArticles = $this->parseRisFile($content, $project->id);
        $totalArticles = count($parsedArticles);

        if ($totalArticles === 0) {
            return response()->json(['error' => 'Nenhum artigo encontrado no arquivo.'], 400);
        }

        // Salva os dados em um arquivo JSON temporário no storage local da Hostinger
        $tempFileName = 'import_' . $project->id . '_' . time() . '.json';
        Storage::put('temp/' . $tempFileName, json_encode($parsedArticles));

        return response()->json([
            'temp_file' => $tempFileName,
            'total_articles' => $totalArticles,
            'batch_size' => 200 // Quantos artigos inserir por requisição
        ]);
    }

    /**
     * Passo 2: O Vue chamará esta rota repetidamente (ex: página 1, 2, 3...)
     */
    public function processBatch(Request $request, Project $project)
    {
        $request->validate([
            'temp_file' => 'required|string',
            'page' => 'required|integer|min:1',
            'batch_size' => 'required|integer'
        ]);

        $path = 'temp/' . $request->temp_file;
        
        // PROTEÇÃO: Se o arquivo sumiu, é porque a importação já finalizou 
        // em um lote anterior. Retornamos 'completed' em vez de erro 404.
        if (!Storage::exists($path)) {
            return response()->json(['status' => 'completed']);
        }

        // Lê o arquivo JSON temporário
        $articles = json_decode(Storage::get($path), true);
        $totalArticles = count($articles);
        $offset = ($request->page - 1) * $request->batch_size;
        $batch = array_slice($articles, $offset, $request->batch_size);

        if (empty($batch)) {
            Storage::delete($path);
            return response()->json(['status' => 'completed']);
        }

        // --- LÓGICA DE DEDUPLICAÇÃO ---
        $existingRecords = DB::table('articles')->where('project_id', $project->id)->get(['doi', 'title']);
        $existingDois = $existingRecords->whereNotNull('doi')->pluck('doi')->toArray();
        $existingTitles = $existingRecords->pluck('title')->map(fn($t) => Str::slug($t))->toArray();

        $newArticles = [];
        $duplicatesCount = 0;

        foreach ($batch as $article) {
            $isDuplicate = false;
            $normalizedTitle = Str::slug($article['title']);

            if (!empty($article['doi']) && in_array($article['doi'], $existingDois)) {
                $isDuplicate = true;
            } elseif (in_array($normalizedTitle, $existingTitles)) {
                $isDuplicate = true;
            }

            if ($isDuplicate) {
                $duplicatesCount++;
            } else {
                $newArticles[] = $article;
                if (!empty($article['doi'])) $existingDois[] = $article['doi'];
                $existingTitles[] = $normalizedTitle;
            }
        }

        // Inserção no banco deste lote
        if (!empty($newArticles)) {
            Article::insert($newArticles);
        }

        // TÉRMINO ANTECIPADO: Se já processamos todos os itens neste exato lote,
        // deletamos o arquivo agora para não precisar de uma requisição extra (Lote 3 vazio).
        $isLastBatch = ($offset + $request->batch_size) >= $totalArticles;
        
        if ($isLastBatch) {
            Storage::delete($path);
            return response()->json([
                'status' => 'completed',
                'inserted' => count($newArticles),
                'duplicates' => $duplicatesCount
            ]);
        }

        return response()->json([
            'status' => 'processing',
            'inserted' => count($newArticles),
            'duplicates' => $duplicatesCount
        ]);
    }

    /**
     * Lógica simplificada para ler arquivos .ris
     */
    private function parseRisFile($content, $projectId)
    {
        $articles = [];
        $currentArticle = [];
        
        // Divide o arquivo em linhas
        $lines = explode(PHP_EOL, $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 6) continue;

            // Extrai a tag (ex: "TI") e o valor (ex: "O impacto dos algoritmos...")
            $tag = substr($line, 0, 2);
            $value = trim(substr($line, 6));

            switch ($tag) {
                case 'TY': // Type of reference (inicia um novo artigo)
                    if (!empty($currentArticle)) {
                        $articles[] = $currentArticle;
                    }
                    $currentArticle = [
                        'project_id' => $projectId,
                        'title' => '',
                        'abstract' => '',
                        'authors' => '',
                        'publication_year' => null,
                        'doi' => null,
                        'status' => 'included',
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ];
                    break;
                case 'TI': // Title
                    $currentArticle['title'] = $value;
                    break;
                case 'AB': // Abstract
                    $currentArticle['abstract'] = $value;
                    break;
                case 'AU': // Author (pode haver vários, vamos concatenar)
                    $currentArticle['authors'] .= $value . '; ';
                    break;
                case 'PY': // Publication Year
                    $cleanYear = preg_replace('/[^0-9]/', '', $value);
                    $currentArticle['publication_year'] = !empty($cleanYear) ? (int) substr($cleanYear, 0, 4) : null;
                    break;
                case 'DO': // DOI
                    $currentArticle['doi'] = $value;
                    break;
                case 'ER': // End of Reference
                    $currentArticle['authors'] = rtrim($currentArticle['authors'], '; ');
                    $articles[] = $currentArticle;
                    $currentArticle = [];
                    break;
            }
        }

        return $articles;
    }
}