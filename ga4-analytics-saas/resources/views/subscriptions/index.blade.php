<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('サブスクリプション管理') }}
        </h2>
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

            <!-- 現在のプラン情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">現在のプラン</h3>

                    <div class="bg-gray-50 p-6 rounded-lg">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div>
                                <h4 class="text-xl font-bold">{{ ucfirst(Auth::user()->plan_name) }}</h4>

                                @if (Auth::user()->subscription_status === 'trial')
                                    <p class="text-sm text-yellow-600 mt-1">
                                        @if (Auth::user()->trial_ends_at && now()->lt(\Carbon\Carbon::parse(Auth::user()->trial_ends_at)))
                                            無料トライアル中 - あと{{ (int)now()->diffInDays(\Carbon\Carbon::parse(Auth::user()->trial_ends_at)) }}日（{{ \Carbon\Carbon::parse(Auth::user()->trial_ends_at)->format('Y年m月d日') }}まで）
                                        @else
                                            トライアル期間が終了しました
                                        @endif
                                    </p>
                                @else
                                    <p class="text-sm text-green-600 mt-1">有効なサブスクリプション</p>
                                @endif

                                <div class="mt-3">
                                    <p class="text-sm text-gray-600">Webサイト登録上限: <span class="font-medium">{{ Auth::user()->website_limit }} サイト</span></p>
                                    <p class="text-sm text-gray-600">現在の登録数: <span class="font-medium">{{ Auth::user()->websites()->count() }} サイト</span></p>
                                </div>
                            </div>

                            @if (Auth::user()->subscription_status === 'trial')
                                <a href="#plans" class="mt-4 md:mt-0 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    有料プランにアップグレード
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- プラン一覧 -->
            <div id="plans" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">ご利用可能なプラン</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- スタータープラン -->
                        <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-blue-50 p-4">
                                <h4 class="text-xl font-bold text-blue-800">スタータープラン</h4>
                                <p class="text-2xl font-bold mt-2">¥5,000<span class="text-sm font-normal">/月</span></p>
                            </div>

                            <div class="p-4">
                                <ul class="space-y-2">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Webサイト登録 最大3サイト</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>GA4・Search Console連携</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>月間レポート数 10件</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>メールサポート</span>
                                    </li>
                                </ul>

                                <div class="mt-6">
                                    <form action="{{ route('subscriptions.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan" value="starter">
                                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                            このプランを選択
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- プロプラン -->
                        <div class="border-2 border-indigo-500 rounded-lg overflow-hidden hover:shadow-lg transition-shadow relative">
                            <div class="absolute top-0 right-0 bg-indigo-500 text-white px-3 py-1 text-xs font-bold">
                                オススメ
                            </div>

                            <div class="bg-indigo-50 p-4">
                                <h4 class="text-xl font-bold text-indigo-800">プロプラン</h4>
                                <p class="text-2xl font-bold mt-2">¥12,000<span class="text-sm font-normal">/月</span></p>
                            </div>

                            <div class="p-4">
                                <ul class="space-y-2">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Webサイト登録 最大10サイト</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>GA4・Search Console連携</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>月間レポート数 無制限</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>優先サポート（電話・メール）</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>カスタムレポート機能</span>
                                    </li>
                                </ul>

                                <div class="mt-6">
                                    <form action="{{ route('subscriptions.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan" value="pro">
                                        <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                            このプランを選択
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- エージェンシープラン -->
                        <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-purple-50 p-4">
                                <h4 class="text-xl font-bold text-purple-800">エージェンシープラン</h4>
                                <p class="text-2xl font-bold mt-2">¥30,000<span class="text-sm font-normal">/月</span></p>
                            </div>

                            <div class="p-4">
                                <ul class="space-y-2">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Webサイト登録 最大30サイト</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>GA4・Search Console連携</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>月間レポート数 無制限</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>専任サポート担当者</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>ホワイトラベル対応</span>
                                    </li>
                                </ul>

                                <div class="mt-6">
                                    <form action="{{ route('subscriptions.subscribe') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan" value="agency">
                                        <button type="submit" class="w-full bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                            このプランを選択
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 支払い情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">支払い情報</h3>

                    <div class="bg-gray-50 p-6 rounded-lg">
                        @if (Auth::user()->subscription_status !== 'trial')
                            <div class="mb-4">
                                <h4 class="font-medium mb-2">クレジットカード情報</h4>
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span>**** **** **** 1234</span>
                                    <span class="ml-2 text-sm text-gray-500">有効期限: 12/25</span>
                                    <button class="ml-4 text-sm text-blue-600 hover:text-blue-800">変更</button>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-medium mb-2">支払い履歴</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    日付
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    内容
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    金額
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    ステータス
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    領収書
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    2025/03/01
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    プロプラン (月額)
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    ¥12,000
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        支払い済み
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <a href="#" class="text-blue-600 hover:text-blue-800">ダウンロード</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    2025/02/01
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    プロプラン (月額)
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    ¥12,000
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        支払い済み
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <a href="#" class="text-blue-600 hover:text-blue-800">ダウンロード</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <p class="text-center text-gray-500">プランをアップグレードすると、ここに支払い情報が表示されます。</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- サブスクリプション管理 -->
            @if (Auth::user()->subscription_status !== 'trial')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">サブスクリプション管理</h3>

                        <div class="space-y-4">
                            <div>
                                <form method="POST" action="{{ route('subscriptions.change-plan') }}">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-800 font-medium" onclick="return confirm('プランを変更しますか？現在のプランは次回の請求日まで有効です。')">
                                        プランを変更する
                                    </button>
                                </form>
                            </div>

                            <div>
                                <form method="POST" action="{{ route('subscriptions.cancel') }}">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" onclick="return confirm('サブスクリプションをキャンセルしますか？この操作は取り消せません。')">
                                        サブスクリプションをキャンセルする
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
