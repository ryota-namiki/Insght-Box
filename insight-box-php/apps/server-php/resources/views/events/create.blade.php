@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">イベントを作成</h1>
</div>

<div class="bg-white rounded-lg shadow p-8">
    <form action="{{ route('events.store') }}" method="POST">
        @csrf
        
        <!-- イベント名 -->
        <div class="mb-6">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                イベント名 <span class="text-red-500">*</span>
            </label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                value="{{ old('name') }}"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror" 
                placeholder="例: 2025年 春季展示会"
                required
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- 説明 -->
        <div class="mb-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                説明
            </label>
            <textarea 
                id="description" 
                name="description" 
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror"
                placeholder="イベントの詳細や目的を入力..."
            >{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- 開始日と終了日 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- 開始日 -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    開始日 <span class="text-red-500">*</span>
                </label>
                <input 
                    type="date" 
                    id="start_date" 
                    name="start_date" 
                    value="{{ old('start_date', date('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('start_date') border-red-500 @enderror"
                    required
                >
                @error('start_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 終了日 -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    終了日 <span class="text-red-500">*</span>
                </label>
                <input 
                    type="date" 
                    id="end_date" 
                    name="end_date" 
                    value="{{ old('end_date', date('Y-m-d', strtotime('+7 days'))) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('end_date') border-red-500 @enderror"
                    required
                >
                @error('end_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- ボタン -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('events.index') }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                キャンセル
            </a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">
                <span class="material-icons text-sm align-middle mr-1">save</span>
                作成
            </button>
        </div>
    </form>
</div>
@endsection

