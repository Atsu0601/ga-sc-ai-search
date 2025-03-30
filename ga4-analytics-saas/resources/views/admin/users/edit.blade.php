<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ユーザー編集') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label for="name" :value="__('ユーザー名')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="email" :value="__('メールアドレス')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="role" :value="__('ユーザー権限')" />
                            <select id="role" name="role" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>一般ユーザー</option>
                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>管理者</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="plan_name" :value="__('プラン')" />
                            <select id="plan_name" name="plan_name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="trial" {{ $user->plan_name === 'trial' ? 'selected' : '' }}>トライアル</option>
                                <option value="starter" {{ $user->plan_name === 'starter' ? 'selected' : '' }}>スターター</option>
                                <option value="pro" {{ $user->plan_name === 'pro' ? 'selected' : '' }}>プロ</option>
                                <option value="agency" {{ $user->plan_name === 'agency' ? 'selected' : '' }}>エージェンシー</option>
                            </select>
                            <x-input-error :messages="$errors->get('plan_name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="website_limit" :value="__('Webサイト登録上限')" />
                            <x-text-input id="website_limit" class="block mt-1 w-full" type="number" name="website_limit" :value="old('website_limit', $user->website_limit)" required min="1" />
                            <x-input-error :messages="$errors->get('website_limit')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="trial_ends_at" :value="__('トライアル期限')" />
                            <x-text-input id="trial_ends_at" class="block mt-1 w-full" type="date" name="trial_ends_at" :value="old('trial_ends_at', $user->trial_ends_at ? \Carbon\Carbon::parse($user->trial_ends_at)->format('Y-m-d') : '')" />
                            <x-input-error :messages="$errors->get('trial_ends_at')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                                キャンセル
                            </a>
                            <x-primary-button class="ml-4">
                                {{ __('更新する') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
