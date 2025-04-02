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

            @if (session('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('info') }}</span>
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
                                @elseif (Auth::user()->subscription_status === 'cancelled')
                                    <p class="text-sm text-red-600 mt-1">
                                        キャンセル済み - 次回の請求日までご利用いただけます
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
                        @foreach ($plans as $plan)
                        <div class="border {{ $plan->is_featured ? 'border-2 border-indigo-500' : '' }} rounded-lg overflow-hidden hover:shadow-lg transition-shadow {{ $plan->is_featured ? 'relative' : '' }}">
                            @if ($plan->is_featured)
                            <div class="absolute top-0 right-0 bg-indigo-500 text-white px-3 py-1 text-xs font-bold">
                                オススメ
                            </div>
                            @endif

                            <div class="bg-{{ $plan->name === 'starter' ? 'blue' : ($plan->name === 'pro' ? 'indigo' : 'purple') }}-50 p-4">
                                <h4 class="text-xl font-bold text-{{ $plan->name === 'starter' ? 'blue' : ($plan->name === 'pro' ? 'indigo' : 'purple') }}-800">{{ $plan->display_name }}</h4>
                                <p class="text-2xl font-bold mt-2">¥{{ number_format($plan->price) }}<span class="text-sm font-normal">/{{ $plan->billing_period }}</span></p>
                            </div>

                            <div class="p-4">
                                <ul class="space-y-2">
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Webサイト登録 最大{{ $plan->website_limit }}サイト</span>
                                    </li>
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>GA4・Search Console連携</span>
                                    </li>
                                    <!-- その他の機能 -->
                                </ul>

                                <div class="mt-6">
                                    @if (Auth::user()->plan_name === $plan->name && Auth::user()->subscription_status !== 'trial')
                                        <button class="w-full bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded cursor-not-allowed">
                                            現在のプラン
                                        </button>
                                    @else
                                        <form action="{{ route('subscriptions.checkout') }}" method="GET">
                                            <input type="hidden" name="plan" value="{{ $plan->name }}">
                                            <button type="submit" class="w-full bg-{{ $plan->name === 'starter' ? 'blue' : ($plan->name === 'pro' ? 'indigo' : 'purple') }}-500 hover:bg-{{ $plan->name === 'starter' ? 'blue' : ($plan->name === 'pro' ? 'indigo' : 'purple') }}-700 text-white font-bold py-2 px-4 rounded">
                                                このプランを選択
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
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
                                <h4 class="font-medium mb-2">お支払い方法</h4>
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span>クレジットカード（Stripe決済）</span>

                                    @if (Auth::user()->stripe_id)
                                        <a href="{{ route('subscriptions.checkout', ['plan' => Auth::user()->plan_name, 'update_payment' => true]) }}" class="ml-4 text-sm text-blue-600 hover:text-blue-800">
                                            支払い方法を変更
                                        </a>
                                    @endif
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
                                            <!-- Webhookでインボイスデータを取得して表示するか、Stripeからデータを取得して表示 -->
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                    @if (Auth::user()->stripe_id)
                                                        支払い履歴はStripeから自動的に同期されます
                                                    @else
                                                        支払い履歴がありません
                                                    @endif
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
                                <h4 class="font-medium mb-2">プラン変更</h4>
                                <form method="POST" action="{{ route('subscriptions.change-plan') }}">
                                    @csrf
                                    <div class="mb-4">
                                        <select name="plan" class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full sm:w-auto">
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->name }}" {{ Auth::user()->plan_name === $plan->name ? 'selected' : '' }}>
                                                    {{ $plan->display_name }} (¥{{ number_format($plan->price) }}/{{ $plan->billing_period }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('プランを変更しますか？現在のプランは次回の請求日まで有効です。')">
                                        プランを変更する
                                    </button>
                                </form>
                            </div>

                            @if (Auth::user()->subscription_status !== 'cancelled')
                                <div class="mt-6">
                                    <form method="POST" action="{{ route('subscriptions.cancel') }}">
                                        @csrf
                                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('サブスクリプションをキャンセルしますか？この操作は元に戻せません。')">
                                            サブスクリプションをキャンセルする
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
