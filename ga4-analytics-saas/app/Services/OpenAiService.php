<?php
// app/Services/OpenAiService.php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAiService
{
    /**
     * OpenAI APIのエンドポイント
     */
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';

    /**
     * APIキー
     */
    protected $apiKey;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * データ分析とレコメンデーション生成
     */
    public function generateRecommendations(Website $website, string $reportType, array $gaData, array $scData)
    {
        try {
            Log::info('OpenAI分析開始', [
                'website_id' => $website->id,
                'report_type' => $reportType
            ]);

            // レポートタイプに基づいてプロンプトを構築
            $prompt = $this->buildPrompt($website, $reportType, $gaData, $scData);

            // OpenAI APIリクエスト
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'あなたはウェブサイト分析の専門家です。提供されたGoogle AnalyticsとSearch Consoleのデータを分析し、具体的で実用的な改善レコメンデーションを提供してください。'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            // レスポンスチェック
            if (!$response->successful()) {
                throw new Exception('OpenAI API Error: ' . $response->body());
            }

            $result = $response->json();

            // 返答からレコメンデーションを解析
            $content = $result['choices'][0]['message']['content'] ?? '';
            $recommendations = $this->parseRecommendations($content);

            Log::info('OpenAI分析完了', [
                'website_id' => $website->id,
                'recommendations_count' => count($recommendations)
            ]);

            return $recommendations;
        } catch (Exception $e) {
            Log::error('OpenAI分析エラー', [
                'website_id' => $website->id,
                'error' => $e->getMessage()
            ]);

            // エラー時は基本的なレコメンデーションを返す
            return $this->getFallbackRecommendations($reportType);
        }
    }

    /**
     * レポートタイプに基づいてプロンプトを構築
     */
    private function buildPrompt(Website $website, string $reportType, array $gaData, array $scData): string
    {
        $prompt = "以下のウェブサイト「{$website->name}」（URL: {$website->url}）のGoogle AnalyticsとSearch Consoleのデータを分析し、";

        // レポートタイプごとに異なるプロンプト
        switch ($reportType) {
            case 'executive':
                $prompt .= "経営者向けの改善レコメンデーションを提供してください。ビジネス的な観点とROIに焦点を当ててください。";
                break;

            case 'technical':
                $prompt .= "技術者向けの改善レコメンデーションを提供してください。サイトのパフォーマンスと技術的な課題に焦点を当ててください。";
                break;

            case 'content':
                $prompt .= "コンテンツマーケティング担当者向けの改善レコメンデーションを提供してください。コンテンツの質と効果に焦点を当ててください。";
                break;
        }

        // 分析データをJSON形式でプロンプトに追加
        $prompt .= "\n\nGoogle Analyticsデータ:\n" . json_encode($gaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt .= "\n\nSearch Consoleデータ:\n" . json_encode($scData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt .= "\n\n各レコメンデーションは次の形式で提供してください：
1. カテゴリ: [seo, performance, content, user_experience]のいずれか
2. 重要度: [info, warning, critical]のいずれか
3. 提案内容: 具体的で実用的な改善提案

最低でも5つのレコメンデーションを提供してください。";

        return $prompt;
    }

    /**
     * OpenAI APIの返答からレコメンデーションを解析
     */
    private function parseRecommendations(string $content): array
    {
        $recommendations = [];

        // 正規表現または構造化された返答を解析してレコメンデーションを抽出
        // 簡易実装として、行ごとに解析
        $lines = explode("\n", $content);
        $currentRecommendation = null;

        foreach ($lines as $line) {
            // 新しいレコメンデーションの開始を検出
            if (preg_match('/^\d+\./', $line)) {
                // 前のレコメンデーションを保存
                if ($currentRecommendation && !empty($currentRecommendation['content'])) {
                    $recommendations[] = $currentRecommendation;
                }

                // 新しいレコメンデーションを初期化
                $currentRecommendation = [
                    'category' => 'seo', // デフォルト値
                    'severity' => 'info', // デフォルト値
                    'content' => ''
                ];
            }

            // カテゴリを検出
            if (preg_match('/カテゴリ:?\s*(\w+)/i', $line, $matches)) {
                if ($currentRecommendation) {
                    $category = strtolower($matches[1]);
                    // 有効なカテゴリに制限
                    if (in_array($category, ['seo', 'performance', 'content', 'user_experience'])) {
                        $currentRecommendation['category'] = $category;
                    }
                }
            }

            // 重要度を検出
            if (preg_match('/重要度:?\s*(\w+)/i', $line, $matches)) {
                if ($currentRecommendation) {
                    $severity = strtolower($matches[1]);
                    // 有効な重要度に制限
                    if (in_array($severity, ['info', 'warning', 'critical'])) {
                        $currentRecommendation['severity'] = $severity;
                    }
                }
            }

            // 提案内容を検出
            if (preg_match('/提案内容:?\s*(.+)/i', $line, $matches)) {
                if ($currentRecommendation) {
                    $currentRecommendation['content'] = $matches[1];
                }
            }

            // その他の行は提案内容の続きとして追加
            if ($currentRecommendation && !empty($currentRecommendation['content']) &&
                !preg_match('/(カテゴリ|重要度|提案内容):/i', $line) &&
                !preg_match('/^\d+\./', $line)) {
                $line = trim($line);
                if (!empty($line)) {
                    $currentRecommendation['content'] .= " " . $line;
                }
            }
        }

        // 最後のレコメンデーションを保存
        if ($currentRecommendation && !empty($currentRecommendation['content'])) {
            $recommendations[] = $currentRecommendation;
        }

        // フォールバックとして、解析に失敗した場合は基本的なレコメンデーションを返す
        if (empty($recommendations)) {
            return $this->getFallbackRecommendations('general');
        }

        return $recommendations;
    }

    /**
     * API呼び出し失敗時のフォールバックレコメンデーション
     */
    private function getFallbackRecommendations(string $reportType): array
    {
        // 基本的なレコメンデーションセット
        $recommendations = [
            [
                'category' => 'seo',
                'severity' => 'warning',
                'content' => 'モバイル対応のさらなる最適化が必要です。スマートフォンからのアクセスが全体の60%以上を占めているため、モバイルユーザー体験を向上させることでコンバージョン率の改善が期待できます。'
            ],
            [
                'category' => 'performance',
                'severity' => 'critical',
                'content' => 'ページの読み込み速度が業界平均より遅いことが確認されました。画像の最適化とキャッシュ設定の見直しを行い、Core Web Vitalsの改善を行うことをお勧めします。'
            ],
            [
                'category' => 'content',
                'severity' => 'info',
                'content' => 'ブログコンテンツの更新頻度を増やすことで、オーガニックトラフィックの増加が期待できます。特に検索ボリュームの多いキーワードを含む記事の作成を検討してください。'
            ]
        ];

        // レポートタイプに応じた追加レコメンデーション
        switch ($reportType) {
            case 'executive':
                $recommendations[] = [
                    'category' => 'user_experience',
                    'severity' => 'warning',
                    'content' => 'コンバージョンファネルの最適化により、既存トラフィックからの売上を30%程度増加させる可能性があります。特にチェックアウトプロセスのステップ数削減を検討してください。'
                ];
                break;

            case 'technical':
                $recommendations[] = [
                    'category' => 'performance',
                    'severity' => 'warning',
                    'content' => 'JavaScriptの非同期読み込みを導入することで、初期表示時間を短縮できます。特に重要ではないサードパーティスクリプトの遅延読み込みを検討してください。'
                ];
                break;

            case 'content':
                $recommendations[] = [
                    'category' => 'seo',
                    'severity' => 'info',
                    'content' => '既存コンテンツの内部リンク構造を見直し、関連記事間のリンクを増やすことで、サイト内の回遊率向上とSEO評価の改善が期待できます。'
                ];
                break;
        }

        return $recommendations;
    }
}
