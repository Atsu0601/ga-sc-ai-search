<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('新規ヒートマップ作成') }} - {{ $website->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('websites.heatmaps.store', $website->id) }}" id="heatmapForm">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="page_url" :value="__('ページURL')" />
                            @if (count($pageUrls) > 0)
                                <select id="page_url" name="page_url"
                                    class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">
                                    @foreach ($pageUrls as $url)
                                        <option value="{{ $url }}"
                                            {{ old('page_url') == $url ? 'selected' : '' }}>{{ $url }}</option>
                                    @endforeach
                                </select>
                            @else
                                <x-text-input id="page_url" class="block mt-1 w-full" type="url" name="page_url"
                                    :value="old('page_url', $website->url)" required />
                            @endif
                            <x-input-error :messages="$errors->get('page_url')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">ヒートマップを生成するウェブページのURLを選択または入力してください。</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="type" :value="__('ヒートマップ種類')" />
                            <select id="type" name="type"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="date_range_start" :value="__('開始日')" />
                                <x-text-input id="date_range_start" class="block mt-1 w-full" type="date"
                                    name="date_range_start" :value="old('date_range_start') ?? now()->subDays(30)->format('Y-m-d')" required />
                                <x-input-error :messages="$errors->get('date_range_start')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="date_range_end" :value="__('終了日')" />
                                <x-text-input id="date_range_end" class="block mt-1 w-full" type="date"
                                    name="date_range_end" :value="old('date_range_end') ?? now()->format('Y-m-d')" required />
                                <x-input-error :messages="$errors->get('date_range_end')" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-md mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        選択した期間のGoogle
                                        Analyticsデータからヒートマップを生成します。データスナップショットが該当期間に存在しない場合は、自動的にサンプルデータが生成されます。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">ヒートマップの種類について</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium text-gray-700 mb-1">クリックヒートマップ</h4>
                                    <p class="text-sm text-gray-600">
                                        訪問者がページ上でどこをクリックしているか視覚化します。コンバージョンに重要な要素を特定するのに役立ちます。</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium text-gray-700 mb-1">スクロールヒートマップ</h4>
                                    <p class="text-sm text-gray-600">
                                        訪問者がページのどこまでスクロールしているかを示します。重要なコンテンツが適切な位置にあるか確認できます。</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium text-gray-700 mb-1">マウス移動ヒートマップ</h4>
                                    <p class="text-sm text-gray-600">
                                        マウスカーソルの動きを追跡して、ユーザーの注目エリアを予測します。視線の動きと関連性があるとされています。</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <h4 class="font-medium text-gray-700 mb-1">注目度ヒートマップ</h4>
                                    <p class="text-sm text-gray-600">滞在時間やマウスの動きを基に、ページ上のどの領域に最も注目が集まっているかを視覚化します。</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('websites.heatmaps.index', $website->id) }}"
                                class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                                キャンセル
                            </a>
                            <x-primary-button type="submit" id="submitBtn">
                                {{ __('保存') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 日付範囲の制限（過去90日までに制限）
                const dateRangeStart = document.getElementById('date_range_start');
                const dateRangeEnd = document.getElementById('date_range_end');

                // 最大日付（今日）
                const maxDate = new Date().toISOString().split('T')[0];

                // 最小日付（90日前）
                const minDate = new Date();
                minDate.setDate(minDate.getDate() - 90);
                const minDateStr = minDate.toISOString().split('T')[0];

                dateRangeStart.max = maxDate;
                dateRangeStart.min = minDateStr;
                dateRangeEnd.max = maxDate;
                dateRangeEnd.min = minDateStr;

                // 日付範囲の整合性チェック
                dateRangeStart.addEventListener('change', function() {
                    if (dateRangeEnd.value < dateRangeStart.value) {
                        dateRangeEnd.value = dateRangeStart.value;
                    }
                });

                dateRangeEnd.addEventListener('change', function() {
                    if (dateRangeEnd.value < dateRangeStart.value) {
                        dateRangeStart.value = dateRangeEnd.value;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
