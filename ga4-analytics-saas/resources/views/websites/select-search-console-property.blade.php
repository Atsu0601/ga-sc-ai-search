<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Search Consoleプロパティの選択
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ $website->name }} のSearch Consoleプロパティを選択してください
                    </h3>

                    @if (empty($properties))
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        利用可能なSearch Consoleプロパティが見つかりませんでした。
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('google.searchconsole.select-property', $website->id) }}">
                            @csrf
                            <input type="hidden" name="access_token" value="{{ $accessToken }}">
                            <input type="hidden" name="refresh_token" value="{{ $refreshToken }}">

                            <div class="mb-4">
                                <label for="site_url" class="block text-sm font-medium text-gray-700">
                                    プロパティの選択
                                </label>
                                <select name="site_url" id="site_url"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    @foreach ($properties as $property)
                                        <option value="{{ $property['siteUrl'] }}"
                                            {{ $website->url == $property['siteUrl'] ? 'selected' : '' }}>
                                            {{ $property['siteUrl'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                    選択して連携
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
