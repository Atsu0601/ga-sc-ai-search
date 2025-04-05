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
                            <x-text-input id="page_url" class="block mt-1 w-full" type="url" name="page_url"
                                :value="old('page_url')" required />
                            <x-input-error :messages="$errors->get('page_url')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">ヒートマップを生成するウェブページのURLを入力してください。</p>
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
                                    name="date_range_start" :value="old('date_range_start') ?? now()->format('Y-m-d')" required />
                                <x-input-error :messages="$errors->get('date_range_start')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="date_range_end" :value="__('終了日')" />
                                <x-text-input id="date_range_end" class="block mt-1 w-full" type="date"
                                    name="date_range_end" :value="old('date_range_end') ?? now()->format('Y-m-d')" required />
                                <x-input-error :messages="$errors->get('date_range_end')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-6">
                            <x-input-label :value="__('データ入力方法')" />

                            <div class="mt-2 flex space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" id="inputTypeFile" name="dataInputType" value="file"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                        checked>
                                    <label for="inputTypeFile"
                                        class="ml-2 block text-sm text-gray-700">ファイルアップロード</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="inputTypeManual" name="dataInputType" value="manual"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="inputTypeManual" class="ml-2 block text-sm text-gray-700">手動入力</label>
                                </div>
                            </div>
                        </div>

                        <div id="fileUploadSection" class="mb-6">
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"
                                id="dropzone">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48" aria-hidden="true">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="fileInput"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>ファイルをアップロード</span>
                                            <input id="fileInput" name="file" type="file" class="sr-only"
                                                accept=".json,.csv">
                                        </label>
                                        <p class="pl-1">またはドラッグ＆ドロップ</p>
                                    </div>
                                    <p class="text-xs text-gray-500">JSON または CSV ファイル (最大 2MB)</p>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                <span class="font-medium">クリックヒートマップの場合:</span> 各行はクリック位置 (X,Y) とカウント数を含む
                                <br>
                                <span class="font-medium">スクロールヒートマップの場合:</span> 各行はスクロール深度とユーザー数を含む
                            </p>

                            <div id="previewContainer" class="mt-4 hidden">
                                <h3 class="text-lg font-medium text-gray-900">データプレビュー</h3>
                                <div id="previewTable" class="mt-2 overflow-x-auto"></div>
                            </div>
                        </div>

                        <div id="manualInputSection" class="mb-6 hidden">
                            <div class="flex space-x-2 mb-2">
                                <button type="button" id="addRowBtn"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 00-1 1v5H4a1 1 0 100 2h5v5a1 1 0 102 0v-5h5a1 1 0 100-2h-5V4a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    行を追加
                                </button>
                                <button type="button" id="addColBtn"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 3a1 1 0 00-1 1v5H4a1 1 0 100 2h5v5a1 1 0 102 0v-5h5a1 1 0 100-2h-5V4a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    列を追加
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200" id="manualInputTable">
                                    <!-- 入力テーブルの内容はJavaScriptで生成 -->
                                </table>
                            </div>
                        </div>

                        <!-- 隠しフィールド - JSON形式のデータ -->
                        <input type="hidden" id="data_json" name="data_json" value="">

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
                // データ入力方法切り替え
                const inputTypeFile = document.getElementById('inputTypeFile');
                const inputTypeManual = document.getElementById('inputTypeManual');
                const fileUploadSection = document.getElementById('fileUploadSection');
                const manualInputSection = document.getElementById('manualInputSection');

                inputTypeFile.addEventListener('change', function() {
                    fileUploadSection.classList.remove('hidden');
                    manualInputSection.classList.add('hidden');
                });

                inputTypeManual.addEventListener('change', function() {
                    fileUploadSection.classList.add('hidden');
                    manualInputSection.classList.remove('hidden');
                    initManualInputTable();
                });

                // ファイルアップロード処理
                const dropzone = document.getElementById('dropzone');
                const fileInput = document.getElementById('fileInput');
                const previewContainer = document.getElementById('previewContainer');
                const previewTable = document.getElementById('previewTable');
                const dataJsonInput = document.getElementById('data_json');

                fileInput.addEventListener('change', handleFileSelect);

                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('bg-gray-50');
                });

                dropzone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('bg-gray-50');
                });

                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('bg-gray-50');

                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        handleFileSelect(e);
                    }
                });

                function handleFileSelect(e) {
                    const file = fileInput.files[0];
                    if (!file) return;

                    const reader = new FileReader();

                    reader.onload = function(e) {
                        let data;
                        let html = '';

                        if (file.name.endsWith('.json')) {
                            // JSON処理
                            try {
                                data = JSON.parse(e.target.result);

                                // JSONプレビューテーブル作成
                                html =
                                    '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';

                                // 種類に応じたプレビュー
                                const type = document.getElementById('type').value;
                                if (type === 'click' && data.clicks) {
                                    html +=
                                        '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">X</th>' +
                                        '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Y</th>';
                                    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                                    // 最初の10件のみ表示
                                    const clicks = data.clicks.slice(0, 10);
                                    clicks.forEach(click => {
                                        html += '<tr>' +
                                            '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                            click.x + '</td>' +
                                            '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                            click.y + '</td>' +
                                            '</tr>';
                                    });

                                    if (data.clicks.length > 10) {
                                        html +=
                                            '<tr><td colspan="2" class="px-3 py-2 text-sm text-gray-500">... 他 ' + (
                                                data.clicks.length - 10) + ' レコード</td></tr>';
                                    }
                                } else if (type === 'scroll' && data.scrollDepth) {
                                    html +=
                                        '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">深度（px）</th>' +
                                        '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ユーザー数</th>';
                                    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                                    // スクロールデータ表示
                                    Object.entries(data.scrollDepth).forEach(([depth, count]) => {
                                        html += '<tr>' +
                                            '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                            depth + '</td>' +
                                            '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                            count + '</td>' +
                                            '</tr>';
                                    });
                                } else {
                                    // データ構造に応じた汎用表示
                                    const keys = Object.keys(data);
                                    keys.forEach(key => {
                                        html +=
                                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">' +
                                            key + '</th>';
                                    });

                                    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr>';

                                    keys.forEach(key => {
                                        const value = typeof data[key] === 'object' ? JSON.stringify(data[
                                            key]).substring(0, 50) + '...' : data[key];
                                        html +=
                                            '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                            value + '</td>';
                                    });

                                    html += '</tr>';
                                }

                                html += '</tbody></table>';
                                dataJsonInput.value = JSON.stringify(data);
                            } catch (error) {
                                html = '<div class="bg-red-50 p-2 rounded text-red-600">JSONの解析エラー: ' + error
                                    .message + '</div>';
                            }
                        } else if (file.name.endsWith('.csv')) {
                            // CSV処理
                            const lines = e.target.result.split('\n');
                            const headers = lines[0].split(',');

                            html =
                                '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';

                            headers.forEach(header => {
                                html +=
                                    '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">' +
                                    header.trim() + '</th>';
                            });

                            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                            // 種類に応じたデータ構造を作成
                            const type = document.getElementById('type').value;
                            if (type === 'click') {
                                // クリックヒートマップ用データ構造
                                const clicks = [];

                                for (let i = 1; i < lines.length; i++) {
                                    if (!lines[i].trim()) continue;

                                    const cols = lines[i].split(',');
                                    if (cols.length >= 2) {
                                        // 各行を表示してプレビュー
                                        html += '<tr>';
                                        cols.forEach(col => {
                                            html +=
                                                '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                                col.trim() + '</td>';
                                        });
                                        html += '</tr>';

                                        // データ構造に追加
                                        clicks.push({
                                            x: parseInt(cols[0].trim()),
                                            y: parseInt(cols[1].trim())
                                        });
                                    }
                                }

                                // 最大10行まで表示
                                if (lines.length > 11) {
                                    html += '<tr><td colspan="' + headers.length +
                                        '" class="px-3 py-2 text-sm text-gray-500">... 他 ' + (lines.length - 11) +
                                        ' レコード</td></tr>';
                                }

                                // 最終的なJSONデータを設定
                                data = {
                                    clicks: clicks,
                                    totalClicks: clicks.length
                                };
                                dataJsonInput.value = JSON.stringify(data);
                            } else if (type === 'scroll') {
                                // スクロールヒートマップ用データ構造
                                const scrollDepth = {};
                                let totalUsers = 0;

                                for (let i = 1; i < lines.length; i++) {
                                    if (!lines[i].trim()) continue;

                                    const cols = lines[i].split(',');
                                    if (cols.length >= 2) {
                                        // 各行を表示してプレビュー
                                        html += '<tr>';
                                        cols.forEach(col => {
                                            html +=
                                                '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">' +
                                                col.trim() + '</td>';
                                        });
                                        html += '</tr>';

                                        // データ構造に追加
                                        const depth = parseInt(cols[0].trim());
                                        const count = parseInt(cols[1].trim());
                                        scrollDepth[depth] = count;

                                        if (depth === 0) {
                                            totalUsers = count;
                                        }
                                    }
                                }

                                // 最終的なJSONデータを設定
                                data = {
                                    scrollDepth: scrollDepth,
                                    totalUsers: totalUsers
                                };
                                dataJsonInput.value = JSON.stringify(data);
                            }

                            html += '</tbody></table>';
                        } else {
                            html = '<div class="bg-red-50 p-2 rounded text-red-600">サポートされていないファイル形式です</div>';
                        }

                        // プレビュー表示
                        previewTable.innerHTML = html;
                        previewContainer.classList.remove('hidden');
                    };

                    if (file.name.endsWith('.json')) {
                        reader.readAsText(file);
                    } else if (file.name.endsWith('.csv')) {
                        reader.readAsText(file);
                    } else {
                        previewTable.innerHTML =
                            '<div class="bg-red-50 p-2 rounded text-red-600">サポートされていないファイル形式です</div>';
                        previewContainer.classList.remove('hidden');
                    }
                }

                // 手動入力テーブル初期化
                function initManualInputTable() {
                    const table = document.getElementById('manualInputTable');
                    const type = document.getElementById('type').value;

                    if (type === 'click') {
                        // クリックヒートマップ用入力テーブル
                        let html = '<thead class="bg-gray-50"><tr>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">X座標</th>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Y座標</th>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">回数</th>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>' +
                            '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                        // 初期行
                        for (let i = 0; i < 3; i++) {
                            html += '<tr>' +
                                '<td class="px-3 py-2"><input type="number" class="x-coord rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value=""></td>' +
                                '<td class="px-3 py-2"><input type="number" class="y-coord rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value=""></td>' +
                                '<td class="px-3 py-2"><input type="number" class="count rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="1" min="1"></td>' +
                                '<td class="px-3 py-2"><button type="button" class="delete-row text-red-600 hover:text-red-900">削除</button></td>' +
                                '</tr>';
                        }

                        html += '</tbody>';
                        table.innerHTML = html;
                    } else if (type === 'scroll') {
                        // スクロールヒートマップ用入力テーブル
                        let html = '<thead class="bg-gray-50"><tr>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">スクロール深度 (px)</th>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ユーザー数</th>' +
                            '<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>' +
                            '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';

                        // 初期行 (0px, 300px, 600px, 900px, 1200px, 1500px)
                        const depths = [0, 300, 600, 900, 1200, 1500];
                        const baseUsers = 100;

                        depths.forEach((depth, index) => {
                            const users = Math.round(baseUsers * Math.pow(0.9, index));
                            html += '<tr>' +
                                '<td class="px-3 py-2"><input type="number" class="depth rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="' +
                                depth + '" step="100"></td>' +
                                '<td class="px-3 py-2"><input type="number" class="users rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="' +
                                users + '" min="0"></td>' +
                                '<td class="px-3 py-2"><button type="button" class="delete-row text-red-600 hover:text-red-900">削除</button></td>' +
                                '</tr>';
                        });

                        html += '</tbody>';
                        table.innerHTML = html;
                    }

                    // 削除ボタンイベント設定
                    setupDeleteButtons();
                }

                // 削除ボタンの設定
                function setupDeleteButtons() {
                    document.querySelectorAll('.delete-row').forEach(button => {
                        button.addEventListener('click', function() {
                            const row = this.closest('tr');
                            row.remove();
                        });
                    });
                }

                // 行の追加
                document.getElementById('addRowBtn').addEventListener('click', function() {
                    const table = document.getElementById('manualInputTable');
                    const tbody = table.querySelector('tbody');
                    const type = document.getElementById('type').value;

                    const newRow = document.createElement('tr');

                    if (type === 'click') {
                        newRow.innerHTML =
                            '<td class="px-3 py-2"><input type="number" class="x-coord rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value=""></td>' +
                            '<td class="px-3 py-2"><input type="number" class="y-coord rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value=""></td>' +
                            '<td class="px-3 py-2"><input type="number" class="count rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="1" min="1"></td>' +
                            '<td class="px-3 py-2"><button type="button" class="delete-row text-red-600 hover:text-red-900">削除</button></td>';
                    } else if (type === 'scroll') {
                        // 最後の行の深度 + 300pxを初期値にする
                        let lastDepth = 0;
                        const depthInputs = document.querySelectorAll('.depth');
                        if (depthInputs.length > 0) {
                            lastDepth = parseInt(depthInputs[depthInputs.length - 1].value) || 0;
                            lastDepth += 300;
                        }

                        newRow.innerHTML =
                            '<td class="px-3 py-2"><input type="number" class="depth rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="' +
                            lastDepth + '" step="100"></td>' +
                            '<td class="px-3 py-2"><input type="number" class="users rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-full" value="0" min="0"></td>' +
                            '<td class="px-3 py-2"><button type="button" class="delete-row text-red-600 hover:text-red-900">削除</button></td>';
                    }

                    tbody.appendChild(newRow);

                    // 削除ボタンイベント設定
                    setupDeleteButtons();
                });

                // フォーム送信前にデータを準備
                document.getElementById('heatmapForm').addEventListener('submit', function(e) {
                    const dataInputType = document.querySelector('input[name="dataInputType"]:checked').value;

                    if (dataInputType === 'manual') {
                        const type = document.getElementById('type').value;

                        if (type === 'click') {
                            // クリックヒートマップデータ作成
                            const clicks = [];

                            document.querySelectorAll('tr').forEach(row => {
                                const xInput = row.querySelector('.x-coord');
                                const yInput = row.querySelector('.y-coord');
                                const countInput = row.querySelector('.count');

                                if (xInput && yInput && countInput) {
                                    const x = parseInt(xInput.value.trim());
                                    const y = parseInt(yInput.value.trim());
                                    const count = parseInt(countInput.value.trim());

                                    if (!isNaN(x) && !isNaN(y) && !isNaN(count) && count > 0) {
                                        // countの数だけクリックデータを追加
                                        for (let i = 0; i < count; i++) {
                                            clicks.push({
                                                x,
                                                y
                                            });
                                        }
                                    }
                                }
                            });

                            const data = {
                                clicks: clicks,
                                totalClicks: clicks.length
                            };

                            document.getElementById('data_json').value = JSON.stringify(data);
                        } else if (type === 'scroll') {
                            // スクロールヒートマップデータ作成
                            const scrollDepth = {};
                            let totalUsers = 0;

                            document.querySelectorAll('tr').forEach(row => {
                                const depthInput = row.querySelector('.depth');
                                const usersInput = row.querySelector('.users');

                                if (depthInput && usersInput) {
                                    const depth = parseInt(depthInput.value.trim());
                                    const users = parseInt(usersInput.value.trim());

                                    if (!isNaN(depth) && !isNaN(users)) {
                                        scrollDepth[depth] = users;

                                        if (depth === 0) {
                                            totalUsers = users;
                                        }
                                    }
                                }
                            });

                            const data = {
                                scrollDepth: scrollDepth,
                                totalUsers: totalUsers || Object.values(scrollDepth)[0] || 0
                            };

                            document.getElementById('data_json').value = JSON.stringify(data);
                        }
                    }

                    // データが空の場合は送信をキャンセル
                    if (!document.getElementById('data_json').value) {
                        e.preventDefault();
                        alert('ヒートマップデータが設定されていません。ファイルをアップロードするか、手動でデータを入力してください。');
                    }
                });

                // 種類変更時にフォームをリセット
                document.getElementById('type').addEventListener('change', function() {
                    if (document.getElementById('inputTypeManual').checked) {
                        initManualInputTable();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
