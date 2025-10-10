@extends('layouts.app')

@section('title', $card['summary']['title'] . ' - Insight-Box')

@section('content')
<div class="mx-auto">
    <!-- ヘッダー -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('cards.index') }}" class="flex items-center text-gray-600 hover:text-gray-900">
            <span class="material-icons mr-1">arrow_back</span>
            一覧に戻る
        </a>
        <div class="flex items-center space-x-2">
            <a href="{{ route('cards.edit', $card['id']) }}" class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                <span class="material-icons text-sm mr-1">edit</span>
                編集
            </a>
            <button 
                onclick="if(confirm('このカードを削除しますか？')) { document.getElementById('delete-form').submit(); }"
                class="flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            >
                <span class="material-icons text-sm mr-1">delete</span>
                削除
            </button>
            <form id="delete-form" action="{{ route('cards.destroy', $card['id']) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
    
    <!-- カード詳細 -->
    <div class="bg-white rounded-lg shadow-lg">
        <!-- ヘッダー部分 -->
        <div class="px-8 py-6 border-b border-gray-200">
            <div class="flex items-start justify-between gap-4 mb-4">
                <h1 class="text-3xl font-bold text-gray-900 flex-1">
                    {{ $card['summary']['title'] }}
                </h1>
                <span class="px-3 py-1 text-sm rounded-full whitespace-nowrap {{ $card['summary']['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $card['summary']['status'] === 'published' ? '公開' : '下書き' }}
                </span>
            </div>
            
            @if($card['summary']['company'])
                <p class="text-lg text-gray-700 mb-4 flex items-center">
                    <span class="material-icons mr-2">business</span>
                    {{ $card['summary']['company'] }}
                </p>
            @endif
            
            @if(!empty($card['summary']['tags']))
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($card['summary']['tags'] as $tag)
                        <span class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-full">
                            {{ $tag['label'] ?? $tag['id'] }}
                        </span>
                    @endforeach
                </div>
            @endif
            
            <div class="flex items-center space-x-6 text-sm text-gray-500">
                <span class="flex items-center">
                    <span class="material-icons text-sm mr-1">schedule</span>
                    作成: {{ \Carbon\Carbon::parse($card['summary']['createdAt'])->format('Y年m月d日 H:i') }}
                </span>
                <span class="flex items-center">
                    <span class="material-icons text-sm mr-1">update</span>
                    更新: {{ \Carbon\Carbon::parse($card['summary']['updatedAt'])->format('Y年m月d日 H:i') }}
                </span>
            </div>
        </div>
        
        <!-- Webクリップ -->
        @if($card['detail']['webclipUrl'])
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                    <span class="material-icons mr-2">language</span>
                    Webクリップ
                </h2>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-5">
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 mb-2">🔗 参照URL</p>
                        <a href="{{ $card['detail']['webclipUrl'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 hover:underline break-all text-sm">
                            {{ $card['detail']['webclipUrl'] }}
                        </a>
                    </div>
                    
                    @if($card['detail']['webclipSummary'])
                        <div class="mt-4 pt-4 border-t border-blue-200">
                            <p class="text-xs text-gray-500 mb-2">📝 AI生成要約（300文字）</p>
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <p class="text-gray-800 leading-relaxed">{{ $card['detail']['webclipSummary'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- アップロードファイルと抽出テキスト（横並び） -->
        @if($card['detail']['documentId'] || $card['detail']['text'])
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="material-icons mr-2">description</span>
                    アップロードファイル
                </h2>
                
                <!-- PC: 横並び / SP: 縦並び -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <!-- 左: ファイルプレビュー（PC） / 上: プレビュー（SP） -->
                    @if($card['detail']['documentId'])
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-icons text-2xl text-indigo-600">insert_drive_file</span>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">ドキュメントID</p>
                                    <p class="text-xs text-gray-500">{{ substr($card['detail']['documentId'], 0, 20) }}...</p>
                                </div>
                            </div>
                            
                            <!-- 画像プレビュー（APIから取得） -->
                            <div class="mt-3">
                                <img 
                                    src="{{ url('/api/v1/documents/' . $card['detail']['documentId'] . '/image') }}" 
                                    alt="アップロードファイル"
                                    class="w-full h-auto rounded-lg shadow-md"
                                    id="preview-image"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                >
                                <div style="display:none;" class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-xs text-blue-800">
                                        <span class="material-icons text-xs align-middle">info</span>
                                        画像プレビューは利用できません
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- 右: 抽出テキスト（PC） / 下: テキスト（SP） -->
                    @if($card['detail']['text'])
                        <div class="bg-gray-50 rounded-lg p-4 flex flex-col">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <span class="material-icons text-sm mr-2">text_snippet</span>
                                抽出されたテキスト
                            </h3>
                            <div class="bg-white rounded p-3 overflow-y-auto flex-1" id="text-container">
                                <p class="text-gray-700 whitespace-pre-wrap text-sm leading-relaxed m-0">{{ $card['detail']['text'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- カメラ画像 -->
        @if($card['detail']['cameraImage'])
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                    <span class="material-icons mr-2">photo_camera</span>
                    カメラ撮影画像
                </h2>
                <img 
                    src="{{ $card['detail']['cameraImage'] }}" 
                    alt="撮影画像"
                    class="rounded-lg shadow-md"
                    style="max-width: 50%; height: auto;"
                >
            </div>
        @endif
        
        <!-- メモ -->
        @if($card['detail']['memo'])
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                    <span class="material-icons mr-2">edit_note</span>
                    メモ
                </h2>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $card['detail']['memo'] }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
// 画像の高さに合わせてテキストエリアの高さを調整
function adjustTextHeight() {
    const previewImage = document.getElementById('preview-image');
    const textContainer = document.getElementById('text-container');
    
    if (previewImage && textContainer && previewImage.style.display !== 'none') {
        // 画像の読み込みが完了してから高さを取得
        if (previewImage.complete) {
            const imageHeight = previewImage.offsetHeight;
            if (imageHeight > 0) {
                textContainer.style.maxHeight = imageHeight + 'px';
            }
        } else {
            previewImage.addEventListener('load', function() {
                const imageHeight = previewImage.offsetHeight;
                if (imageHeight > 0) {
                    textContainer.style.maxHeight = imageHeight + 'px';
                }
            });
        }
    }
}

// ページ読み込み時とウィンドウリサイズ時に調整
window.addEventListener('load', adjustTextHeight);
window.addEventListener('resize', adjustTextHeight);
</script>
@endsection

