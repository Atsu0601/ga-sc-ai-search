<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('管理者ダッシュボード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- 管理メニュー -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">管理メニュー</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="" class="block p-6 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-150">
                            <h4 class="font-medium text-blue-800 mb-2">ユーザー管理</h4>
                            <p class="text-sm text-gray-600">ユーザーアカウントと会社情報の管理を行います</p>
                        </a>

                        <a href="{{ route('subscriptions.index') }}" class="block p-6 bg-green-50 hover:bg-green-100 rounded-lg transition duration-150">
                            <h4 class="font-medium text-green-800 mb-2">サブスクリプション管理</h4>
                            <p class="text-sm text-gray-600">課金プランと支払い状況を管理します</p>
                        </a>

                        <a href="{{ route('settings.index') }}" class="block p-6 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-150">
                            <h4 class="font-medium text-purple-800 mb-2">システム設定</h4>
                            <p class="text-sm text-gray-600">APIキーやシステム設定を管理します</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- システム概要 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">システム概要</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">ユーザー数</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">登録サイト数</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\Website::count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">総レポート数</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\AnalysisReport::count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">有効サブスクリプション</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('subscription_status', '!=', 'trial')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近の活動 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">最近の活動</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-center text-gray-500">アクティビティログはまだ実装されていません</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
