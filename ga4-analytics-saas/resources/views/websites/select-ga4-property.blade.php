<x-app-layout>
    <x-slot:header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Google Analytics プロパティの選択') }}
        </h2>
    </x-slot:header>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (empty($properties))
                        <div class="text-center py-4">
                            <p class="text-gray-600">利用可能なGA4プロパティが見つかりませんでした。</p>
                            <p class="text-sm text-gray-500 mt-2">Google Analyticsで対象のウェブサイトのプロパティを作成してください。</p>
                            <div class="mt-4">
                                <a href="{{ route('websites.show', $website) }}"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    戻る
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="mb-4">
                            <p class="text-gray-600">連携するGA4プロパティを選択してください。</p>
                        </div>
                        <div class="grid gap-6 mt-6">
                            @foreach ($properties as $property)
                                <div class="border rounded-lg p-6 hover:bg-gray-50">
                                    <form action="{{ route('google.analytics.select-property', $website) }}"
                                        method="POST">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $property['id'] }}">
                                        <input type="hidden" name="access_token" value="{{ $accessToken }}">
                                        <input type="hidden" name="refresh_token" value="{{ $refreshToken }}">

                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    {{ $property['displayName'] }}
                                                </h3>
                                                <div class="mt-2 text-sm text-gray-500">
                                                    <p>プロパティID: {{ $property['id'] }}</p>
                                                    @if (isset($property['websiteUrl']))
                                                        <p class="mt-1">ウェブサイト: {{ $property['websiteUrl'] }}</p>
                                                    @endif
                                                    @if (isset($property['createTime']))
                                                        <p class="mt-1">作成日:
                                                            {{ \Carbon\Carbon::parse($property['createTime'])->format('Y/m/d') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                                選択
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
