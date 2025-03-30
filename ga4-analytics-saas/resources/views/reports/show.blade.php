<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $report->website->name }} - {{ $report->report_type_japanese }}レポート
            </h2>
            <div>
                @if ($report->status === 'completed')
                    <a href="{{ route('reports.download', $report->id) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mr-2">
                        PDFダウンロード
                    </a>
                @endif
                <a href="{{ route('reports.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    一覧に戻る
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
                            <p class="mt-1">{{ $report->date_range_start->format('Y年m月d日') }} 〜 {{ $report->date_range_end->format('Y年m月d日') }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">ステータス</h3>
                            <p class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $report->status === 'completed' ? 'bg-green-100 text-green-800' :
                                   ($report->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $report->status_japanese }}
                                </span>
                            </p>
                        </div>
                    </div>

                    @if ($report->status === 'processing')
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">レポート生成中...</h3>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $report->getProgressPercentage() }}%"></div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">レポートの生成には数分かかることがあります。このページを更新すると進捗状況が更新されます。</p>
                        </div>
                    @elseif ($report->status === 'failed')
                        <div class="mt-6 bg-red-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-red-800 mb-2">レポート生成に失敗しました</h3>
                            <p class="text-red-600">レポートの生成中にエラーが発生しました。もう一度お試しいただくか、管理者にお問い合わせください。</p>
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
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-white mr-3">
                                                <i class="fas {{ $recommendation->category_icon_class }}"></i>
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">分析データ</h3>

                        @if ($components->isEmpty())
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-center text-gray-500">このレポートにはデータコンポーネントがありません。</p>
                            </div>
                        @else
                            <div class="space-y-8">
                                @foreach ($components as $component)
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h4 class="font-medium text-lg mb-4">{{ $component->title }}</h4>

                                        @if ($component->component_type === 'chart')
                                            <div class="h-80">
                                                <!-- Chart.jsなどでチャートを表示 -->
                                                <canvas id="chart-{{ $component->id }}"></canvas>
                                            </div>
                                        @elseif ($component->component_type === 'table')
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-100">
                                                        <tr>
                                                            @foreach ($component->data_json['headers'] as $header)
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                    {{ $header }}
                                                                </th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($component->data_json['rows'] as $row)
                                                            <tr class="bg-white">
                                                                @foreach ($row as $cell)
                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
                                                <!-- ヒートマップ表示 -->
                                                <div id="heatmap-{{ $component->id }}"></div>
                                            </div>
                                        @else
                                            <div class="prose">
                                                <p>{{ $component->data_json['content'] ?? '' }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
