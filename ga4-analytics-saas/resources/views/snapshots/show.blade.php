<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $website->name }} - {{ $snapshot->snapshot_type === 'analytics' ? 'Analytics' : 'Search Console' }}スナップショット ({{ $snapshot->snapshot_date->format('Y/m/d') }})
            </h2>
            <div>
                <a href="{{ route('snapshots.index', $website->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    一覧に戻る
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- スナップショット情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">スナップショット情報</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">取得日</p>
                            <p class="font-medium">{{ $snapshot->snapshot_date->format('Y年m月d日') }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">タイプ</p>
                            <p class="font-medium">{{ $snapshot->snapshot_type === 'analytics' ? 'Google Analytics' : 'Google Search Console' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">作成日時</p>
                            <p class="font-medium">{{ $snapshot->created_at->format('Y年m月d日 H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- メトリクス情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">主要指標</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if ($snapshot->snapshot_type === 'analytics')
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-800 mb-1">ユーザー数</h4>
                                <p class="text-2xl font-bold">{{ number_format($snapshot->data_json['metrics']['users']) }}</p>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-800 mb-1">セッション数</h4>
                                <p class="text-2xl font-bold">{{ number_format($snapshot->data_json['metrics']['sessions']) }}</p>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-800 mb-1">ページビュー数</h4>
                                <p class="text-2xl font-bold">{{ number_format($snapshot->data_json['metrics']['pageviews']) }}</p>
                            </div>

                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-800 mb-1">直帰率</h4>
                                <p class="text-2xl font-bold">{{ $snapshot->data_json['metrics']['bounceRate'] }}%</p>
                            </div>
                        @else
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-medium text-purple-800 mb-1">クリック数</h4>
                                <p class="text-2xl font-bold">{{ number_format($snapshot->data_json['metrics']['clicks']) }}</p>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-medium text-purple-800 mb-1">インプレッション数</h4>
                                <p class="text-2xl font-bold">{{ number_format($snapshot->data_json['metrics']['impressions']) }}</p>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-medium text-purple-800 mb-1">CTR</h4>
                                <p class="text-2xl font-bold">{{ $snapshot->data_json['metrics']['ctr'] }}%</p>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-medium text-purple-800 mb-1">平均掲載順位</h4>
                                <p class="text-2xl font-bold">{{ $snapshot->data_json['metrics']['position'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 詳細データ -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if ($snapshot->snapshot_type === 'analytics')
                        <h3 class="text-lg font-medium text-gray-900 mb-4">デバイス別ユーザー数</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <canvas id="devicesChart" height="250"></canvas>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                デバイス
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ユーザー数
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                割合
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @php
                                            $totalUsers = array_sum(array_column($snapshot->data_json['dimensions']['devices'], 'users'));
                                        @endphp

                                        @foreach ($snapshot->data_json['dimensions']['devices'] as $device)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ ucfirst($device['device']) }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        {{ number_format($device['users']) }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        {{ round(($device['users'] / $totalUsers) * 100, 1) }}%
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 mt-8">人気ページ</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ページURL
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ページビュー数
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($snapshot->data_json['dimensions']['pages'] as $page)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $page['page'] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($page['pageviews']) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <h3 class="text-lg font-medium text-gray-900 mb-4">検索キーワード</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            検索キーワード
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            クリック数
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            インプレッション数
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            CTR
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            平均掲載順位
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($snapshot->data_json['queries'] as $query)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $query['query'] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($query['clicks']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($query['impressions']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $query['ctr'] }}%
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $query['position'] }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 mt-8">人気ページ</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ページURL
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            クリック数
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($snapshot->data_json['pages'] as $page)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $page['page'] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($page['clicks']) }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- JSONデータ表示（デバッグ用、本番では非表示にしても良い） -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">生データ（JSON）</h3>

                    <div class="bg-gray-100 p-4 rounded-lg overflow-x-auto">
                        <pre class="text-xs">{{ json_encode($snapshot->data_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.jsを使用したグラフ描画 -->
    @if ($snapshot->snapshot_type === 'analytics')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // デバイス別チャート
            const devicesData = {
                labels: [
                    @foreach ($snapshot->data_json['dimensions']['devices'] as $device)
                        "{{ ucfirst($device['device']) }}",
                    @endforeach
                ],
                datasets: [{
                    label: 'ユーザー数',
                    data: [
                        @foreach ($snapshot->data_json['dimensions']['devices'] as $device)
                            {{ $device['users'] }},
                        @endforeach
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 205, 86, 0.7)',
                    ],
                    borderColor: [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 205, 86)',
                    ],
                    borderWidth: 1
                }]
            };

            const devicesCtx = document.getElementById('devicesChart').getContext('2d');
            new Chart(devicesCtx, {
                type: 'pie',
                data: devicesData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'デバイス別ユーザー数'
                        }
                    }
                }
            });
        });
    </script>
    @endif
</x-app-layout>
