@extends('layouts.app')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">イベント一覧</h1>
    <a href="{{ route('events.create') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">
        <span class="material-icons mr-2">add</span>
        イベントを追加
    </a>
</div>

@if(empty($events))
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <span class="material-icons text-6xl text-gray-300">event</span>
        <h3 class="mt-4 text-lg font-medium text-gray-900">イベントがありません</h3>
        <p class="mt-2 text-gray-500">新しいイベントを作成してみましょう</p>
        <a href="{{ route('events.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            <span class="material-icons text-sm mr-2">add</span>
            イベントを作成
        </a>
    </div>
@else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        イベント名
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        場所
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        開始日
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        終了日
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        説明
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        操作
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($events as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $event['name'] }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                @if(isset($event['location']) && $event['location'])
                                    <span class="inline-flex items-center">
                                        <span class="material-icons text-xs mr-1">place</span>
                                        {{ $event['location'] }}
                                    </span>
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ date('Y年m月d日', strtotime($event['start_date'])) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ date('Y年m月d日', strtotime($event['end_date'])) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                {{ $event['description'] ? Str::limit($event['description'], 50) : '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('events.edit', $event['id']) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                <span class="material-icons text-sm align-middle">edit</span>
                                編集
                            </a>
                            <form action="{{ route('events.destroy', $event['id']) }}" method="POST" class="inline" 
                                  onsubmit="return confirm('このイベントを削除してもよろしいですか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <span class="material-icons text-sm align-middle">delete</span>
                                    削除
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

