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

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Alert Messages -->
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Webサイト</h3>
                            <p class="font-semibold">{{ $report->website->name }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">期間</h3>
                            <p class="font-semibold">{{ $report->date_range_start->format('Y年m月d日') }} 〜
                                {{ $report->date_range_end->format('Y年m月d日') }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">ステータス</h3>
                            <p>
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
                        <div class="mt-4">
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
                        <div class="mt-4 bg-red-50 p-4 rounded-lg">
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- 基本メトリクス -->
                    <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">基本メトリクス</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-9 gap-2 mb-4">
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">総ユーザー数</div>
                                <div class="text-xl font-bold">{{ $data['metrics']['totalUsers'] }}</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">新規ユーザー数</div>
                                <div class="text-xl font-bold">{{ $data['metrics']['newUsers'] }}</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">セッション数</div>
                                <div class="text-xl font-bold">{{ $data['metrics']['sessions'] }}</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">ページビュー</div>
                                <div class="text-xl font-bold">{{ $data['metrics']['pageviews'] }}</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">直帰率</div>
                                <div class="text-xl font-bold">{{ number_format($data['metrics']['bounceRate'], 2) }}%
                                </div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">平均セッション時間</div>
                                <div class="text-xl font-bold">{{ round($data['metrics']['avgSessionDuration'], 1) }}秒
                                </div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">エンゲージメント率</div>
                                <div class="text-xl font-bold">
                                    {{ number_format($data['metrics']['engagementRate'], 2) }}%</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
                                <div class="text-gray-500 text-xs">コンバージョン数</div>
                                <div class="text-xl font-bold">{{ $data['metrics']['keyEvents'] ?? 0 }}</div>
                            </div>
                            <div class="bg-blue-50 rounded p-3 flex flex-col items-center">
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
                                <div class="text-xl font-bold">{{ number_format($conversionRate, 2) }}%</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <canvas id="metricsBarChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="metricsPieChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- トレンドデータ -->
                    <div class="bg-white rounded shadow p-4">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">日別トレンド</h3>
                        <div style="height: 300px">
                            <canvas id="trendLineChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded shadow p-4">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">トレンド比較</h3>
                        <div style="height: 300px">
                            <canvas id="trendBarChart"></canvas>
                        </div>
                    </div>

                    <!-- デバイスデータ -->
                    <div class="bg-white rounded shadow p-4">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">デバイスカテゴリ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <canvas id="devicePieChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="deviceBarChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- トラフィックソース -->
                    <div class="bg-white rounded shadow p-4">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">トラフィックソース</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <canvas id="sourceBarChart" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="sourcePieChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- デバイスデータの詳細テーブル -->
                    <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">デバイス詳細</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4 flex justify-center">
                                <div class="w-full">
                                    <canvas id="deviceDetailPieChart" height="200"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left">カテゴリ</th>
                                            <th class="px-4 py-2 text-left">OS</th>
                                            <th class="px-4 py-2 text-left">ブラウザ</th>
                                            <th class="px-4 py-2 text-right">ユーザー数</th>
                                            <th class="px-4 py-2 text-right">セッション</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($data['dimensions']['devices'] as $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $row['deviceCategory'] }}</td>
                                                <td class="px-4 py-2">{{ $row['operatingSystem'] }}</td>
                                                <td class="px-4 py-2">{{ $row['browser'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['users'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['sessions'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- トラフィックソース詳細テーブル -->
                    <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">トラフィックソース詳細</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4 flex justify-center">
                                <div class="w-full">
                                    <canvas id="sourceDetailPieChart" height="200"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left">ソース</th>
                                            <th class="px-4 py-2 text-left">メディア</th>
                                            <th class="px-4 py-2 text-right">ユーザー数</th>
                                            <th class="px-4 py-2 text-right">セッション</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($data['dimensions']['sources'] as $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $row['source'] }}</td>
                                                <td class="px-4 py-2">{{ $row['medium'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['users'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['sessions'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ページデータ -->
                    <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">ページ別データ</h3>
                        <div class="mb-4">
                            <canvas id="pageBarChart" height="200"></canvas>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">ページ</th>
                                        <th class="px-4 py-2 text-left">タイトル</th>
                                        <th class="px-4 py-2 text-right">ページビュー</th>
                                        <th class="px-4 py-2 text-right">ユーザー数</th>
                                        <th class="px-4 py-2 text-right">セッション</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($data['dimensions']['pages'] as $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 max-w-xs truncate">{{ $row['pagePath'] }}</td>
                                            <td class="px-4 py-2 max-w-xs truncate">{{ $row['pageTitle'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['pageviews'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['users'] }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['sessions'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 地域データ -->
                    <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                        <h3 class="text-lg font-bold mb-3 border-b pb-2">地域データ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="mb-4 flex justify-center">
                                <div class="w-full">
                                    <canvas id="locationPieChart" height="200"></canvas>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left">国</th>
                                            <th class="px-4 py-2 text-left">地域</th>
                                            <th class="px-4 py-2 text-left">市区町村</th>
                                            <th class="px-4 py-2 text-right">ユーザー数</th>
                                            <th class="px-4 py-2 text-right">セッション</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($data['dimensions']['locations'] as $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $row['country'] }}</td>
                                                <td class="px-4 py-2">{{ $row['region'] }}</td>
                                                <td class="px-4 py-2">{{ $row['city'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['users'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['sessions'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- コンバージョン基本データ -->
                    @if (isset($data['dimensions']['keyevents']) && count($data['dimensions']['keyevents']) > 0)
                        <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                            <h3 class="text-lg font-bold mb-3 border-b pb-2">コンバージョン基本分析</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <canvas id="conversionBarChart" height="250"></canvas>
                                </div>
                                <div>
                                    <canvas id="conversionLineChart" height="250"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left">日付</th>
                                            <th class="px-4 py-2 text-left">イベント名</th>
                                            <th class="px-4 py-2 text-right">コンバージョン数</th>
                                            <th class="px-4 py-2 text-right">イベント値</th>
                                            <th class="px-4 py-2 text-right">ユーザー数</th>
                                            <th class="px-4 py-2 text-right">セッション数</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($data['dimensions']['keyevents'] as $row)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">{{ $row['date'] }}</td>
                                                <td class="px-4 py-2">{{ $row['eventName'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['keyEvents'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['eventValue'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['users'] }}</td>
                                                <td class="px-4 py-2 text-right">{{ $row['sessions'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- コンバージョン詳細分析 -->
                        <div class="bg-white rounded shadow p-4 col-span-1 lg:col-span-2">
                            <h3 class="text-lg font-bold mb-3 border-b pb-2">コンバージョン詳細分析</h3>

                            <!-- トラフィックソース・ページ別 -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 p-4 rounded">
                                    <h4 class="font-semibold mb-3 text-gray-700">トラフィックソース別コンバージョン</h4>
                                    <div class="h-64">
                                        <canvas id="conversionSourceBarChart"></canvas>
                                    </div>
                                    <div class="overflow-x-auto mt-3">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">ソース/メディア</th>
                                                    <th class="px-3 py-2 text-right">コンバージョン数</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php
                                                    $conversionBySource = [];
                                                    foreach ($data['dimensions']['keyevents'] as $event) {
                                                        $source =
                                                            ($event['source'] ?? '-') .
                                                            ' / ' .
                                                            ($event['medium'] ?? '-');
                                                        if (!isset($conversionBySource[$source])) {
                                                            $conversionBySource[$source] = 0;
                                                        }
                                                        $conversionBySource[$source] += $event['keyEvents'];
                                                    }
                                                    // 降順ソート
                                                    arsort($conversionBySource);
                                                @endphp
                                                @foreach ($conversionBySource as $key => $count)
                                                    <tr class="hover:bg-white">
                                                        <td class="px-3 py-2 truncate max-w-xs">{{ $key }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right">{{ $count }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded">
                                    <h4 class="font-semibold mb-3 text-gray-700">ページ別コンバージョン</h4>
                                    <div class="h-64">
                                        <canvas id="conversionPageBarChart"></canvas>
                                    </div>
                                    <div class="overflow-x-auto mt-3">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">ページ</th>
                                                    <th class="px-3 py-2 text-right">コンバージョン数</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php
                                                    $conversionByPage = [];
                                                    foreach ($data['dimensions']['keyevents'] as $event) {
                                                        $page = $event['pagePath'] ?? '-';
                                                        if (!isset($conversionByPage[$page])) {
                                                            $conversionByPage[$page] = 0;
                                                        }
                                                        $conversionByPage[$page] += $event['keyEvents'];
                                                    }
                                                    // 降順ソート
                                                    arsort($conversionByPage);
                                                @endphp
                                                @foreach ($conversionByPage as $page => $count)
                                                    <tr class="hover:bg-white">
                                                        <td class="px-3 py-2 truncate max-w-xs">{{ $page }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right">{{ $count }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- デバイス・地域別 -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 p-4 rounded">
                                    <h4 class="font-semibold mb-3 text-gray-700">デバイス別コンバージョン</h4>
                                    <div class="h-64">
                                        <canvas id="conversionDevicePieChart"></canvas>
                                    </div>
                                    <div class="overflow-x-auto mt-3">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">デバイス</th>
                                                    <th class="px-3 py-2 text-right">コンバージョン数</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php
                                                    $conversionByDevice = [];
                                                    foreach ($data['dimensions']['keyevents'] as $event) {
                                                        $device = $event['deviceCategory'] ?? '-';
                                                        if (!isset($conversionByDevice[$device])) {
                                                            $conversionByDevice[$device] = 0;
                                                        }
                                                        $conversionByDevice[$device] += $event['keyEvents'];
                                                    }
                                                    // 降順ソート
                                                    arsort($conversionByDevice);
                                                @endphp
                                                @foreach ($conversionByDevice as $device => $count)
                                                    <tr class="hover:bg-white">
                                                        <td class="px-3 py-2">{{ $device }}</td>
                                                        <td class="px-3 py-2 text-right">{{ $count }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded">
                                    <h4 class="font-semibold mb-3 text-gray-700">地域別コンバージョン</h4>
                                    <div class="h-64">
                                        <canvas id="conversionLocationBarChart"></canvas>
                                    </div>
                                    <div class="overflow-x-auto mt-3">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">国/地域</th>
                                                    <th class="px-3 py-2 text-right">コンバージョン数</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php
                                                    $conversionByCity = [];
                                                    foreach ($data['dimensions']['keyevents'] as $event) {
                                                        $city = $event['city'] ?? '-';
                                                        if (!isset($conversionByCity[$city])) {
                                                            $conversionByCity[$city] = 0;
                                                        }
                                                        $conversionByCity[$city] += $event['keyEvents'];
                                                    }
                                                    // 降順ソート
                                                    arsort($conversionByCity);
                                                @endphp
                                                @foreach ($conversionByCity as $city => $count)
                                                    <tr class="hover:bg-white">
                                                        <td class="px-3 py-2">{{ $city }}</td>
                                                        <td class="px-3 py-2 text-right">{{ $count }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- 曜日別コンバージョン -->
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold mb-3 text-gray-700">曜日別コンバージョン</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="h-64">
                                        <canvas id="conversionDayBarChart"></canvas>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">曜日</th>
                                                    <th class="px-3 py-2 text-right">コンバージョン数</th>
                                                    <th class="px-3 py-2 text-right">割合</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @php
                                                    $conversionByDay = [];
                                                    $totalConversions = 0;
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
                                                        $totalConversions += $event['keyEvents'];
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
                                                    <tr class="hover:bg-white">
                                                        <td class="px-3 py-2">{{ $day }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            {{ $conversionByDay[$day] ?? 0 }}</td>
                                                        <td class="px-3 py-2 text-right">
                                                            @if ($totalConversions > 0)
                                                                {{ number_format((($conversionByDay[$day] ?? 0) / $totalConversions) * 100, 1) }}%
                                                            @else
                                                                0%
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex items-center justify-end mt-4">
                <a href="{{ route('reports.export', $report->id) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    PowerPointで出力
                </a>
            </div>
        </div>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart.js共通オプション
        const commonChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        };

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
            },
            options: commonChartOptions
        });

        new Chart(document.getElementById('metricsPieChart'), {
            type: 'pie',
            data: {
                labels: ['直帰率', 'エンゲージメント率'],
                datasets: [{
                    data: [metrics.bounceRate, metrics.engagementRate],
                    backgroundColor: ['#f59e42', '#10b981']
                }]
            },
            options: commonChartOptions
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
                        tension: 0.2,
                        fill: false
                    },
                    {
                        label: 'セッション数',
                        data: trendSessions,
                        borderColor: '#f59e42',
                        tension: 0.2,
                        fill: false
                    }
                ]
            },
            options: {
                ...commonChartOptions,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 15
                        }
                    }
                }
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
            },
            options: {
                ...commonChartOptions,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            autoSkip: true,
                            maxTicksLimit: 15
                        }
                    }
                }
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
            },
            options: commonChartOptions
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
            },
            options: commonChartOptions
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
            },
            options: {
                ...commonChartOptions,
                indexAxis: 'y',
                scales: {
                    y: {
                        ticks: {
                            autoSkip: false
                        }
                    }
                }
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
            },
            options: commonChartOptions
        });

        // ページデータ
        const pageData = @json($data['dimensions']['pages']);
        // 上位10ページを表示
        const topPages = pageData.sort((a, b) => b.pageviews - a.pageviews).slice(0, 10);
        const pageLabels = topPages.map(d => d.pagePath);
        const pageViews = topPages.map(d => d.pageviews);

        new Chart(document.getElementById('pageBarChart'), {
            type: 'bar',
            data: {
                labels: pageLabels,
                datasets: [{
                    label: 'ページビュー',
                    data: pageViews,
                    backgroundColor: '#6366f1'
                }]
            },
            options: {
                ...commonChartOptions,
                indexAxis: 'y',
                scales: {
                    y: {
                        ticks: {
                            autoSkip: false,
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                return label.length > 25 ? label.substring(0, 22) + '...' : label;
                            }
                        }
                    }
                }
            }
        });

        // 地域データ（円グラフ）
        const locationData = @json($data['dimensions']['locations']);
        const regionLabels = [...new Set(locationData.map(d => d.region))];
        const regionUsers = regionLabels.map(label =>
            locationData.filter(d => d.region === label).reduce((sum, d) => sum + d.users, 0)
        );
        new Chart(document.getElementById('locationPieChart'), {
            type: 'pie',
            data: {
                labels: regionLabels,
                datasets: [{
                    data: regionUsers,
                    backgroundColor: [
                        '#3b82f6', '#f59e42', '#10b981', '#6366f1', '#eab308', '#ef4444',
                        '#a3e635', '#f472b6', '#f87171', '#facc15', '#818cf8', '#fbbf24'
                    ]
                }]
            },
            options: {
                ...commonChartOptions,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // コンバージョンデータ
        const conversionData = @json($data['dimensions']['keyevents'] ?? []);
        if (conversionData.length > 0) {
            const conversionLabels = conversionData.map(d => d.date + ' ' + d.eventName);
            const conversionCounts = conversionData.map(d => d.keyEvents);
            const conversionValues = conversionData.map(d => d.eventValue);

            // 基本コンバージョンチャート
            // 棒グラフ（コンバージョン数）
            new Chart(document.getElementById('conversionBarChart'), {
                type: 'bar',
                data: {
                    labels: conversionLabels,
                    datasets: [{
                        label: 'コンバージョン数',
                        data: conversionCounts,
                        backgroundColor: '#eab308'
                    }]
                },
                options: {
                    ...commonChartOptions,
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 20 ? label.substring(0, 17) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });

            // 折れ線グラフ（イベント値の推移）
            new Chart(document.getElementById('conversionLineChart'), {
                type: 'line',
                data: {
                    labels: conversionLabels,
                    datasets: [{
                        label: 'イベント値',
                        data: conversionValues,
                        borderColor: '#ef4444',
                        fill: false,
                        tension: 0.2
                    }]
                },
                options: {
                    ...commonChartOptions,
                    scales: {
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 20 ? label.substring(0, 17) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });

            // 詳細コンバージョン分析チャート
            // トラフィックソース別
            const sourceMap = {};
            conversionData.forEach(d => {
                const key = (d.source ?? '-') + ' / ' + (d.medium ?? '-');
                sourceMap[key] = (sourceMap[key] ?? 0) + (d.keyEvents ?? 0);
            });

            // ソート済みの配列を作成
            const sortedSourceData = Object.entries(sourceMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10); // 上位10件のみ表示

            new Chart(document.getElementById('conversionSourceBarChart'), {
                type: 'bar',
                data: {
                    labels: sortedSourceData.map(d => d[0]),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: sortedSourceData.map(d => d[1]),
                        backgroundColor: '#f59e42'
                    }]
                },
                options: {
                    ...commonChartOptions,
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 25 ? label.substring(0, 22) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });

            // ページ別
            const pageMap = {};
            conversionData.forEach(d => {
                const key = d.pagePath ?? '-';
                pageMap[key] = (pageMap[key] ?? 0) + (d.keyEvents ?? 0);
            });

            // ソート済みの配列を作成
            const sortedPageData = Object.entries(pageMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10); // 上位10件のみ表示

            new Chart(document.getElementById('conversionPageBarChart'), {
                type: 'bar',
                data: {
                    labels: sortedPageData.map(d => d[0]),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: sortedPageData.map(d => d[1]),
                        backgroundColor: '#10b981'
                    }]
                },
                options: {
                    ...commonChartOptions,
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 25 ? label.substring(0, 22) + '...' : label;
                                }
                            }
                        }
                    }
                }
            });

            // デバイス別
            const deviceMap = {};
            conversionData.forEach(d => {
                const key = d.deviceCategory ?? '-';
                deviceMap[key] = (deviceMap[key] ?? 0) + (d.keyEvents ?? 0);
            });

            new Chart(document.getElementById('conversionDevicePieChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(deviceMap),
                    datasets: [{
                        data: Object.values(deviceMap),
                        backgroundColor: ['#3b82f6', '#f59e42', '#10b981']
                    }]
                },
                options: {
                    ...commonChartOptions,
                    maintainAspectRatio: false,
                    cutout: '50%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });

            // 地域別
            const cityMap = {};
            conversionData.forEach(d => {
                const key = d.city ?? '-';
                cityMap[key] = (cityMap[key] ?? 0) + (d.keyEvents ?? 0);
            });

            // ソート済みの配列を作成
            const sortedCityData = Object.entries(cityMap)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10); // 上位10件のみ表示

            new Chart(document.getElementById('conversionLocationBarChart'), {
                type: 'bar',
                data: {
                    labels: sortedCityData.map(d => d[0]),
                    datasets: [{
                        label: 'コンバージョン数',
                        data: sortedCityData.map(d => d[1]),
                        backgroundColor: '#6366f1'
                    }]
                },
                options: {
                    ...commonChartOptions,
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    return label.length > 25 ? label.substring(0, 22) + '...' : label;
                                }
                            }
                        }
                    }
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

            // 初期化（0埋め）
            weekOrder.forEach(day => {
                dayMap[day] = 0;
            });

            // データ集計
            conversionData.forEach(d => {
                const enDay = d.dayOfWeekName ?? '-';
                const jaDay = en2ja[enDay] ?? enDay;
                if (weekOrder.includes(jaDay)) {
                    dayMap[jaDay] = (dayMap[jaDay] ?? 0) + (d.keyEvents ?? 0);
                }
            });

            // 曜日順にデータを並べ替え
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
                },
            });
        }

        // デバイス詳細円グラフ
        const deviceDetailData = @json($data['dimensions']['devices']);
        const deviceDetailLabels = [...new Set(deviceDetailData.map(d => d.deviceCategory))];
        const deviceDetailCounts = deviceDetailLabels.map(label =>
            deviceDetailData.filter(d => d.deviceCategory === label).reduce((sum, d) => sum + d.users, 0)
        );
        new Chart(document.getElementById('deviceDetailPieChart'), {
            type: 'doughnut',
            data: {
                labels: deviceDetailLabels,
                datasets: [{
                    data: deviceDetailCounts,
                    backgroundColor: ['#3b82f6', '#f59e42', '#10b981', '#6366f1', '#eab308', '#ef4444']
                }]
            },
            options: {
                ...commonChartOptions,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // トラフィックソース詳細円グラフ
        const sourceDetailData = @json($data['dimensions']['sources']);
        const sourceDetailLabels = sourceDetailData.map(d => d.source + ' / ' + d.medium);
        const sourceDetailCounts = sourceDetailData.map(d => d.users);
        new Chart(document.getElementById('sourceDetailPieChart'), {
            type: 'pie',
            data: {
                labels: sourceDetailLabels,
                datasets: [{
                    data: sourceDetailCounts,
                    backgroundColor: ['#3b82f6', '#f59e42', '#10b981', '#6366f1', '#eab308', '#ef4444']
                }]
            },
            options: {
                ...commonChartOptions,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</x-app-layout>
