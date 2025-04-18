<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->website->name }} - 分析レポート</title>
    <style>
        @media screen {
            body {
                max-width: 210mm;
                margin: 20px auto;
                padding: 20px;
                background: #f0f0f0;
            }

            .page {
                background: white;
                padding: 20mm;
                margin-bottom: 20px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .page {
                padding: 20mm;
                page-break-after: always;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: "Helvetica Neue", Arial, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        h3 {
            font-size: 16px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
    </style>
    <script>
        window.onload = function() {
            // ページ読み込み時に自動で印刷ダイアログを表示
            window.print();
        }
    </script>
</head>

<body>
    <!-- 印刷時に非表示になる操作ボタン -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">印刷する</button>
    </div>

    <div class="page">
        <h1>{{ $report->website->name }} - 分析レポート</h1>
        <p>期間：{{ $report->date_range_start->format('Y年m月d日') }} 〜 {{ $report->date_range_end->format('Y年m月d日') }}</p>

        @if ($components && $components->isNotEmpty())
            @foreach ($components as $component)
                <div class="component">
                    <h2>{{ $component->title }}</h2>

                    @if ($component->component_type === 'metrics')
                        <table>
                            <tr>
                                <th style="width: 40%">指標</th>
                                <th>値</th>
                            </tr>
                            @foreach ($component->data_json as $key => $value)
                                <tr>
                                    <td>
                                        {{ match ($key) {
                                            'users' => 'ユーザー数',
                                            'sessions' => 'セッション数',
                                            'pageviews' => 'ページビュー数',
                                            'bounce_rate' => '直帰率',
                                            'avg_session_duration' => '平均セッション時間',
                                            default => $key,
                                        } }}
                                    </td>
                                    <td>
                                        @if ($key === 'bounce_rate')
                                            {{ number_format($value, 1) }}%
                                        @elseif($key === 'avg_session_duration')
                                            {{ gmdate('i:s', $value) }}
                                        @else
                                            {{ number_format($value) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @elseif($component->component_type === 'devices')
                        <table>
                            <tr>
                                <th>デバイス</th>
                                <th>ユーザー数</th>
                                <th>割合</th>
                            </tr>
                            @php
                                $totalUsers = collect($component->data_json['devices'])->sum('users');
                            @endphp
                            @foreach ($component->data_json['devices'] as $device)
                                <tr>
                                    <td>{{ match ($device['device']) {
                                        'desktop' => 'デスクトップ',
                                        'mobile' => 'モバイル',
                                        'tablet' => 'タブレット',
                                        default => $device['device'],
                                    } }}
                                    </td>
                                    <td>{{ number_format($device['users']) }}</td>
                                    <td>{{ number_format(($device['users'] / $totalUsers) * 100, 1) }}%</td>
                                </tr>
                            @endforeach
                        </table>
                    @elseif($component->component_type === 'sources')
                        <table>
                            <tr>
                                <th>ソース</th>
                                <th>ユーザー数</th>
                                <th>割合</th>
                            </tr>
                            @php
                                $totalUsers = collect($component->data_json['sources'])->sum('users');
                            @endphp
                            @foreach ($component->data_json['sources'] as $source)
                                <tr>
                                    <td>{{ $source['source'] }}</td>
                                    <td>{{ number_format($source['users']) }}</td>
                                    <td>{{ number_format(($source['users'] / $totalUsers) * 100, 1) }}%</td>
                                </tr>
                            @endforeach
                        </table>
                    @elseif($component->component_type === 'pages')
                        <table>
                            <tr>
                                <th>ページパス</th>
                                <th>ページビュー数</th>
                                <th>平均滞在時間</th>
                            </tr>
                            @foreach ($component->data_json['pages'] as $page)
                                <tr>
                                    <td>{{ $page['page'] }}</td>
                                    <td>{{ number_format($page['pageviews']) }}</td>
                                    <td>
                                        @if (isset($page['avgTimeOnPage']))
                                            {{ gmdate('i:s', $page['avgTimeOnPage']) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>
                <div class="page-break"></div>
            @endforeach
        @else
            <p>レポートコンポーネントが見つかりません。</p>
        @endif

        @if ($recommendations && $recommendations->isNotEmpty())
            <h2>AI改善提案</h2>
            @foreach ($recommendations as $recommendation)
                <div class="recommendation">
                    <h3>{{ $recommendation->category_japanese }}</h3>
                    <p>{{ $recommendation->content }}</p>
                </div>
            @endforeach
        @endif
    </div>
</body>

</html>
