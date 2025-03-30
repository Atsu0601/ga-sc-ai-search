<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ダッシュボード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- サブスクリプション情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">サブスクリプション</h3>
                            <p class="text-sm text-gray-600">
                                現在のプラン: <span class="font-medium">{{ ucfirst(Auth::user()->plan_name) }}</span>
                            </p>

                            @if (Auth::user()->trial_ends_at && now()->lt(Auth::user()->trial_ends_at))
                                <p class="text-sm text-yellow-600">
                                    無料トライアル期間: {{ (int)now()->diffInDays(Auth::user()->trial_ends_at) }}日残っています（{{ Auth::user()->trial_ends_at->format('Y年m月d日') }}まで）
                                </p>
                            @endif
                        </div>

                        <a href="#" class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded">
                            プランをアップグレード
                        </a>
                    </div>
                </div>
            </div>

            <!-- 登録Webサイト一覧 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">登録Webサイト</h3>

                        <a href="{{ route('websites.create') }}" class="bg-green-500 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded">
                            新規登録
                        </a>
                    </div>

                    @if (Auth::user()->websites->isEmpty())
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-center text-gray-500">Webサイトが登録されていません。「新規登録」ボタンからWebサイトを追加してください。</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach (Auth::user()->websites as $website)
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h4 class="font-medium mb-2">{{ $website->name }}</h4>

                                    <p class="text-sm text-gray-600 mb-2">
                                        <a href="{{ $website->url }}" target="_blank" class="text-blue-600 hover:underline">
                                            {{ $website->url }}
                                        </a>
                                    </p>

                                    <div class="flex items-center justify-between">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $website->status === 'active' ? 'bg-green-100 text-green-800' :
                                              ($website->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $website->status === 'active' ? '有効' :
                                              ($website->status === 'pending' ? '準備中' : '無効') }}
                                        </span>

                                        <a href="{{ route('websites.show', $website->id) }}" class="text-sm text-blue-600 hover:underline">
                                            詳細を見る →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if (Auth::user()->websites->count() >= Auth::user()->website_limit)
                            <div class="mt-4 bg-yellow-50 p-4 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    Webサイトの登録上限（{{ Auth::user()->website_limit }}サイト）に達しています。さらに追加するには、プランをアップグレードしてください。
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- 最近の分析レポート -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">最近の分析レポート</h3>

                    @php
                        $reports = App\Models\AnalysisReport::whereIn('website_id', Auth::user()->websites->pluck('id'))
                                                           ->orderByDesc('created_at')
                                                           ->take(5)
                                                           ->get();
                    @endphp

                    @if ($reports->isEmpty())
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-center text-gray-500">まだ分析レポートがありません。Webサイトの詳細ページから分析を開始してください。</p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Webサイト
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        レポートタイプ
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        期間
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ステータス
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        作成日
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($reports as $report)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $report->website->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ $report->report_type === 'executive' ? '経営者向け' :
                                                  ($report->report_type === 'technical' ? '技術者向け' : 'コンテンツ向け') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ $report->date_range_start->format('Y/m/d') }} 〜 {{ $report->date_range_end->format('Y/m/d') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $report->status === 'completed' ? 'bg-green-100 text-green-800' :
                                                   ($report->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ $report->status === 'completed' ? '完了' :
                                                   ($report->status === 'processing' ? '処理中' : '失敗') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                {{ $report->created_at->format('Y/m/d H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if ($report->status === 'completed')
                                                <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    表示
                                                </a>
                                                <a href="#" class="text-green-600 hover:text-green-900">
                                                    ダウンロード
                                                </a>
                                            @else
                                                <span class="text-gray-400">処理中...</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-4 text-right">
                            <a href="#" class="text-sm text-blue-600 hover:underline">
                                すべてのレポートを表示 →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
