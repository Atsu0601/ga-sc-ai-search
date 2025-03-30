<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('ユーザー詳細') }}
            </h2>
            <div>
                <a href="{{ route('admin.users.edit', $user->id) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">
                    編集
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
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

            <!-- ユーザー情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ユーザー情報</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">ユーザー名</p>
                            <p class="font-medium">{{ $user->name }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">メールアドレス</p>
                            <p class="font-medium">{{ $user->email }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">ユーザー権限</p>
                            <p class="font-medium">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $user->role === 'admin' ? '管理者' : '一般ユーザー' }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">登録日</p>
                            <p class="font-medium">{{ $user->created_at->format('Y年m月d日') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- サブスクリプション情報 -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">サブスクリプション情報</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">現在のプラン</p>
                            <p class="font-medium">{{ ucfirst($user->plan_name) }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">ステータス</p>
                            <p class="font-medium">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->sub_status === 'trial' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $user->sub_status === 'trial' ? 'トライアル' : '有料プラン' }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Webサイト登録上限</p>
                            <p class="font-medium">{{ $user->website_limit }} サイト</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">トライアル期限</p>
                            <p class="font-medium">
                                @if ($user->trial_ends_at && now()->lt(\Carbon\Carbon::parse($user->trial_ends_at)))
                                    {{ \Carbon\Carbon::parse($user->trial_ends_at)->format('Y年m月d日') }}まで（残り{{ (int)now()->diffInDays(\Carbon\Carbon::parse($user->trial_ends_at)) }}日）
                                @elseif ($user->trial_ends_at)
                                    {{ \Carbon\Carbon::parse($user->trial_ends_at)->format('Y年m月d日') }}に終了
                                @else
                                    なし
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 会社情報 -->
            @if($user->company)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">会社情報</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">会社名</p>
                            <p class="font-medium">{{ $user->company->name }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">請求先メールアドレス</p>
                            <p class="font-medium">{{ $user->company->billing_email ?? '未設定' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">担当者名</p>
                            <p class="font-medium">{{ $user->company->contact_name ?? '未設定' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">電話番号</p>
                            <p class="font-medium">{{ $user->company->phone ?? '未設定' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">住所</p>
                            <p class="font-medium">
                                {{ $user->company->zip_code ? '〒'.$user->company->zip_code : '' }}
                                {{ $user->company->state ?? '' }}
                                {{ $user->company->city ?? '' }}
                                {{ $user->company->address ?? '' }}
                                {{ $user->company->country ?? '' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 登録Webサイト -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">登録Webサイト（{{ $user->websites->count() }}/{{ $user->website_limit }}）</h3>

                    @if($user->websites->isEmpty())
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-center text-gray-500">Webサイトが登録されていません。</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($user->websites as $website)
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
