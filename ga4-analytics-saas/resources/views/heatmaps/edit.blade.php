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
                            <x-text-input id="page_url" class="block mt-1 w-full" type="url" name="page_url"
                                :value="old('page_url', $heatmap->page_url)" required />
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
                            <x-input-label :value="__('データ編集')" />

                            <div class="mt-2 flex space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" id="dataEditNo" name="dataEdit" value="no"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        checked>
                                    <label for="dataEditNo" class="ml-2 block text-sm text-gray-700">データを変更しない</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="dataEditYes" name="dataEdit" value="yes"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="dataEditYes" class="ml-2 block text-sm text-gray-700">データを編集する</label>
                                </div>
                            </div>
                        </div>

                        <div id="dataEditSection" class="mb-6 hidden">
                            <div class="bg-gray-50 p-4 rounded-md">
                                @if ($heatmap->type === 'click')
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500 mb-2">
                                            現在のデータには <strong>{{ count($heatmap->getData()['clicks'] ?? []) }}</strong>
                                            件のクリックデータがあります。
                                        </p>
                                        <textarea id="clickData" rows="10"
                                            class="w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ json_encode($heatmap->getData(), JSON_PRETTY_PRINT) }}</textarea>
                                    </div>
                                @elseif($heatmap->type === 'scroll')
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500 mb-2">
                                            現在のデータには
                                            <strong>{{ count($heatmap->getData()['scrollDepth'] ?? []) }}</strong>
                                            件のスクロール深度データがあります。
                                        </p>
                                        <textarea id="scrollData" rows="10"
                                            class="w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ json_encode($heatmap->getData(), JSON_PRETTY_PRINT) }}</textarea>
                                    </div>
                                @endif
                                <p class="text-xs text-gray-500">
                                    JSONフォーマットで編集してください。形式が正しくないとエラーになります。
                                </p>
                            </div>
                        </div>

                        <!-- 隠しフィールド - JSON形式のデータ -->
                        <input type="hidden" id="data_json" name="data_json"
                            value="{{ json_encode($heatmap->getData()) }}">

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('websites.heatmaps.show', [$website->id, $heatmap->id]) }}"
                                class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                                キャンセル
                            </a>
                            <x-primary-button type="submit" id="submitBtn">
                                {{ __('更新') }}
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
                // データ編集切り替え
                const dataEditNo = document.getElementById('dataEditNo');
                const dataEditYes = document.getElementById('dataEditYes');
                const dataEditSection = document.getElementById('dataEditSection');

                dataEditNo.addEventListener('change', function() {
                    dataEditSection.classList.add('hidden');
                });

                dataEditYes.addEventListener('change', function() {
                    dataEditSection.classList.remove('hidden');
                });

                // タイプ変更時の警告
                const originalType = "{{ $heatmap->type }}";
                const typeSelect = document.getElementById('type');

                typeSelect.addEventListener('change', function() {
                    if (this.value !== originalType) {
                        if (!confirm('種類を変更すると、データ形式が異なるため問題が発生する可能性があります。続行しますか？')) {
                            this.value = originalType;
                        }
                    }
                });

                // フォーム送信前の処理
                document.getElementById('heatmapForm').addEventListener('submit', function(e) {
                    if (dataEditYes.checked) {
                        try {
                            let dataJson;

                            if (document.getElementById('clickData')) {
                                dataJson = JSON.parse(document.getElementById('clickData').value);
                            } else if (document.getElementById('scrollData')) {
                                dataJson = JSON.parse(document.getElementById('scrollData').value);
                            }

                            document.getElementById('data_json').value = JSON.stringify(dataJson);
                        } catch (error) {
                            e.preventDefault();
                            alert('JSONデータの形式が正しくありません: ' + error.message);
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
