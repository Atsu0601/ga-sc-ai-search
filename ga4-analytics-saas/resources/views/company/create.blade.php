<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('会社情報の登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('company.store') }}">
                        @csrf

                        <div class="mb-4">
                            <x-input-label for="name" :value="__('会社名')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="billing_email" :value="__('請求先メールアドレス')" />
                            <x-text-input id="billing_email" class="block mt-1 w-full" type="email" name="billing_email" :value="old('billing_email')" />
                            <x-input-error :messages="$errors->get('billing_email')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="contact_person" :value="__('担当者名')" />
                            <x-text-input id="contact_person" class="block mt-1 w-full" type="text" name="contact_person" :value="old('contact_person')" />
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="phone" :value="__('電話番号')" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="address" :value="__('住所')" />
                            <x-text-input id="address" class="block mt-1 w-full" type="text" name="address" :value="old('address')" />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="city" :value="__('市区町村')" />
                                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" />
                                <x-input-error :messages="$errors->get('city')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="state" :value="__('都道府県')" />
                                <x-text-input id="state" class="block mt-1 w-full" type="text" name="state" :value="old('state')" />
                                <x-input-error :messages="$errors->get('state')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="zip_code" :value="__('郵便番号')" />
                                <x-text-input id="zip_code" class="block mt-1 w-full" type="text" name="zip_code" :value="old('zip_code')" />
                                <x-input-error :messages="$errors->get('zip_code')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="country" :value="__('国')" />
                                <x-text-input id="country" class="block mt-1 w-full" type="text" name="country" :value="old('country')" value="日本" />
                                <x-input-error :messages="$errors->get('country')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('登録する') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
