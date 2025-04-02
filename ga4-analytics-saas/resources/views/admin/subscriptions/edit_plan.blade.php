<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('プラン編集') }} - {{ $plan->display_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('admin.subscriptions.plans.update', $plan->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label for="name" :value="__('プラン名（システム内部名）')" />
                            <x-text-input id="name" class="block mt-1 w-full bg-gray-100" type="text" name="name" :value="old('name', $plan->name)" disabled readonly />
                            <p class="mt-1 text-sm text-gray-600">内部名は変更できません</p>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="display_name" :value="__('表示名')" />
                            <x-text-input id="display_name" class="block mt-1 w-full" type="text" name="display_name" :value="old('display_name', $plan->display_name)" required />
                            <p class="mt-1 text-sm text-gray-600">ユーザーに表示される名前（例: スタータープラン, プロプラン）</p>
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="description" :value="__('説明')" />
                            <textarea id="description" name="description" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" rows="3">{{ old('description', $plan->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="price" :value="__('価格')" />
                                <x-text-input id="price" class="block mt-1 w-full" type="number" name="price" :value="old('price', $plan->price)" required min="0" step="0.01" />
                                <x-input-error :messages="$errors->get('price')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="billing_period" :value="__('請求周期')" />
                                <select id="billing_period" name="billing_period" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="monthly" {{ old('billing_period', $plan->billing_period) === 'monthly' ? 'selected' : '' }}>月額</option>
                                    <option value="yearly" {{ old('billing_period', $plan->billing_period) === 'yearly' ? 'selected' : '' }}>年額</option>
                                </select>
                                <x-input-error :messages="$errors->get('billing_period')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="stripe_plan_id" :value="__('Stripe プランID')" />
                                <x-text-input id="stripe_plan_id" class="block mt-1 w-full" type="text" name="stripe_plan_id" :value="old('stripe_plan_id', $plan->stripe_plan_id)" />
                                    <p class="mt-1 text-sm text-gray-600">Stripe上で作成したプランのID</p>
                                    <x-input-error :messages="$errors->get('stripe_plan_id')" class="mt-2" />
                                </div>

                                <div class="mb-4">
                                    <x-input-label for="website_limit" :value="__('Webサイト登録上限')" />
                                    <x-text-input id="website_limit" class="block mt-1 w-full" type="number" name="website_limit" :value="old('website_limit', $plan->website_limit)" required min="1" />
                                    <x-input-error :messages="$errors->get('website_limit')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="is_active" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">有効</label>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">このプランを有効にします</p>
                            </div>

                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="is_featured" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="is_featured" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}>
                                    <label for="is_featured" class="ml-2 block text-sm text-gray-900">おすすめプラン</label>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">このプランをおすすめとして表示します</p>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <a href="{{ route('admin.subscriptions.plans') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                                    キャンセル
                                </a>
                                <x-primary-button class="ml-4">
                                    {{ __('プランを更新') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
