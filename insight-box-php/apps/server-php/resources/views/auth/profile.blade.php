@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">プロフィール</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- 名前 -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        名前 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                        value="{{ old('name', $user->name) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <!-- メールアドレス（変更不可） -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        メールアドレス
                    </label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500">
                    <p class="mt-1 text-xs text-gray-500">メールアドレスは変更できません</p>
                </div>
                
                <!-- 部署 -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                        部署
                    </label>
                    <input type="text" id="department" name="department"
                        value="{{ old('department', $user->profile->department ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <!-- 興味・関心（カンマ区切り） -->
                <div>
                    <label for="interests" class="block text-sm font-medium text-gray-700 mb-2">
                        興味・関心
                    </label>
                    <input type="text" id="interests" name="interests"
                        value="{{ old('interests', $user->profile && $user->profile->interests ? implode(', ', $user->profile->interests) : '') }}"
                        placeholder="例: デザイン, マーケティング, データ分析"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">カンマ区切りで入力してください</p>
                </div>
                
                <!-- 更新ボタン -->
                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        更新
                    </button>
                </div>
            </form>
            
            <!-- アカウント情報 -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h2 class="text-lg font-semibold mb-4">アカウント情報</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    <p><span class="font-medium">登録日:</span> {{ $user->created_at->format('Y年m月d日') }}</p>
                    <p><span class="font-medium">カード数:</span> {{ $user->cards()->count() }}件</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

