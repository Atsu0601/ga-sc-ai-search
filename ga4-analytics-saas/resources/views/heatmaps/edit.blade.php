<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ヒートマップ編集') }} - {{ $website->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('websites.heatmaps.update', [$website->id, $heatmap->id]) }}"
                        id="heatmapForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label for="page_url" :value="__('ページURL')" />
                            @if (count($pageUrls) > 0)
                                <select id="page_url" name="page_url"
                                    class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">
                                    @foreach ($pageUrls as $url)
                                        <option value="{{ $url }}"
                                            {{ old('page_url', $heatmap->page_url) == $url ? 'selected' : '' }}>
                                            {{ $url }}</option>
                                    @endforeach
                                </select>
                            @else
                                <x-text-input id="page_url" class="block mt-1 w-full" type="url" name="page_url"
                                    :value="old('page_url', $heatmap->page_url)" required />
                            @endif
                            <x-input-error :messages="$errors->get('page_url')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="type" :value="__('ヒートマップ種類')" />
                            <select id="type" name="type"
                                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('type', $heatmap->type) == $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="date_range_start" :value="__('開始日')" />
                                <x-text-input id="date_range_start" class="block mt-1 w-full" type="date"
                                    name="date_range_start" :value="old('date_range_start', $heatmap->date_range_start->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date_range_start')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="date_range_end" :value="__('終了日')" />
                                <x-text-input id="date_range_end" class="block mt-1 w-full" type="date"
                                    name="date_range_end" :value="old('date_range_end', $heatmap->date_range_end->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('date_range_end')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center">
                                <input id="regenerate_data" name="regenerate_data" type="checkbox" value="yes"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="regenerate_data" class="ml-2 block text-sm text-gray-700">データを再生成する</label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">チェックすると、選択したページURLと日付範囲に基づいてデータが再生成されます。</p>
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
                                        最新のGoogle Analyticsデータでヒートマップを更新したい場合は、「データを再生成する」にチェックを入れてください。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            <div>
                                <a href="{{ route('websites.heatmaps.show', [$website->id, $heatmap->id]) }}"
                                    class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                                    キャンセル
                                </a>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('websites.heatmaps.refresh-data', [$website->id, $heatmap->id]) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('最新データに更新') }}
                                </a>
                                <x-primary-button type="submit" id="submitBtn">
                                    {{ __('保存') }}
                                </x-primary-button>
                            </div>
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

                // タイプ変更時の警告
                const originalType = "{{ $heatmap->type }}";
                const typeSelect = document.getElementById('type');

                typeSelect.addEventListener('change', function() {
                    if (this.value !== originalType) {
                        if (!confirm('種類を変更すると、データ形式が異なるため問題が発生する可能性があります。続行しますか？')) {
                            this.value = originalType;
                        } else {
                            // 種類を変更した場合は自動的に再生成チェックボックスをオンにする
                            document.getElementById('regenerate_data').checked = true;
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
