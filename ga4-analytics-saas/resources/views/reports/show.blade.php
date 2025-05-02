<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $report->website->name }} - {{ $report->report_type_japanese }}レポート
            </h2>
            <div>
                @if ($report->status === 'completed')
                    <a href="{{ route('reports.download', $report->id) }}"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2"
                        target="_blank">
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
                {{-- 基本メトリクス --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">基本メトリクス</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">総ユーザー数</div>
                            <div class="text-2xl font-bold">{{ $data['metrics']['totalUsers'] }}</div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">新規ユーザー数</div>
                            <div class="text-2xl font-bold">{{ $data['metrics']['newUsers'] }}</div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">セッション数</div>
                            <div class="text-2xl font-bold">{{ $data['metrics']['sessions'] }}</div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">ページビュー</div>
                            <div class="text-2xl font-bold">{{ $data['metrics']['pageviews'] }}</div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">直帰率</div>
                            <div class="text-2xl font-bold">{{ number_format($data['metrics']['bounceRate'], 2) }}%
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">平均セッション時間</div>
                            <div class="text-2xl font-bold">{{ round($data['metrics']['avgSessionDuration'], 1) }}秒
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">エンゲージメント率</div>
                            <div class="text-2xl font-bold">{{ number_format($data['metrics']['engagementRate'], 2) }}%
                            </div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">コンバージョン数</div>
                            <div class="text-2xl font-bold">{{ $data['metrics']['keyEvents'] ?? 0 }}</div>
                        </div>
                        <div class="bg-blue-50 rounded p-4 flex flex-col items-center">
                            <div class="text-gray-500 text-xs">コンバージョン率</div>
                            @php
                                $conversionRate = 0;
                                if (!empty($data['metrics']['sessions']) && !empty($data['metrics']['keyEvents'])) {
                                    $conversionRate =
                                        $data['metrics']['sessions'] > 0
                                            ? ($data['metrics']['keyEvents'] / $data['metrics']['sessions']) * 100
                                            : 0;
                                }
                            @endphp
                            <div class="text-2xl font-bold">{{ number_format($conversionRate, 2) }}%</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <canvas id="metricsBarChart"></canvas>
                        </div>
                        <div>
                            <canvas id="metricsPieChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- トレンドデータ --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">日別トレンド</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <canvas id="trendLineChart"></canvas>
                        </div>
                        <div>
                            <canvas id="trendBarChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- デバイスデータ --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">デバイスカテゴリ</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                        <div>
                            <canvas id="devicePieChart"></canvas>
                        </div>
                        <div>
                            <canvas id="deviceBarChart"></canvas>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th>カテゴリ</th>
                                    <th>OS</th>
                                    <th>ブラウザ</th>
                                    <th>ユーザー数</th>
                                    <th>セッション</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['dimensions']['devices'] as $row)
                                    <tr>
                                        <td>{{ $row['deviceCategory'] }}</td>
                                        <td>{{ $row['operatingSystem'] }}</td>
                                        <td>{{ $row['browser'] }}</td>
                                        <td>{{ $row['users'] }}</td>
                                        <td>{{ $row['sessions'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- トラフィックソース --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">トラフィックソース</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                        <div>
                            <canvas id="sourceBarChart"></canvas>
                        </div>
                        <div>
                            <canvas id="sourcePieChart"></canvas>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th>ソース</th>
                                    <th>メディア</th>
                                    <th>ユーザー数</th>
                                    <th>セッション</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['dimensions']['sources'] as $row)
                                    <tr>
                                        <td>{{ $row['source'] }}</td>
                                        <td>{{ $row['medium'] }}</td>
                                        <td>{{ $row['users'] }}</td>
                                        <td>{{ $row['sessions'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ページデータ --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">ページ別データ</h3>
                    <div class="mb-4">
                        <canvas id="pageBarChart"></canvas>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th>ページ</th>
                                    <th>タイトル</th>
                                    <th>ページビュー</th>
                                    <th>ユーザー数</th>
                                    <th>セッション</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['dimensions']['pages'] as $row)
                                    <tr>
                                        <td>{{ $row['pagePath'] }}</td>
                                        <td>{{ $row['pageTitle'] }}</td>
                                        <td>{{ $row['pageviews'] }}</td>
                                        <td>{{ $row['users'] }}</td>
                                        <td>{{ $row['sessions'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 地域データ --}}
                <div class="bg-white rounded shadow p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4">地域データ</h3>
                    <div class="mb-4">
                        <canvas id="locationRadarChart"></canvas>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th>国</th>
                                    <th>地域</th>
                                    <th>市区町村</th>
                                    <th>ユーザー数</th>
                                    <th>セッション</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['dimensions']['locations'] as $row)
                                    <tr>
                                        <td>{{ $row['country'] }}</td>
                                        <td>{{ $row['region'] }}</td>
                                        <td>{{ $row['city'] }}</td>
                                        <td>{{ $row['users'] }}</td>
                                        <td>{{ $row['sessions'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- コンバージョン詳細分析 --}}
                @if (isset($data['dimensions']['keyevents']) && count($data['dimensions']['keyevents']) > 0)
                    <div class="bg-white rounded shadow p-6 mb-8">
                        <h3 class="text-lg font-bold mb-4">コンバージョン詳細分析</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                            <div>
                                <h4 class="font-semibold mb-2">トラフィックソース別コンバージョン</h4>
                                <canvas id="conversionSourceBarChart"></canvas>
                                <div class="overflow-x-auto mt-2">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>ソース/メディア</th>
                                                <th>コンバージョン数</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $conversionBySource = [];
                                                foreach ($data['dimensions']['keyevents'] as $event) {
                                                    $source =
                                                        ($event['source'] ?? '-') . ' / ' . ($event['medium'] ?? '-');
                                                    if (!isset($conversionBySource[$source])) {
                                                        $conversionBySource[$source] = 0;
                                                    }
                                                    $conversionBySource[$source] += $event['keyEvents'];
                                                }
                                            @endphp
                                            @foreach ($conversionBySource as $key => $count)
                                                <tr>
                                                    <td>{{ $key }}</td>
                                                    <td>{{ $count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-2">ページ別コンバージョン</h4>
                                <canvas id="conversionPageBarChart"></canvas>
                                <div class="overflow-x-auto mt-2">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>ページ</th>
                                                <th>コンバージョン数</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $conversionByPage = [];
                                                foreach ($data['dimensions']['keyevents'] as $event) {
                                                    $page = $event['pagePath'] ?? '-';
                                                    if (!isset($conversionByPage[$page])) {
                                                        $conversionByPage[$page] = 0;
                                                    }
                                                    $conversionByPage[$page] += $event['keyEvents'];
                                                }
                                            @endphp
                                            @foreach ($conversionByPage as $page => $count)
                                                <tr>
                                                    <td>{{ $page }}</td>
                                                    <td>{{ $count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-4">
                            <div>
                                <h4 class="font-semibold mb-2">デバイス別コンバージョン</h4>
                                <canvas id="conversionDevicePieChart"></canvas>
                                <div class="overflow-x-auto mt-2">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>デバイス</th>
                                                <th>コンバージョン数</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $conversionByDevice = [];
                                                foreach ($data['dimensions']['keyevents'] as $event) {
                                                    $device = $event['deviceCategory'] ?? '-';
                                                    if (!isset($conversionByDevice[$device])) {
                                                        $conversionByDevice[$device] = 0;
                                                    }
                                                    $conversionByDevice[$device] += $event['keyEvents'];
                                                }
                                            @endphp
                                            @foreach ($conversionByDevice as $device => $count)
                                                <tr>
                                                    <td>{{ $device }}</td>
                                                    <td>{{ $count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold mb-2">地域別コンバージョン</h4>
                                <canvas id="conversionLocationBarChart"></canvas>
                                <div class="overflow-x-auto mt-2">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th>国</th>
                                                <th>コンバージョン数</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $conversionByCity = [];
                                                foreach ($data['dimensions']['keyevents'] as $event) {
                                                    $city = $event['city'] ?? '-';
                                                    if (!isset($conversionByCity[$city])) {
                                                        $conversionByCity[$city] = 0;
                                                    }
                                                    $conversionByCity[$city] += $event['keyEvents'];
                                                }
                                            @endphp
                                            @foreach ($conversionByCity as $city => $count)
                                                <tr>
                                                    <td>{{ $city }}</td>
                                                    <td>{{ $count }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">曜日別コンバージョン</h4>
                            <canvas id="conversionDayBarChart"></canvas>
                            <div class="overflow-x-auto mt-2">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th>曜日</th>
                                            <th>コンバージョン数</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $conversionByDay = [];
                                            $en2ja = [
                                                'Sunday' => '日曜日',
                                                'Monday' => '月曜日',
                                                'Tuesday' => '火曜日',
                                                'Wednesday' => '水曜日',
                                                'Thursday' => '木曜日',
                                                'Friday' => '金曜日',
                                                'Saturday' => '土曜日',
                                            ];
                                            foreach ($data['dimensions']['keyevents'] as $event) {
                                                $enDay = $event['dayOfWeekName'] ?? '-';
                                                $jaDay = $en2ja[$enDay] ?? $enDay;
                                                if (!isset($conversionByDay[$jaDay])) {
                                                    $conversionByDay[$jaDay] = 0;
                                                }
                                                $conversionByDay[$jaDay] += $event['keyEvents'];
                                            }
                                            $weekOrder = [
                                                '日曜日',
                                                '月曜日',
                                                '火曜日',
                                                '水曜日',
                                                '木曜日',
                                                '金曜日',
                                                '土曜日',
                                            ];
                                        @endphp
                                        @foreach ($weekOrder as $day)
                                            <tr>
                                                <td>{{ $day }}</td>
                                                <td>{{ $conversionByDay[$day] ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 基本メトリクス
        const metrics = @json($data['metrics']);
        new Chart(document.getElementById('metricsBarChart'), {
            type: 'bar',
            data: {
                labels: ['ユーザー数', '新規ユーザー数', 'セッション数', 'ページビュー'],
                datasets: [{
                    label: '基本メトリクス',
                    data: [metrics.totalUsers, metrics.newUsers, metrics.sessions, metrics.pageviews],
                    backgroundColor: ['#3b82f6', '#f59e42', '#10b981', '#6366f1']
                }]
            }
        });
        new Chart(document.getElementById('metricsPieChart'), {
            type: 'pie',
            data: {
                labels: ['直帰率', 'エンゲージメント率'],
                datasets: [{
                    data: [metrics.bounceRate, metrics.engagementRate],
                    backgroundColor: ['#f59e42', '#10b981']
                }]
            }
        });

        // トレンドデータ
        const trend = @json($data['dimensions']['trends']);
        const trendLabels = trend.map(d => d.date);
        const trendUsers = trend.map(d => d.users);
        const trendSessions = trend.map(d => d.sessions);
        new Chart(document.getElementById('trendLineChart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                        label: 'ユーザー数',
                        data: trendUsers,
                        borderColor: '#3b82f6',
                        fill: false
                    },
                    {
                        label: 'セッション数',
                        data: trendSessions,
                        borderColor: '#f59e42',
                        fill: false
                    }
                ]
            }
        });
        new Chart(document.getElementById('trendBarChart'), {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [{
                        label: 'ユーザー数',
                        data: trendUsers,
                        backgroundColor: '#3b82f6'
                    },
                    {
                        label: 'セッション数',
                        data: trendSessions,
                        backgroundColor: '#f59e42'
                    }
                ]
            }
        });

        // デバイスデータ
        const deviceData = @json($data['dimensions']['devices']);
        const deviceLabels = [...new Set(deviceData.map(d => d.deviceCategory))];
        const deviceCounts = deviceLabels.map(label => deviceData.filter(d => d.deviceCategory === label).reduce((sum, d) =>
            sum + d.users, 0));
        new Chart(document.getElementById('devicePieChart'), {
            type: 'doughnut',
            data: {
                labels: deviceLabels,
                datasets: [{
                    data: deviceCounts,
                    backgroundColor: ['#3b82f6', '#f59e42', '#10b981']
                }]
            }
        });
        new Chart(document.getElementById('deviceBarChart'), {
            type: 'bar',
            data: {
                labels: deviceLabels,
                datasets: [{
                    label: 'ユーザー数',
                    data: deviceCounts,
                    backgroundColor: '#3b82f6'
                }]
            }
        });

        // トラフィックソース
        const sourceData = @json($data['dimensions']['sources']);
        const sourceLabels = sourceData.map(d => d.source + ' / ' + d.medium);
        const sourceUsers = sourceData.map(d => d.users);
        new Chart(document.getElementById('sourceBarChart'), {
            type: 'bar',
            data: {
                labels: sourceLabels,
                datasets: [{
                    label: 'ユーザー数',
                    data: sourceUsers,
                    backgroundColor: '#3b82f6'
                }]
            }
        });
        new Chart(document.getElementById('sourcePieChart'), {
            type: 'pie',
            data: {
                labels: sourceLabels,
                datasets: [{
                    data: sourceUsers,
                    backgroundColor: ['#3b82f6', '#f59e42', '#10b981', '#6366f1', '#eab308', '#ef4444']
                }]
            }
        });

        // ページデータ
        const pageData = @json($data['dimensions']['pages']);
        const pageLabels = pageData.map(d => d.pagePath);
        const pageViews = pageData.map(d => d.pageviews);
        new Chart(document.getElementById('pageBarChart'), {
            type: 'bar',
            data: {
                labels: pageLabels,
                datasets: [{
                    label: 'ページビュー',
                    data: pageViews,
                    backgroundColor: '#6366f1'
                }]
            }
        });

        // 地域データ
        const locationData = @json($data['dimensions']['locations']);
        const cityLabels = [...new Set(locationData.map(d => d.city))];
        const cityUsers = cityLabels.map(label => locationData.filter(d => d.city === label).reduce((sum, d) =>
            sum + d.users, 0));
        new Chart(document.getElementById('locationRadarChart'), {
            type: 'radar',
            data: {
                labels: cityLabels,
                datasets: [{
                    label: 'ユーザー数',
                    data: cityUsers,
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    borderColor: '#3b82f6'
                }]
            }
        });

        // コンバージョン詳細分析
        const conversionData = @json($data['dimensions']['keyevents'] ?? []);
        if (conversionData.length > 0) {
            // トラフィックソース別
            const sourceMap = {};
            conversionData.forEach(d => {
                const key = (d.source ?? '-') + ' / ' + (d.medium ?? '-');
                sourceMap[key] = (sourceMap[key] ?? 0) + (d.keyEvents ?? 0);
            });
            new Chart(document.getElementById('conversionSourceBarChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(sourceMap),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: Object.values(sourceMap),
                        backgroundColor: '#f59e42'
                    }]
                }
            });

            // ページ別
            const pageMap = {};
            conversionData.forEach(d => {
                const key = d.pagePath ?? '-';
                pageMap[key] = (pageMap[key] ?? 0) + (d.keyEvents ?? 0);
            });
            new Chart(document.getElementById('conversionPageBarChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(pageMap),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: Object.values(pageMap),
                        backgroundColor: '#10b981'
                    }]
                }
            });

            // デバイス別
            const deviceMap = {};
            conversionData.forEach(d => {
                const key = d.deviceCategory ?? '-';
                deviceMap[key] = (deviceMap[key] ?? 0) + (d.keyEvents ?? 0);
            });
            new Chart(document.getElementById('conversionDevicePieChart'), {
                type: 'pie',
                data: {
                    labels: Object.keys(deviceMap),
                    datasets: [{
                        data: Object.values(deviceMap),
                        backgroundColor: ['#3b82f6', '#f59e42', '#10b981']
                    }]
                }
            });

            // 地域別
            const cityMap = {};
            conversionData.forEach(d => {
                const key = d.city ?? '-';
                cityMap[key] = (cityMap[key] ?? 0) + (d.keyEvents ?? 0);
            });
            new Chart(document.getElementById('conversionLocationBarChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(cityMap),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: Object.values(cityMap),
                        backgroundColor: '#6366f1'
                    }]
                }
            });

            // 曜日別
            const en2ja = {
                'Sunday': '日曜日',
                'Monday': '月曜日',
                'Tuesday': '火曜日',
                'Wednesday': '水曜日',
                'Thursday': '木曜日',
                'Friday': '金曜日',
                'Saturday': '土曜日'
            };
            const weekOrder = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];
            const dayMap = {};
            conversionData.forEach(d => {
                const enDay = d.dayOfWeekName ?? '-';
                const jaDay = en2ja[enDay] ?? enDay;
                dayMap[jaDay] = (dayMap[jaDay] ?? 0) + (d.keyEvents ?? 0);
            });
            // すべての曜日を0埋めで用意
            const sortedDays = weekOrder;
            const sortedCounts = weekOrder.map(day => dayMap[day] ?? 0);

            new Chart(document.getElementById('conversionDayBarChart'), {
                type: 'bar',
                data: {
                    labels: sortedDays,
                    datasets: [{
                        label: 'コンバージョン数',
                        data: sortedCounts,
                        backgroundColor: '#eab308'
                    }]
                }
            });
        }
    </script>
</x-app-layout>
