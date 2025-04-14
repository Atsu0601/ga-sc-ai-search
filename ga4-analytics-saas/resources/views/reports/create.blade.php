<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('新規レポート作成') }} - {{ $website->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-lg font-semibold mb-4">レポート作成</h2>

                    <form method="POST" action="{{ route('reports.store', ['website' => $website->id]) }}"
                        class="space-y-6" id="reportForm">
                        @csrf
                        <input type="hidden" name="website_id" value="{{ $website->id }}">

                        <!-- レポートタイプ選択 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                レポートタイプを選択
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach ([
        'business' => [
            'label' => '経営者向け',
            'description' => 'ビジネス指標とROIに焦点を当てた包括的な分析レポート',
        ],
        'technical' => [
            'label' => '技術者向け',
            'description' => 'サイトパフォーマンスや技術的な問題に焦点を当てた詳細レポート',
        ],
        'content' => [
            'label' => 'コンテンツ向け',
            'description' => 'コンテンツのパフォーマンスと改善提案を含む分析レポート',
        ],
    ] as $type => $data)
                                    <div class="relative">
                                        <input type="radio" name="report_type" id="{{ $type }}"
                                            value="{{ $type }}" class="hidden peer" required>
                                        <label for="{{ $type }}"
                                            class="block p-4 border rounded-lg cursor-pointer
                                                      transition-all duration-200 ease-in-out
                                                      peer-checked:bg-blue-50 peer-checked:border-blue-500
                                                      hover:bg-gray-50">
                                            <div class="font-medium">{{ $data['label'] }}</div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $data['description'] }}</p>
                                        </label>
                                        <div class="absolute hidden peer-checked:block top-2 right-2 text-blue-500">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 分析期間選択 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                分析期間を選択
                            </label>
                            <p class="text-sm text-gray-600 mb-4">データを分析する期間を選択してください</p>
                            <div>
                                <input type="text" name="date_range" id="date_range"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value="{{ now()->subDays(30)->format('Y/m/d') }} - {{ now()->format('Y/m/d') }}"
                                    required>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" id="submitButton"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                                レポート生成
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // flatpickrの初期化
            flatpickr('#date_range', {
                mode: 'range',
                dateFormat: 'Y/m/d',
                defaultDate: [
                    new Date().setDate(new Date().getDate() - 30),
                    new Date()
                ],
                locale: {
                    ...flatpickr.l10ns.ja,
                    rangeSeparator: ' - '
                }
            });

            // フォームのsubmitイベントを監視
            const form = document.getElementById('reportForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // 一旦デフォルトの送信を防ぐ

                // バリデーション
                const reportType = document.querySelector('input[name="report_type"]:checked');
                const dateRange = document.getElementById('date_range').value;

                if (!reportType) {
                    alert('レポートタイプを選択してください。');
                    return;
                }

                if (!dateRange) {
                    alert('分析期間を選択してください。');
                    return;
                }

                // バリデーションが通ったらフォームを送信
                console.log('フォーム送信:', {
                    reportType: reportType.value,
                    dateRange: dateRange,
                    websiteId: {{ $website->id }}
                });

                form.submit(); // フォームを送信
            });
        });
    </script>
</x-app-layout>
