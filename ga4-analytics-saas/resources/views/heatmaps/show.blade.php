<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $types[$heatmap->type] ?? 'ヒートマップ詳細' }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('websites.heatmaps.edit', [$website, $heatmap]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('編集') }}
                </a>
                <form action="{{ route('websites.heatmaps.destroy', [$website, $heatmap]) }}" method="POST"
                    onsubmit="return confirm('本当に削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('削除') }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">基本情報</h3>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500">ページURL:</span>
                                    <a href="{{ $heatmap->page_url }}" target="_blank"
                                        class="block mt-1 text-sm text-indigo-600 hover:text-indigo-500 break-all">
                                        {{ $heatmap->page_url }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500">種類:</span>
                                    <span class="block mt-1 text-sm text-gray-900">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            {{ $types[$heatmap->type] ?? $heatmap->type }}
                                        </span>
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500">期間:</span>
                                    <span class="block mt-1 text-sm text-gray-900">
                                        {{ $heatmap->date_range_start->format('Y/m/d') }} 〜
                                        {{ $heatmap->date_range_end->format('Y/m/d') }}
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <span class="text-sm font-medium text-gray-500">作成日:</span>
                                    <span
                                        class="block mt-1 text-sm text-gray-900">{{ $heatmap->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">データサマリー</h3>
                            <div class="bg-gray-50 p-4 rounded-md">
                                @if ($heatmap->type === 'click')
                                    @php
                                        $data = $heatmap->getData();
                                        $totalClicks = $data['totalClicks'] ?? count($data['clicks'] ?? []);
                                    @endphp
                                    <div class="mb-4">
                                        <span class="text-sm font-medium text-gray-500">総クリック数:</span>
                                        <span class="block mt-1 text-2xl font-bold text-indigo-600">
                                            {{ number_format($totalClicks) }}
                                        </span>
                                    </div>

                                    <div class="mb-4">
                                        <span class="text-sm font-medium text-gray-500">ユニーク位置数:</span>
                                        <span class="block mt-1 text-sm text-gray-900">
                                            {{ count($formattedData) }}
                                        </span>
                                    </div>
                                @elseif($heatmap->type === 'scroll')
                                    @php
                                        $data = $heatmap->getData();
                                        $totalUsers = $data['totalUsers'] ?? 0;
                                    @endphp
                                    <div class="mb-4">
                                        <span class="text-sm font-medium text-gray-500">総ユーザー数:</span>
                                        <span class="block mt-1 text-2xl font-bold text-indigo-600">
                                            {{ number_format($totalUsers) }}
                                        </span>
                                    </div>

                                    @if (!empty($formattedData) && count($formattedData) > 0)
                                        <div class="mb-4">
                                            <span class="text-sm font-medium text-gray-500">50%ユーザー到達深度:</span>
                                            <span class="block mt-1 text-sm text-gray-900">
                                                @php
                                                    $halfUsers = $totalUsers / 2;
                                                    $reachedDepth = 0;

                                                    foreach ($formattedData as $item) {
                                                        if ($item['count'] >= $halfUsers) {
                                                            $reachedDepth = $item['depth'];
                                                        }
                                                    }
                                                @endphp
                                                約 {{ number_format($reachedDepth) }}px
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900">ヒートマップ可視化</h3>
                    </div>

                    @if ($heatmap->type === 'click')
                        <div class="controls-panel mb-4 bg-gray-50 p-4 rounded-md">
                            <div
                                class="flex flex-col md:flex-row items-start md:items-center space-y-2 md:space-y-0 md:space-x-4">
                                <div class="w-full md:w-1/3">
                                    <label for="opacity"
                                        class="block text-sm font-medium text-gray-700 mb-1">透明度</label>
                                    <input type="range" id="opacity" class="w-full" min="0" max="1"
                                        step="0.1" value="0.7">
                                </div>
                                <div class="w-full md:w-1/3">
                                    <label for="radius"
                                        class="block text-sm font-medium text-gray-700 mb-1">半径</label>
                                    <input type="range" id="radius" class="w-full" min="10" max="50"
                                        value="30">
                                </div>
                                <div class="w-full md:w-1/3">
                                    <label for="colorPalette"
                                        class="block text-sm font-medium text-gray-700 mb-1">カラーパレット</label>
                                    <select id="colorPalette"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="default">デフォルト (赤-黄)</option>
                                        <option value="blues">ブルー</option>
                                        <option value="greens">グリーン</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="heatmap-container mb-4 relative border border-gray-200 rounded-md overflow-hidden">
                            <div class="relative bg-gray-100 flex justify-center items-center min-h-[400px]">
                                <div id="clickHeatmap" class="w-full h-full"></div>

                                <!-- スクリーンショット未取得時のプレースホルダー -->
                                <div
                                    class="absolute inset-0 flex items-center justify-center text-center p-6 bg-gray-50">
                                    <div>
                                        <p class="text-gray-500 mb-4">スクリーンショットがありません。</p>
                                        <p class="text-sm text-gray-400">ページのURLに基づいてヒートマップを生成しています。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($heatmap->type === 'scroll')
                        <div class="controls-panel mb-4 bg-gray-50 p-4 rounded-md">
                            <div
                                class="flex flex-col md:flex-row items-start md:items-center space-y-2 md:space-y-0 md:space-x-4">
                                <div class="w-full md:w-1/2">
                                    <label for="colorPaletteScroll"
                                        class="block text-sm font-medium text-gray-700 mb-1">カラーパレット</label>
                                    <select id="colorPaletteScroll"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="blues">ブルー</option>
                                        <option value="greens">グリーン</option>
                                        <option value="purples">パープル</option>
                                    </select>
                                </div>
                                <div class="w-full md:w-1/2">
                                    <label for="showPercentage"
                                        class="block text-sm font-medium text-gray-700 mb-1">表示方法</label>
                                    <select id="showPercentage"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="percentage">パーセンテージ (%)</option>
                                        <option value="absolute">実数値</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            class="scroll-heatmap-container mb-4 bg-white border border-gray-200 rounded-md overflow-hidden p-4">
                            <h4 class="text-base font-medium text-gray-700 mb-2">スクロール深度分布</h4>
                            <div id="scrollHeatmap" class="h-64"></div>
                        </div>
                    @endif

                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">元データ</h3>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        @if ($heatmap->type === 'click')
                                            <tr>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    X座標</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Y座標</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    クリック数</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    強度</th>
                                            </tr>
                                        @elseif($heatmap->type === 'scroll')
                                            <tr>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    スクロール深度 (px)</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    ユーザー数</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    割合 (%)</th>
                                            </tr>
                                        @endif
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @if ($heatmap->type === 'click')
                                            @foreach (array_slice($formattedData, 0, 20) as $item)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $item['x'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $item['y'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $item['value'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ number_format($item['intensity'] * 100, 1) }}%</td>
                                                </tr>
                                            @endforeach
                                            @if (count($formattedData) > 20)
                                                <tr>
                                                    <td colspan="4"
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">
                                                        ...他 {{ count($formattedData) - 20 }} 件のデータがあります
                                                    </td>
                                                </tr>
                                            @endif
                                        @elseif($heatmap->type === 'scroll')
                                            @php
                                                $data = $heatmap->getData();
                                                $totalUsers = $data['totalUsers'] ?? 1;
                                            @endphp
                                            @foreach ($formattedData as $item)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $item['depth'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $item['count'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ number_format($item['percentage'], 1) }}%</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/heatmap.js@2.0.5/build/heatmap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if ($heatmap->type === 'click')
                    // クリックヒートマップの初期化
                    const heatmapContainer = document.getElementById('clickHeatmap');
                    const heatmapInstance = h337.create({
                        container: heatmapContainer,
                        radius: 30,
                        maxOpacity: 0.7,
                        minOpacity: 0,
                        blur: 0.75
                    });

                    // データのフォーマット
                    const heatmapData = {
                        max: 10,
                        data: {!! json_encode($formattedData) !!}.map(point => ({
                            x: point.x,
                            y: point.y,
                            value: point.value
                        }))
                    };

                    // ヒートマップにデータをセット
                    heatmapInstance.setData(heatmapData);

                    // コントロールイベント
                    document.getElementById('opacity').addEventListener('input', function(e) {
                        heatmapInstance.configure({
                            maxOpacity: parseFloat(e.target.value)
                        });
                    });

                    document.getElementById('radius').addEventListener('input', function(e) {
                        heatmapInstance.configure({
                            radius: parseInt(e.target.value)
                        });
                    });

                    document.getElementById('colorPalette').addEventListener('change', function(e) {
                        let gradient;

                        switch (e.target.value) {
                            case 'blues':
                                gradient = {
                                    0.4: 'blue',
                                    0.6: 'cyan',
                                    0.8: 'lime',
                                    1.0: 'yellow'
                                };
                                break;
                            case 'greens':
                                gradient = {
                                    0.4: 'lime',
                                    0.6: 'green',
                                    0.8: 'darkgreen',
                                    1.0: 'yellow'
                                };
                                break;
                            default:
                                gradient = {
                                    0.4: 'blue',
                                    0.6: 'cyan',
                                    0.7: 'lime',
                                    0.8: 'yellow',
                                    1.0: 'red'
                                };
                        }

                        heatmapInstance.configure({
                            gradient: gradient
                        });
                    });
                @elseif ($heatmap->type === 'scroll')
                    // スクロールヒートマップの初期化
                    const ctx = document.getElementById('scrollHeatmap').getContext('2d');
                    const scrollData = {!! json_encode($formattedData) !!};

                    // データ整形
                    const depths = scrollData.map(item => item.depth + 'px');
                    const counts = scrollData.map(item => item.count);
                    const percentages = scrollData.map(item => item.percentage);

                    let chartInstance;

                    function createChart(displayType) {
                        const dataValues = displayType === 'percentage' ? percentages : counts;
                        const yLabel = displayType === 'percentage' ? 'ユーザー割合 (%)' : 'ユーザー数';

                        return new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: depths,
                                datasets: [{
                                    label: 'スクロール深度分布',
                                    data: dataValues,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: yLabel
                                        }
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'スクロール深度'
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // 初期チャート作成
                    chartInstance = createChart('percentage');

                    // 表示切替イベント
                    document.getElementById('showPercentage').addEventListener('change', function(e) {
                        if (chartInstance) {
                            chartInstance.destroy();
                        }
                        chartInstance = createChart(e.target.value);
                    });

                    // カラーパレット変更イベント
                    document.getElementById('colorPaletteScroll').addEventListener('change', function(e) {
                        let bgColor, borderColor;

                        switch (e.target.value) {
                            case 'greens':
                                bgColor = 'rgba(75, 192, 75, 0.5)';
                                borderColor = 'rgba(75, 192, 75, 1)';
                                break;
                            case 'purples':
                                bgColor = 'rgba(153, 102, 255, 0.5)';
                                borderColor = 'rgba(153, 102, 255, 1)';
                                break;
                            default: // blues
                                bgColor = 'rgba(54, 162, 235, 0.5)';
                                borderColor = 'rgba(54, 162, 235, 1)';
                        }

                        chartInstance.data.datasets[0].backgroundColor = bgColor;
                        chartInstance.data.datasets[0].borderColor = borderColor;
                        chartInstance.update();
                    });
                @endif
            });
        </script>
    @endpush
</x-app-layout>
