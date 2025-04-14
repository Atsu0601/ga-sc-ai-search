<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $website->name }}
            </h2>
            <div>
                <a href="{{ route('websites.edit', $website->id) }}"
                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">
                    編集
                </a>
                <a href="{{ route('websites.index') }}"
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

            <!-- ウェブサイト情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ウェブサイト情報</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">ウェブサイト名</p>
                            <p class="font-medium">{{ $website->name }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">URL</p>
                            <p class="font-medium">
                                <a href="{{ $website->url }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $website->url }}
                                </a>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">状態</p>
                            <p class="font-medium">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $website->status === 'active'
                                        ? 'bg-green-100 text-green-800'
                                        : ($website->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-red-100 text-red-800') }}">
                                    {{ $website->status === 'active' ? '有効' : ($website->status === 'pending' ? '準備中' : '無効') }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">登録日</p>
                            <p class="font-medium">{{ $website->created_at->format('Y年m月d日') }}</p>
                        </div>
                    </div>

                    @if ($website->description)
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">説明</p>
                            <p class="mt-1">{{ $website->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- API連携状態 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">API連携状態</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Google Analytics -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium">Google Analytics (GA4)</h4>

                                @if ($website->analyticsAccount)
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        接続済み
                                    </span>
                                @else
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        未接続
                                    </span>
                                @endif
                            </div>

                            @if ($website->analyticsAccount)
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600">プロパティID</p>
                                    <p class="font-medium">{{ $website->analyticsAccount->property_id }}</p>
                                </div>

                                <div class="mb-4">
                                    <p class="text-sm text-gray-600">最終同期日時</p>
                                    <p class="font-medium">
                                        {{ $website->analyticsAccount->last_synced_at ? $website->analyticsAccount->last_synced_at->format('Y年m月d日 H:i') : '未同期' }}
                                    </p>
                                </div>

                                <div class="flex space-x-2">
                                    <a href="{{ route('google.analytics.redirect', $website->id) }}"
                                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                        データ取得
                                    </a>
                                    <form action="{{ route('google.analytics.disconnect', $website->id) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500"
                                            onclick="return confirm('Google Analyticsの接続を解除してもよろしいですか？')">
                                            接続解除
                                        </button>
                                    </form>
                                </div>
                            @else
                                <p class="text-sm text-gray-600 mb-4">
                                    Google Analyticsと接続することで、サイトのアクセス解析データを取得できます。
                                </p>

                                <a href="{{ route('google.analytics.redirect', $website->id) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">
                                    Google Analyticsと接続
                                </a>
                            @endif
                        </div>

                        <!-- Search Console -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium">Google Search Console</h4>

                                @if ($website->searchConsoleAccount && $website->searchConsoleAccount->site_url)
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        接続済み
                                    </span>
                                @else
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        未接続
                                    </span>
                                @endif
                            </div>

                            @if ($website->searchConsoleAccount && $website->searchConsoleAccount->site_url)
                                <div class="text-sm text-gray-600 mb-4">
                                    <p><span class="font-medium">サイトURL:</span>
                                        {{ $website->searchConsoleAccount->site_url }}</p>
                                    <p><span class="font-medium">最終同期:</span>
                                        {{ $website->searchConsoleAccount->last_synced_at ? $website->searchConsoleAccount->last_synced_at->format('Y/m/d H:i') : '未同期' }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ route('snapshots.create', $website->id) }}"
                                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                        データ取得
                                    </a>
                                    <form action="{{ route('google.searchconsole.disconnect', $website->id) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500"
                                            onclick="return confirm('Search Consoleの接続を解除してもよろしいですか？')">
                                            接続解除
                                        </button>
                                    </form>
                                </div>
                            @else
                                <p class="text-sm text-gray-600 mb-4">
                                    サイトのSearch Consoleデータを取得するには、Google Search Consoleとの接続が必要です。
                                </p>
                                <a href="{{ route('google.searchconsole.redirect', $website->id) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">
                                    Search Consoleと接続する
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- データ分析セクション -->
            @if ($website->status === 'active')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">データ分析</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="{{ route('reports.create', $website->id) }}?type=executive"
                                class="block p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-150">
                                <h4 class="font-medium text-blue-800 mb-2">経営者向けレポート</h4>
                                <p class="text-sm text-gray-600">ビジネス指標に焦点を当てた包括的な分析レポート</p>
                            </a>

                            <a href="{{ route('reports.create', $website->id) }}?type=technical"
                                class="block p-4 bg-green-50 hover:bg-green-100 rounded-lg transition duration-150">
                                <h4 class="font-medium text-green-800 mb-2">技術者向けレポート</h4>
                                <p class="text-sm text-gray-600">サイトパフォーマンスや技術的な問題に焦点を当てた分析</p>
                            </a>

                            <a href="{{ route('reports.create', $website->id) }}?type=content"
                                class="block p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-150">
                                <h4 class="font-medium text-purple-800 mb-2">コンテンツ向けレポート</h4>
                                <p class="text-sm text-gray-600">コンテンツのパフォーマンスと改善提案を含む分析</p>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-yellow-800 font-medium">データ分析を利用するには、まずGoogle AnalyticsとSearch Consoleを接続してください。
                        </p>
                    </div>
                </div>
            @endif

            <!-- データスナップショット -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">データスナップショット</h3>
                        <a href="{{ route('snapshots.index', $website->id) }}"
                            class="text-blue-600 hover:text-blue-800">
                            すべてのスナップショットを表示 →
                        </a>
                    </div>

                    <p class="text-gray-600 mb-4">データスナップショットは、Google AnalyticsとSearch Consoleからのデータを日別に保存したものです。</p>

                    <div class="flex space-x-2">
                        <form action="{{ route('snapshots.create', $website->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded">
                                新規スナップショット作成
                            </button>
                        </form>

                        <a href="{{ route('snapshots.index', $website->id) }}"
                            class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded">
                            スナップショット一覧
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
