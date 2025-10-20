@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full space-y-8">
      <div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          アカウント作成
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
          または
          <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
        ログイン
          </a>
      </p>
      </div>
      
      <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
      @if ($errors->any())
          <div class="mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
        <li class="text-sm">{{ $error }}</li>
            @endforeach
        </ul>
          </div>
      @endif
      
      <form method="POST" action="{{ route('register') }}" class="space-y-6">
          @csrf
          
          <!-- 名前 -->
          <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            名前 <span class="text-red-500">*</span>
        </label>
        <input type="text" id="name" name="name" required autofocus
            value="{{ old('name') }}"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          
          <!-- メールアドレス -->
          <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
            メールアドレス <span class="text-red-500">*</span>
        </label>
        <input type="email" id="email" name="email" required
            value="{{ old('email') }}"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          
          <!-- パスワード -->
          <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
            パスワード <span class="text-red-500">*</span>
        </label>
        <input type="password" id="password" name="password" required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
        <p class="mt-1 text-xs text-gray-500">8文字以上で入力してください</p>
          </div>
          
          <!-- パスワード確認 -->
          <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
            パスワード確認 <span class="text-red-500">*</span>
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation" required
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          
          <!-- 登録ボタン -->
          <div>
        <button type="submit"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            登録
        </button>
          </div>
      </form>
      </div>
  </div>
</div>
@endsection

