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
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                会社名 <span
                                    class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                            </label>
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label for="billing_email" class="block text-sm font-medium text-gray-700">
                                請求先メールアドレス <span
                                    class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                            </label>
                            <x-text-input id="billing_email" class="block mt-1 w-full" type="email"
                                name="billing_email" :value="old('billing_email')" required />
                            <x-input-error :messages="$errors->get('billing_email')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label for="contact_person" class="block text-sm font-medium text-gray-700">
                                担当者名 <span
                                    class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                            </label>
                            <x-text-input id="contact_person" class="block mt-1 w-full" type="text"
                                name="contact_person" :value="old('contact_person')" required />
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                電話番号 <span
                                    class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                            </label>
                            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone"
                                :value="old('phone')" required />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                住所 <span
                                    class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                            </label>
                            <x-text-input id="address" class="block mt-1 w-full" type="text" name="address"
                                :value="old('address')" required />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="city" class="block text-sm font-medium text-gray-700">
                                    市区町村 <span
                                        class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                                </label>
                                <x-text-input id="city" class="block mt-1 w-full" type="text" name="city"
                                    :value="old('city')" required />
                                <x-input-error :messages="$errors->get('city')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <label for="state" class="block text-sm font-medium text-gray-700">
                                    都道府県 <span
                                        class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                                </label>
                                <x-text-input id="state" class="block mt-1 w-full" type="text" name="state"
                                    :value="old('state')" required />
                                <x-input-error :messages="$errors->get('state')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="mb-4">
                                <label for="postal_code" class="block text-sm font-medium text-gray-700">
                                    郵便番号 <span
                                        class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                                </label>
                                <x-text-input id="postal_code" class="block mt-1 w-full" type="text"
                                    name="postal_code" :value="old('postal_code')" required />
                                <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <label for="country" class="block text-sm font-medium text-gray-700">
                                    国 <span
                                        class="text-red-500 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold ml-2">必須</span>
                                </label>
                                <x-text-input id="country" class="block mt-1 w-full" type="text" name="country"
                                    :value="old('country')" value="日本" required />
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');
                const requiredFields = form.querySelectorAll('[required]');

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    let hasError = false;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            hasError = true;
                            field.classList.add('border-red-500');

                            // エラーメッセージを表示
                            const errorElement = field.nextElementSibling;
                            if (errorElement && errorElement.classList.contains('input-error')) {
                                errorElement.textContent = 'この項目は必須です';
                            }
                        } else {
                            field.classList.remove('border-red-500');
                            const errorElement = field.nextElementSibling;
                            if (errorElement && errorElement.classList.contains('input-error')) {
                                errorElement.textContent = '';
                            }
                        }
                    });

                    if (!hasError) {
                        form.submit();
                    } else {
                        alert('必須項目を入力してください。');
                    }
                });

                // 入力時にエラー表示をクリア
                requiredFields.forEach(field => {
                    field.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.classList.remove('border-red-500');
                            const errorElement = this.nextElementSibling;
                            if (errorElement && errorElement.classList.contains('input-error')) {
                                errorElement.textContent = '';
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
