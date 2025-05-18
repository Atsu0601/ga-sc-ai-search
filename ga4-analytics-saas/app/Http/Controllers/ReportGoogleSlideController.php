<?php

namespace App\Http\Controllers;

use App\Models\AnalysisReport;
use Google_Client;
use Google_Service_Slides;
use Google_Service_Drive;
use Google_Service_Drive_Permission;
use Illuminate\Support\Facades\Log;

class ReportGoogleSlideController extends Controller
{
    private $client;
    private $slidesService;
    private $driveService;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google/service-account-credentials.json'));

        // 必要最小限のスコープのみを追加
        $this->client->addScope(Google_Service_Slides::PRESENTATIONS);
        $this->client->addScope('https://www.googleapis.com/auth/drive.file'); // ファイルのみの権限

        $this->slidesService = new Google_Service_Slides($this->client);
        $this->driveService = new Google_Service_Drive($this->client);
    }

    public function export($id)
    {
        try {
            $report = AnalysisReport::findOrFail($id);
            $data = $report->data_json;

            // 新しいプレゼンテーションを作成
            $presentation = new \Google_Service_Slides_Presentation([
                'title' => $report->title ?? '分析レポート'
            ]);
            $presentation = $this->slidesService->presentations->create($presentation);
            $presentationId = $presentation->presentationId;

            // 最新のスライドIDs
            $slideIds = [];

            // タイトルスライド
            $createTitleSlide = $this->createBasicSlide($presentationId, 'TITLE', $report->title ?? '分析レポート', '作成日: ' . $report->created_at->format('Y年m月d日'));
            $slideIds[] = $createTitleSlide['slideId'];

            // 基本メトリクスのスライド
            if (isset($data['metrics'])) {
                $metrics = $data['metrics'];

                $metricsText = "総ユーザー数: " . number_format($metrics['totalUsers']) . "\n";
                $metricsText .= "新規ユーザー数: " . number_format($metrics['newUsers']) . "\n";
                $metricsText .= "セッション数: " . number_format($metrics['sessions']) . "\n";
                $metricsText .= "ページビュー: " . number_format($metrics['pageviews']) . "\n";
                $metricsText .= "直帰率: " . number_format($metrics['bounceRate'], 2) . "%\n";
                $metricsText .= "平均セッション時間: " . round($metrics['avgSessionDuration'], 1) . "秒\n";
                $metricsText .= "エンゲージメント率: " . number_format($metrics['engagementRate'], 2) . "%";

                $createMetricsSlide = $this->createBasicSlide($presentationId, 'TITLE_AND_BODY', '基本メトリクス', $metricsText);
                $slideIds[] = $createMetricsSlide['slideId'];
            }

            // デバイスデータのスライド
            if (isset($data['dimensions']['devices'])) {
                $deviceData = $data['dimensions']['devices'];

                $deviceText = "デバイスカテゴリ別ユーザー数:\n\n";
                foreach ($deviceData as $device) {
                    $deviceText .= "{$device['deviceCategory']}: " . number_format($device['users']) . " ユーザー\n";
                }

                $createDeviceSlide = $this->createBasicSlide($presentationId, 'TITLE_AND_BODY', 'デバイスデータ', $deviceText);
                $slideIds[] = $createDeviceSlide['slideId'];
            }

            // トラフィックソースのスライド
            if (isset($data['dimensions']['sources'])) {
                $sourceData = $data['dimensions']['sources'];

                $sourceText = "トラフィックソース別ユーザー数:\n\n";
                foreach ($sourceData as $source) {
                    $sourceText .= "{$source['source']} / {$source['medium']}: " . number_format($source['users']) . " ユーザー\n";
                }

                $createSourceSlide = $this->createBasicSlide($presentationId, 'TITLE_AND_BODY', 'トラフィックソース', $sourceText);
                $slideIds[] = $createSourceSlide['slideId'];
            }

            // ページデータのスライド
            if (isset($data['dimensions']['pages'])) {
                $pageData = $data['dimensions']['pages'];

                $pageText = "ページ別ページビュー数（上位10ページ）:\n\n";
                foreach (array_slice($pageData, 0, 10) as $page) {
                    $pagePath = $page['pagePath'];
                    if (mb_strlen($pagePath) > 50) {
                        $pagePath = mb_substr($pagePath, 0, 47) . '...';
                    }
                    $pageText .= "{$pagePath}: " . number_format($page['pageviews']) . " PV\n";
                }

                $createPageSlide = $this->createBasicSlide($presentationId, 'TITLE_AND_BODY', 'ページ別データ', $pageText);
                $slideIds[] = $createPageSlide['slideId'];
            }

            // 最初のデフォルトスライドを削除
            if (!empty($presentation->getSlides())) {
                $defaultSlideId = $presentation->getSlides()[0]->getObjectId();
                $deleteRequest = new \Google_Service_Slides_BatchUpdatePresentationRequest([
                    'requests' => [
                        [
                            'deleteObject' => [
                                'objectId' => $defaultSlideId
                            ]
                        ]
                    ]
                ]);
                $this->slidesService->presentations->batchUpdate($presentationId, $deleteRequest);
            }

            // 共有設定の追加 - 誰でも閲覧可能に設定
            try {
                $permission = new Google_Service_Drive_Permission([
                    'type' => 'anyone',        // 'anyone'は誰でもアクセス可能という意味
                    'role' => 'reader',        // 閲覧のみの権限
                    'allowFileDiscovery' => false  // 検索での発見を防止
                ]);

                // 権限の追加（通知メールを送信しない）
                $this->driveService->permissions->create(
                    $presentationId,
                    $permission,
                    ['sendNotificationEmail' => false]
                );

                Log::info('Slide permissions set to public: ' . $presentationId);
            } catch (\Exception $e) {
                // 権限設定に失敗しても処理を継続
                Log::error('Failed to set permissions: ' . $e->getMessage());
            }

            // 共有可能なプレゼンテーションURL（発表モード）を生成
            $slidesUrl = "https://docs.google.com/presentation/d/{$presentationId}/present";

            // HTMLページを返して自動的に別タブでGoogle Slidesを開く
            $html = '<!DOCTYPE html>
                    <html>
                    <head>
                        <title>レポートを開いています...</title>
                        <script type="text/javascript">
                            window.onload = function() {
                                // 新しいタブでGoogle Slidesを開く
                                window.open("' . $slidesUrl . '", "_blank");

                                // 1秒後に元のページに戻る
                                setTimeout(function() {
                                    window.location.href = "' . url()->previous() . '";
                                }, 1000);
                            }
                        </script>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                text-align: center;
                                margin-top: 100px;
                            }
                            .container {
                                max-width: 600px;
                                margin: 0 auto;
                                padding: 20px;
                                border: 1px solid #ddd;
                                border-radius: 5px;
                                background-color: #f9f9f9;
                            }
                            h1 {
                                color: #333;
                            }
                            .spinner {
                                width: 40px;
                                height: 40px;
                                margin: 20px auto;
                                border: 4px solid #f3f3f3;
                                border-top: 4px solid #3498db;
                                border-radius: 50%;
                                animation: spin 1s linear infinite;
                            }
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                            .link {
                                margin-top: 20px;
                            }
                            .link a {
                                color: #3498db;
                                text-decoration: none;
                            }
                            .link a:hover {
                                text-decoration: underline;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>レポートを開いています</h1>
                            <p>Google Slidesでレポートが別タブで開かれます。<br>ブラウザがポップアップをブロックしている場合は、下のリンクをクリックしてください。</p>
                            <div class="spinner"></div>
                            <div class="link">
                                <a href="' . $slidesUrl . '" target="_blank">レポートを開く</a>
                            </div>
                        </div>
                    </body>
                    </html>';

            return response($html);
        } catch (\Exception $e) {
            Log::error('Google Slides Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * シンプルなスライドを作成する
     *
     * @param string $presentationId プレゼンテーションID
     * @param string $layout レイアウトタイプ ('TITLE', 'TITLE_AND_BODY', 'BLANK', etc)
     * @param string $title タイトル
     * @param string $body 本文テキスト（オプション）
     * @return array 作成されたスライドの情報
     */
    private function createBasicSlide($presentationId, $layout, $title, $body = '')
    {
        // スライドIDを生成
        $slideId = 'slide_' . uniqid();
        $titleId = 'title_' . uniqid();
        $bodyId = 'body_' . uniqid();

        $requests = [
            // スライドの作成
            [
                'createSlide' => [
                    'objectId' => $slideId,
                    'slideLayoutReference' => [
                        'predefinedLayout' => $layout
                    ]
                ]
            ]
        ];

        // スライドの更新をリクエスト
        $batchUpdateRequest = new \Google_Service_Slides_BatchUpdatePresentationRequest([
            'requests' => $requests
        ]);
        $response = $this->slidesService->presentations->batchUpdate($presentationId, $batchUpdateRequest);

        // 作成されたスライドの情報を取得
        $createdSlide = $response->getReplies()[0]->getCreateSlide()->getObjectId();

        // 取得したスライド情報から、既存のテキストプレースホルダを探す
        $presentation = $this->slidesService->presentations->get($presentationId);
        $slide = null;
        foreach ($presentation->getSlides() as $s) {
            if ($s->getObjectId() === $createdSlide) {
                $slide = $s;
                break;
            }
        }

        if ($slide) {
            $textRequests = [];
            $titlePlaceholderId = null;
            $bodyPlaceholderId = null;

            // プレースホルダを探す
            if ($slide->getPageElements()) {
                foreach ($slide->getPageElements() as $element) {
                    if ($element->getShape() && $element->getShape()->getPlaceholder()) {
                        $placeholder = $element->getShape()->getPlaceholder();
                        if ($placeholder->getType() === 'TITLE' || $placeholder->getType() === 'CENTERED_TITLE') {
                            $titlePlaceholderId = $element->getObjectId();
                        } elseif ($placeholder->getType() === 'BODY' || $placeholder->getType() === 'SUBTITLE') {
                            $bodyPlaceholderId = $element->getObjectId();
                        }
                    }
                }
            }

            // タイトルと本文を更新
            if ($titlePlaceholderId) {
                $textRequests[] = [
                    'insertText' => [
                        'objectId' => $titlePlaceholderId,
                        'text' => $title
                    ]
                ];
            } else {
                // タイトルプレースホルダが見つからない場合、新規にテキストボックスを作成
                $textRequests[] = [
                    'createShape' => [
                        'objectId' => $titleId,
                        'shapeType' => 'TEXT_BOX',
                        'elementProperties' => [
                            'pageObjectId' => $createdSlide,
                            'size' => [
                                'width' => [
                                    'magnitude' => 600,
                                    'unit' => 'PT'
                                ],
                                'height' => [
                                    'magnitude' => 80,
                                    'unit' => 'PT'
                                ]
                            ],
                            'transform' => [
                                'scaleX' => 1,
                                'scaleY' => 1,
                                'translateX' => 50,
                                'translateY' => 50,
                                'unit' => 'PT'
                            ]
                        ]
                    ]
                ];

                $textRequests[] = [
                    'insertText' => [
                        'objectId' => $titleId,
                        'text' => $title
                    ]
                ];
            }

            if (!empty($body)) {
                if ($bodyPlaceholderId) {
                    $textRequests[] = [
                        'insertText' => [
                            'objectId' => $bodyPlaceholderId,
                            'text' => $body
                        ]
                    ];
                } else {
                    // 本文プレースホルダが見つからない場合、新規にテキストボックスを作成
                    $textRequests[] = [
                        'createShape' => [
                            'objectId' => $bodyId,
                            'shapeType' => 'TEXT_BOX',
                            'elementProperties' => [
                                'pageObjectId' => $createdSlide,
                                'size' => [
                                    'width' => [
                                        'magnitude' => 600,
                                        'unit' => 'PT'
                                    ],
                                    'height' => [
                                        'magnitude' => 320,
                                        'unit' => 'PT'
                                    ]
                                ],
                                'transform' => [
                                    'scaleX' => 1,
                                    'scaleY' => 1,
                                    'translateX' => 50,
                                    'translateY' => 150,
                                    'unit' => 'PT'
                                ]
                            ]
                        ]
                    ];

                    $textRequests[] = [
                        'insertText' => [
                            'objectId' => $bodyId,
                            'text' => $body
                        ]
                    ];
                }
            }

            // テキストの更新をリクエスト
            if (!empty($textRequests)) {
                $textUpdateRequest = new \Google_Service_Slides_BatchUpdatePresentationRequest([
                    'requests' => $textRequests
                ]);
                $this->slidesService->presentations->batchUpdate($presentationId, $textUpdateRequest);
            }
        }

        return ['slideId' => $createdSlide];
    }
}
