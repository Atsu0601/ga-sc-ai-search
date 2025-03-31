<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $website->name }} - データスナップショット
            </h2>
            <div>
                <form action="{{ route('snapshots.create', $website->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                        新規スナップショット作成
                    </button>
                </form>
                <a href="{{ route('websites.show', $website->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    サイト詳細に戻る
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Analytics スナップショット -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Google Analytics スナップショット</h3>

                    @if ($analyticsSnapshots->isEmpty())
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-center text-gray-500">Analytics のスナップショットがありません。「新規スナップショット作成」ボタンをクリックして、データを取得してください。</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            日付
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ユーザー数
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            セッション数
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ページビュー数
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            操作
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($analyticsSnapshots as $snapshot)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $snapshot->snapshot_date->format('Y/m/d') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($snapshot->data_json['metrics']['users']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($snapshot->data_json['metrics']['sessions']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($snapshot->data_json['metrics']['pageviews']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('snapshots.show', ['website' => $website->id, 'id' => $snapshot->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    詳細
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Search Console スナップショット -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Search Console スナップショット</h3>

                    @if ($searchConsoleSnapshots->isEmpty())
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-center text-gray-500">Search Console のスナップショットがありません。「新規スナップショット作成」ボタンをクリックして、データを取得してください。</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            日付
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
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            操作
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($searchConsoleSnapshots as $snapshot)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $snapshot->snapshot_date->format('Y/m/d') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($snapshot->data_json['metrics']['clicks']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ number_format($snapshot->data_json['metrics']['impressions']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $snapshot->data_json['metrics']['ctr'] }}%
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $snapshot->data_json['metrics']['position'] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('snapshots.show', ['website' => $website->id, 'id' => $snapshot->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    詳細
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- データ可視化セクション -->
            @if (!$analyticsSnapshots->isEmpty() || !$searchConsoleSnapshots->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">データトレンド</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Analytics トレンドグラフ -->
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Google Analytics トレンド</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <canvas id="analyticsChart" height="250"></canvas>
                                </div>
                            </div>

                            <!-- Search Console トレンドグラフ -->
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Search Console トレンド</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <canvas id="searchConsoleChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Chart.jsを使用したグラフ描画 -->
    @if (!$analyticsSnapshots->isEmpty() || !$searchConsoleSnapshots->isEmpty())
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Analytics データの準備
            @if (!$analyticsSnapshots->isEmpty())
            const analyticsData = {
                labels: [
                    @foreach ($analyticsSnapshots as $snapshot)
                        "{{ $snapshot->snapshot_date->format('m/d') }}",
                    @endforeach
                ],
                datasets: [
                    {
                        label: 'ユーザー数',
                        data: [
                            @foreach ($analyticsSnapshots as $snapshot)
                                {{ $snapshot->data_json['metrics']['users'] }},
                            @endforeach
                        ],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'ページビュー',
                        data: [
                            @foreach ($analyticsSnapshots as $snapshot)
                                {{ $snapshot->data_json['metrics']['pageviews'] }},
                            @endforeach
                        ],
                        borderColor: 'rgb(54, 162, 235)',
                        tension: 0.1,
                        yAxisID: 'y'
                    }
                ]
            };

            const analyticsCtx = document.getElementById('analyticsChart').getContext('2d');
            new Chart(analyticsCtx, {
                type: 'line',
                data: analyticsData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '数値'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '日付'
                            }
                        }
                    }
                }
            });
            @endif

            // Search Console データの準備
            @if (!$searchConsoleSnapshots->isEmpty())
            const searchConsoleData = {
                labels: [
                    @foreach ($searchConsoleSnapshots as $snapshot)
                        "{{ $snapshot->snapshot_date->format('m/d') }}",
                    @endforeach
                ],
                datasets: [
                    {
                        label: 'クリック数',
                        data: [
                            @foreach ($searchConsoleSnapshots as $snapshot)
                                {{ $snapshot->data_json['metrics']['clicks'] }},
                            @endforeach
                        ],
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'インプレッション',
                        data: [
                            @foreach ($searchConsoleSnapshots as $snapshot)
                                {{ $snapshot->data_json['metrics']['impressions'] }},
                            @endforeach
                        ],
                        borderColor: 'rgb(153, 102, 255)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            };

            const searchConsoleCtx = document.getElementById('searchConsoleChart').getContext('2d');
            new Chart(searchConsoleCtx, {
                type: 'line',
                data: searchConsoleData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'クリック数'
                            },
                            position: 'left'
                        },
                        y1: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'インプレッション'
                            },
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '日付'
                            }
                        }
                    }
                }
            });
            @endif
        });
    </script>
    @endif
</x-app-layout>
