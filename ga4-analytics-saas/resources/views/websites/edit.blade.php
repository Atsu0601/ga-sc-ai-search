<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $website->name }} - 編集
            </h2>
            <div>
                <a href="{{ route('websites.show', $website->id) }}"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    詳細に戻る
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- 更新フォーム -->
                    <form method="POST" action="{{ route('websites.update', $website->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- ウェブサイト名 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">ウェブサイト名 <span
                                    class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name"
                                value="{{ old('name', $website->name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                                required>
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- URL -->
                        <div>
                            <label for="url" class="block text-sm font-medium text-gray-700">URL <span
                                    class="text-red-600">*</span></label>
                            <input type="url" name="url" id="url" value="{{ old('url', $website->url) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('url') border-red-500 @enderror"
                                required>
                            @error('url')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">例: https://example.com</p>
                        </div>

                        <!-- 説明 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-500 @enderror">{{ old('description', $website->description) }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ステータス -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">ステータス <span
                                    class="text-red-600">*</span></label>
                            <select name="status" id="status"
                                class="mt-1 block w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('status') border-red-500 @enderror">
                                <option value="active"
                                    {{ old('status', $website->status) === 'active' ? 'selected' : '' }}>有効</option>
                                <option value="pending"
                                    {{ old('status', $website->status) === 'pending' ? 'selected' : '' }}>準備中</option>
                                <option value="inactive"
                                    {{ old('status', $website->status) === 'inactive' ? 'selected' : '' }}>無効</option>
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-between items-center pt-4">
                            <div class="flex space-x-3">
                                <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    更新する
                                </button>
                                <a href="{{ route('websites.show', $website->id) }}"
                                    class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    キャンセル
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- 削除フォーム - 更新フォームとは別に配置 -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <form method="POST" action="{{ route('websites.destroy', $website->id) }}"
                            onsubmit="return confirm('本当にこのウェブサイトを削除しますか？この操作は元に戻せません。');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                ウェブサイトを削除
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- API連携の警告 -->
            @if ($website->analyticsAccount || $website->searchConsoleAccount)
                <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-yellow-200">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <h3 class="text-lg font-medium text-yellow-800">API連携に関する注意</h3>
                                <p class="text-yellow-700 mt-1">URLを変更すると、既存のGoogle Analytics・Search
                                    Console連携に影響が出る可能性があります。URLを変更した場合は、API連携の再設定をお勧めします。</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- データ削除の警告 -->
            <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-red-200">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-lg font-medium text-red-800">ウェブサイト削除に関する注意</h3>
                            <p class="text-red-700 mt-1">
                                ウェブサイトを削除すると、そのウェブサイトに関連するすべてのデータ（アナリティクス連携、スナップショット、レポートなど）も削除されます。この操作は元に戻せません。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
