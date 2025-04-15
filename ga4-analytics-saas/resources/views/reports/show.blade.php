<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $report->website->name }} - {{ $report->report_type_japanese }}レポート
            </h2>
            <div>
                @if ($report->status === 'completed')
                    <a href="{{ route('reports.download', $report->id) }}"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2">
                        PDFダウンロード
                    </a>
                @endif
                <a href="{{ route('reports.index') }}"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    一覧に戻る
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- レポート情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Webサイト</h3>
                            <p class="mt-1">{{ $report->website->name }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">期間</h3>
                            <p class="mt-1">{{ $report->date_range_start->format('Y年m月d日') }} 〜
                                {{ $report->date_range_end->format('Y年m月d日') }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">ステータス</h3>
                            <p class="mt-1">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $report->status === 'completed'
                                    ? 'bg-green-100 text-green-800'
                                    : ($report->status === 'processing'
                                        ? 'bg-yellow-100 text-yellow-800'
                                        : 'bg-red-100 text-red-800') }}">
                                    {{ $report->status_japanese }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if ($report->status === 'processing')
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">レポート生成中...</h3>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full"
                                    style="width: {{ $report->getProgressPercentage() }}%"></div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">レポートの生成には数分かかることがあります。このページを更新すると進捗状況が更新されます。</p>

                            <script>
                                // 自動更新（30秒ごと）
                                setTimeout(function() {
                                    window.location.reload();
                                }, 30000);
                            </script>
                        </div>
                    @elseif ($report->status === 'failed')
                        <div class="mt-6 bg-red-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-red-800 mb-2">レポート生成に失敗しました</h3>
                            <p class="text-red-600">レポートの生成中にエラーが発生しました。もう一度お試しいただくか、管理者にお問い合わせください。</p>

                            <form method="POST" action="{{ route('reports.destroy', $report->id) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                    onclick="return confirm('このレポートを削除しますか？')">
                                    レポートを削除
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            @if ($report->status === 'completed')
                <!-- AIレコメンデーション -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">AIによる改善提案</h3>

                        @if ($recommendations->isEmpty())
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-center text-gray-500">このレポートには改善提案がありません。</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($recommendations as $recommendation)
                                    <div class="p-4 rounded-lg {{ $recommendation->severity_css_class }}">
                                        <div class="flex items-start">
                                            <div
                                                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-white mr-3">
                                                <svg class="h-5 w-5 {{ $recommendation->severity === 'critical' ? 'text-red-600' : ($recommendation->severity === 'warning' ? 'text-yellow-600' : 'text-blue-600') }}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    @if ($recommendation->category === 'seo')
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    @elseif ($recommendation->category === 'performance')
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    @elseif ($recommendation->category === 'content')
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                    @endif
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-medium">{{ $recommendation->category_japanese }}</h4>
                                                <p class="mt-1">{{ $recommendation->content }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- レポートコンポーネント -->
                @if (!$components->isEmpty())
                    @foreach ($components as $component)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6 bg-white border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $component->title }}</h3>

                                @if ($component->component_type === 'text')
                                    <div class="prose max-w-none">
                                        {!! $component->data_json['content'] !!}
                                    </div>
                                @elseif ($component->component_type === 'chart')
                                    <div class="h-80">
                                        <canvas id="chart-{{ $component->id }}"></canvas>
                                    </div>
                                @elseif ($component->component_type === 'table')
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    @foreach ($component->data_json['headers'] as $header)
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            {{ $header }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($component->data_json['rows'] as $row)
                                                    <tr>
                                                        @foreach ($row as $cell)
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ $cell }}
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif ($component->component_type === 'heatmap')
                                    <div class="h-80">
                                        <canvas id="heatmap-{{ $component->id }}"></canvas>
                                    </div>
                                @elseif ($component->component_type === 'metrics')
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        @foreach ($component->data_json as $key => $value)
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <dt class="text-sm font-medium text-gray-500">
                                                    {{ match ($key) {
                                                        'users' => 'ユーザー数',
                                                        'sessions' => 'セッション数',
                                                        'pageviews' => 'ページビュー数',
                                                        'bounce_rate' => '直帰率',
                                                        'avg_session_duration' => '平均セッション時間',
                                                        default => $key,
                                                    } }}
                                                </dt>
                                                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                                                    @if ($key === 'bounce_rate')
                                                        {{ number_format($value, 1) }}%
                                                    @elseif ($key === 'avg_session_duration')
                                                        {{ gmdate('i:s', $value) }}
                                                    @else
                                                        {{ number_format($value) }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($component->component_type === 'devices')
                                    <div class="mt-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            デバイス</th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            ユーザー数</th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            割合</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @php
                                                        // 総ユーザー数を計算
                                                        $totalUsers = collect($component->data_json['devices'])->sum(
                                                            'users',
                                                        );
                                                    @endphp

                                                    @foreach ($component->data_json['devices'] as $device)
                                                        <tr>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ match ($device['device']) {
                                                                    'desktop' => 'デスクトップ',
                                                                    'mobile' => 'モバイル',
                                                                    'tablet' => 'タブレット',
                                                                    default => $device['device'],
                                                                } }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ number_format($device['users']) }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ number_format(($device['users'] / $totalUsers) * 100, 1) }}%
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @elseif ($component->component_type === 'sources')
                                    <div class="mt-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            ソース</th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            ユーザー数</th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            割合</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @php
                                                        // 総ユーザー数を計算
                                                        $totalUsers = collect($component->data_json['sources'])->sum(
                                                            'users',
                                                        );
                                                    @endphp

                                                    @foreach ($component->data_json['sources'] as $source)
                                                        <tr>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ match ($source['source']) {
                                                                    'google' => 'Google',
                                                                    '(direct)' => '直接訪問',
                                                                    '(not set)' => '未設定',
                                                                    'search.fenrir-inc.com' => 'Fenrir検索',
                                                                    'biztools.corp.google.com' => 'Google Biztools',
                                                                    default => $source['source'],
                                                                } }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ number_format($source['users']) }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ number_format(($source['users'] / $totalUsers) * 100, 1) }}%
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @elseif ($component->component_type === 'pages')
                                    <div class="mt-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            ページパス
                                                        </th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            ページビュー数
                                                        </th>
                                                        <th
                                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            平均滞在時間
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach ($component->data_json['pages'] as $page)
                                                        <tr>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ $page['page'] }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {{ number_format($page['pageviews']) }}
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                @if (isset($page['avgTimeOnPage']))
                                                                    {{ gmdate('i:s', $page['avgTimeOnPage']) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif
        </div>
    </div>

    @if ($report->status === 'completed' && !$components->isEmpty())
        <!-- Chart.jsとヒートマップスクリプト -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // チャートとヒートマップの描画
                @foreach ($components as $component)
                    @if ($component->component_type === 'chart')
                        // チャート描画
                        const chartData{{ $component->id }} = @json($component->formatted_data);
                        const chartCtx{{ $component->id }} = document.getElementById('chart-{{ $component->id }}')
                            .getContext('2d');
                        new Chart(chartCtx{{ $component->id }}, chartData{{ $component->id }});
                    @elseif ($component->component_type === 'heatmap')
                        // ヒートマップ描画（簡易的な実装）
                        const heatmapData{{ $component->id }} = @json($component->data_json);
                        const heatmapCtx{{ $component->id }} = document.getElementById('heatmap-{{ $component->id }}')
                            .getContext('2d');

                        // ヒートマップ用の色関数
                        const getHeatmapColor = (value) => {
                            const min = 0;
                            const max = 100;
                            const normalized = (value - min) / (max - min);
                            const h = (1 - normalized) * 240; // 青から赤へのグラデーション
                            return `hsl(${h}, 100%, 50%)`;
                        };

                        // キャンバスのサイズ設定
                        const canvas = heatmapCtx{{ $component->id }}.canvas;
                        const cellWidth = canvas.width / 24; // 24時間
                        const cellHeight = canvas.height / 7; // 7日

                        // ヒートマップの描画
                        heatmapCtx{{ $component->id }}.clearRect(0, 0, canvas.width, canvas.height);

                        heatmapData{{ $component->id }}.data.forEach(item => {
                            const x = item.hour * cellWidth;
                            const y = item.day * cellHeight;

                            heatmapCtx{{ $component->id }}.fillStyle = getHeatmapColor(item.value);
                            heatmapCtx{{ $component->id }}.fillRect(x, y, cellWidth, cellHeight);

                            // 境界線
                            heatmapCtx{{ $component->id }}.strokeStyle = 'rgba(255, 255, 255, 0.3)';
                            heatmapCtx{{ $component->id }}.strokeRect(x, y, cellWidth, cellHeight);
                        });

                        // 軸ラベルの描画（簡易的）
                        heatmapCtx{{ $component->id }}.fillStyle = '#000';
                        heatmapCtx{{ $component->id }}.font = '10px Arial';

                        // 日ラベル
                        heatmapData{{ $component->id }}.days.forEach((day, index) => {
                            heatmapCtx{{ $component->id }}.fillText(day, 5, index * cellHeight + cellHeight /
                                2 + 3);
                        });

                        // 時間ラベル（間引いて表示）
                        for (let i = 0; i < 24; i += 3) {
                            heatmapCtx{{ $component->id }}.fillText(`${i}時`, i * cellWidth + cellWidth / 2, canvas
                                .height - 5);
                        }
                    @endif
                @endforeach
            });
        </script>
    @endif
</x-app-layout>
