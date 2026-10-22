<?php

namespace App\Jobs;

use App\Models\Article;
use App\Events\ImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessArticleImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Define um tempo limite maior (ex: 10 minutos) para ficheiros gigantes
    public $timeout = 600; 

    protected $filePath;
    protected $projectId;

    public function __construct($filePath, $projectId)
    {
        $this->filePath = $filePath;
        $this->projectId = $projectId;
    }

    public function handle()
    {
        $content = Storage::get($this->filePath);

        $parsedArticles = $this->parseRisFile($content, $this->projectId);

        $existingRecords = DB::table('articles')
            ->where('project_id', $this->projectId)
            ->get(['doi', 'title']);
        
        $existingDois = $existingRecords->whereNotNull('doi')->pluck('doi')->toArray();
        
        $existingTitles = $existingRecords->pluck('title')->map(function($title) {
            return Str::slug($title);
        })->toArray();

        $newArticles = [];
        
        foreach ($parsedArticles as $article) {
            $isDuplicate = false;

            if (!empty($article['doi']) && in_array($article['doi'], $existingDois)) {
                $isDuplicate = true;
            }

            $normalizedTitle = Str::slug($article['title']);
            if (!$isDuplicate && in_array($normalizedTitle, $existingTitles)) {
                $isDuplicate = true;
            }

            if (!$isDuplicate) {
                $newArticles[] = $article;
                if (!empty($article['doi'])) {
                    $existingDois[] = $article['doi'];
                }
                $existingTitles[] = $normalizedTitle;
            }
        }

        if (!empty($newArticles)) {
            foreach (array_chunk($newArticles, 500) as $chunk) {
                Article::insert($chunk);
            }
        }

        Storage::delete($this->filePath);

        $importedCount = count($newArticles);

        ImportCompleted::dispatch($this->projectId, "Importação concluída. {$importedCount} novos artigos.");
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
                        'status' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
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
                    $currentArticle['publication_year'] = substr($value, 0, 4); // Pega apenas o ano
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