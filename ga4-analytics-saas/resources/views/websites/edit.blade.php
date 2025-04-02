<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $website->name }} の編集
            </h2>
            <div>
                <a href="{{ route('websites.show', $website->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    詳細に戻る
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

            <!-- ウェブサイト編集フォーム -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">ウェブサイト情報の編集</h3>

                    <form method="POST" action="{{ route('websites.update', $website->id) }}">
                        @csrf
                        @method('PATCH')

                        <!-- ウェブサイト名 -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ウェブサイト名 <span class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $website->name) }}"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full sm:w-1/2"
                                required>
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- URL -->
                        <div class="mb-4">
                            <label for="url" class="block text-sm font-medium text-gray-700 mb-1">URL <span class="text-red-600">*</span></label>
                            <input type="url" name="url" id="url" value="{{ old('url', $website->url) }}"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full sm:w-1/2"
                                required>
                            @error('url')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 mt-1">例: https://example.com</p>
                        </div>

                        <!-- 説明 -->
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                            <textarea name="description" id="description" rows="3"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full">{{ old('description', $website->description) }}</textarea>
                            @error('description')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ステータス -->
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">ステータス <span class="text-red-600">*</span></label>
                            <select name="status" id="status"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block w-full sm:w-1/3">
                                <option value="active" {{ old('status', $website->status) === 'active' ? 'selected' : '' }}>有効</option>
                                <option value="pending" {{ old('status', $website->status) === 'pending' ? 'selected' : '' }}>準備中</option>
                                <option value="inactive" {{ old('status', $website->status) === 'inactive' ? 'selected' : '' }}>無効</option>
                            </select>
                            @error('status')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-between">
                            <div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    更新する
                                </button>
                                <a href="{{ route('websites.show', $website->id) }}" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    キャンセル
                                </a>
                            </div>

                            <!-- 削除ボタン -->
                            <form method="POST" action="{{ route('websites.destroy', $website->id) }}" class="inline" onsubmit="return confirm('本当にこのウェブサイトを削除しますか？この操作は元に戻せません。');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    ウェブサイトを削除
                                </button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>

            <!-- API連携の警告 -->
            @if($website->analyticsAccount || $website->searchConsoleAccount)
                <div class="bg-yellow-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 border-b border-yellow-200">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <h3 class="text-lg font-medium text-yellow-800">API連携に関する注意</h3>
                                <p class="text-yellow-700 mt-1">URLを変更すると、既存のGoogle Analytics・Search Console連携に影響が出る可能性があります。URLを変更した場合は、API連携の再設定をお勧めします。</p>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="text-lg font-medium text-red-800">ウェブサイト削除に関する注意</h3>
                            <p class="text-red-700 mt-1">ウェブサイトを削除すると、そのウェブサイトに関連するすべてのデータ（アナリティクス連携、スナップショット、レポートなど）も削除されます。この操作は元に戻せません。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
