<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('サブスクリプション管理') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- サブスクリプション概要 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">サブスクリプション概要</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">総サブスクリプション数</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('sub_status', '!=', 'trial')->count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">トライアルユーザー数</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('sub_status', '=', 'trial')->count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">最多プラン</h4>
                            <p class="text-2xl font-bold">スターター</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">月間売上</h4>
                            <p class="text-2xl font-bold">¥350,000</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- プラン別ユーザー数 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">プラン別ユーザー数</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">トライアル</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('plan_name', '=', 'trial')->count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">スターター</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('plan_name', '=', 'starter')->count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">プロ</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('plan_name', '=', 'pro')->count() }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">エージェンシー</h4>
                            <p class="text-2xl font-bold">{{ \App\Models\User::where('plan_name', '=', 'agency')->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近の支払い -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">最近の支払い</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-center text-gray-500">サブスクリプション支払い履歴はまだ実装されていません。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
