<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('新規レポート作成') }} - {{ $website->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('reports.store', $website->id) }}">
                        @csrf

                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">レポートタイプを選択</h3>
                            <p class="text-sm text-gray-600 mb-4">ビジネスニーズに合わせて最適なレポートタイプを選択してください</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="relative p-4 bg-white border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500': form.report_type === 'executive', 'border-gray-200': form.report_type !== 'executive' }">
                                    <input type="radio" name="report_type" value="executive" class="absolute opacity-0" required x-model="form.report_type">
                                    <div>
                                        <div class="font-medium mb-2">経営者向け</div>
                                        <p class="text-sm text-gray-600">ビジネス指標とROIに焦点を当てた包括的な分析レポート</p>
                                    </div>
                                </label>

                                <label class="relative p-4 bg-white border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500': form.report_type === 'technical', 'border-gray-200': form.report_type !== 'technical' }">
                                    <input type="radio" name="report_type" value="technical" class="absolute opacity-0" required x-model="form.report_type">
                                    <div>
                                        <div class="font-medium mb-2">技術者向け</div>
                                        <p class="text-sm text-gray-600">サイトパフォーマンスや技術的な問題に焦点を当てた詳細レポート</p>
                                    </div>
                                </label>

                                <label class="relative p-4 bg-white border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500': form.report_type === 'content', 'border-gray-200': form.report_type !== 'content' }">
                                    <input type="radio" name="report_type" value="content" class="absolute opacity-0" required x-model="form.report_type">
                                    <div>
                                        <div class="font-medium mb-2">コンテンツ向け</div>
                                        <p class="text-sm text-gray-600">コンテンツのパフォーマンスと改善提案を含む分析レポート</p>
                                    </div>
                                </label>
                            </div>

                            <x-input-error :messages="$errors->get('report_type')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">分析期間を選択</h3>
                            <p class="text-sm text-gray-600 mb-4">データを分析する期間を選択してください</p>

                            <div class="relative">
                                <input type="text" name="date_range" class="block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="期間を選択..." required value="{{ now()->subDays(30)->format('Y/m/d') }} - {{ now()->format('Y/m/d') }}">
                                <x-input-error :messages="$errors->get('date_range')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('websites.show', $website->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                                キャンセル
                            </a>
                            <x-primary-button>
                                {{ __('レポートを生成') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.jsとDateRangePickerの初期化 -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('form', () => ({
                report_type: 'executive',
            }))
        });

        // DateRangePicker初期化
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('input[name="date_range"]', {
                mode: 'range',
                dateFormat: 'Y/m/d',
                defaultDate: [
                    new Date().setDate(new Date().getDate() - 30),
                    new Date()
                ],
                locale: {
                    rangeSeparator: ' - '
                }
            });
        });
    </script>
</x-app-layout>
