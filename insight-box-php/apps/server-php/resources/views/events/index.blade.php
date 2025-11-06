@extends('layouts.app')

@section('content')
<div class="mb-8 flex justify-between items-center">
  <h1 class="text-[28px] font-semibold text-text-main leading-tight">イベント一覧</h1>
  <a href="{{ route('events.create') }}" class="flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
    <i data-lucide="plus" class="w-4 h-4"></i>
    イベントを追加
  </a>
</div>

@if(empty($events))
  <div class="bg-surface rounded-card shadow-card p-12 text-center">
    <i data-lucide="calendar" class="w-16 h-16 text-divider mx-auto mb-4"></i>
    <h3 class="mt-4 text-lg font-medium text-text-main">イベントがありません</h3>
    <p class="mt-2 text-text-subtle leading-relaxed">新しいイベントを作成してみましょう</p>
    <a href="{{ route('events.create') }}" class="mt-4 inline-flex items-center gap-2 h-12 px-4 bg-primary text-white rounded-btn text-sm font-medium hover:brightness-95 transition-fast btn-primary">
      <i data-lucide="plus" class="w-4 h-4"></i>
      イベントを作成
    </a>
  </div>
@else
  <div class="bg-surface rounded-card shadow-card overflow-hidden">
    <table class="min-w-full divide-y divide-divider">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-[13px] font-medium text-text-subtle uppercase tracking-wider">
            イベント名
          </th>
          <th class="px-6 py-3 text-left text-[13px] font-medium text-text-subtle uppercase tracking-wider">
            期間
          </th>
          <th class="px-6 py-3 text-left text-[13px] font-medium text-text-subtle uppercase tracking-wider">
            場所
          </th>
          <th class="px-6 py-3 text-right text-[13px] font-medium text-text-subtle uppercase tracking-wider">
            操作
          </th>
        </tr>
      </thead>
      <tbody class="bg-surface divide-y divide-divider">
        @foreach($events as $event)
        <tr class="hover:bg-gray-100 transition-all duration-200">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-text-main">{{ $event['name'] }}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-text-subtle">
              {{ \Carbon\Carbon::parse($event['start_date'] ?? $event['startDate'])->format('Y/m/d') }} 〜 
              {{ \Carbon\Carbon::parse($event['end_date'] ?? $event['endDate'])->format('Y/m/d') }}
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-text-subtle">{{ $event['location'] ?? '未設定' }}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <a href="{{ route('events.edit', $event['id']) }}" class="text-primary hover:bg-white rounded inline-flex items-center gap-1 mr-3 transition-all px-2 py-1">
              <i data-lucide="pencil" class="w-4 h-4"></i>
              編集
            </a>
            <form action="{{ route('events.destroy', $event['id']) }}" method="POST" class="inline" onsubmit="return confirm('本当に削除しますか？')">
              @csrf
              @method('DELETE')
              <button type="submit" class="text-error hover:bg-white rounded inline-flex items-center gap-1 transition-all px-2 py-1">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
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

<script>
  // Lucide Icons初期化
  lucide.createIcons();
</script>
@endsection
