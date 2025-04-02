<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('システム設定') }}
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

            <!-- API設定 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">API設定</h3>

                    <form method="POST" action="{{ route('admin.settings.update.api') }}">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="google_client_id" :value="__('Google Client ID')" />
                            <x-text-input id="google_client_id" class="block mt-1 w-full" type="text" name="google_client_id" :value="$apiSettings['google_client_id']->value ?? ''" />
                            <p class="mt-1 text-sm text-gray-600">Google AnalyticsとSearch Console連携用のClient ID</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="google_client_secret" :value="__('Google Client Secret')" />
                            <x-text-input id="google_client_secret" class="block mt-1 w-full" type="password" name="google_client_secret" :value="$apiSettings['google_client_secret']->value ?? ''" />
                            <p class="mt-1 text-sm text-gray-600">Google AnalyticsとSearch Console連携用のClient Secret</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="openai_api_key" :value="__('OpenAI API Key')" />
                            <x-text-input id="openai_api_key" class="block mt-1 w-full" type="password" name="openai_api_key" :value="$apiSettings['openai_api_key']->value ?? ''" />
                            <p class="mt-1 text-sm text-gray-600">AIレポート生成用のOpenAI API Key</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="stripe_key" :value="__('Stripe公開キー')" />
                            <x-text-input id="stripe_key" class="block mt-1 w-full" type="text" name="stripe_key" :value="$apiSettings['stripe_key']->value ?? ''" />
                            <p class="mt-1 text-sm text-gray-600">決済処理用のStripe公開キー</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="stripe_secret" :value="__('Stripeシークレットキー')" />
                            <x-text-input id="stripe_secret" class="block mt-1 w-full" type="password" name="stripe_secret" :value="$apiSettings['stripe_secret']->value ?? ''" />
                            <p class="mt-1 text-sm text-gray-600">決済処理用のStripeシークレットキー</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('設定を保存') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- システム設定 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">システム設定</h3>

                    <form method="POST" action="{{ route('admin.settings.update.system') }}">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="trial_days" :value="__('無料トライアル期間（日数）')" />
                            <x-text-input id="trial_days" class="block mt-1 w-full" type="number" name="trial_days" :value="$systemSettings['trial_days']->value ?? 14" min="1" />
                        </div>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <input id="maintenance_mode" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="maintenance_mode" {{ isset($systemSettings['maintenance_mode']) && $systemSettings['maintenance_mode']->value ? 'checked' : '' }}>
                                <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">メンテナンスモード</label>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">有効にするとシステムがメンテナンスモードになり、管理者以外はアクセスできなくなります</p>
                        </div>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <input id="debug_mode" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="debug_mode" {{ isset($systemSettings['debug_mode']) && $systemSettings['debug_mode']->value ? 'checked' : '' }}>
                                <label for="debug_mode" class="ml-2 block text-sm text-gray-900">デバッグモード</label>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">有効にするとエラー詳細が表示されます（本番環境では無効にしてください）</p>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('設定を保存') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- キャッシュ管理 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">キャッシュ管理</h3>

                    <div class="space-y-3">
                        <div>
                            <form method="POST" action="{{ route('admin.settings.cache.clear') }}">
                                @csrf
                                <x-primary-button>
                                    {{ __('キャッシュをクリア') }}
                                </x-primary-button>
                            </form>
                        </div>

                        <div>
                            <form method="POST" action="{{ route('admin.settings.cache.config') }}">
                                @csrf
                                <x-primary-button>
                                    {{ __('設定をキャッシュ') }}
                                </x-primary-button>
                            </form>
                        </div>

                        <div>
                            <form method="POST" action="{{ route('admin.settings.cache.routes') }}">
                                @csrf
                                <x-primary-button>
                                    {{ __('ルートをキャッシュ') }}
                                </x-primary-button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
