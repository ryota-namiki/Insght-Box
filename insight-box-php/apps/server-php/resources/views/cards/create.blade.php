@extends('layouts.app')

@section('title', 'カード作成 - Insight-Box')

@section('content')
<div class="mx-auto" x-data="cardFormMain()">
  <div class="mb-6">
      <a href="{{ route('cards.index') }}" class="flex items-center text-gray-600 hover:text-gray-900">
      <span class="material-icons mr-1">arrow_back</span>
      一覧に戻る
      </a>
  </div>
  
  <div class="bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">新しいカードを作成</h1>
      
      <!-- モード選択タブ -->
      <div class="mb-6">
      <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
        <button 
            type="button"
            @click="uploadMode = 'file'"
            :class="uploadMode === 'file' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <span class="material-icons text-sm mr-2">upload_file</span>
            ファイルアップロード
        </button>
        <button 
            type="button"
            @click="uploadMode = 'webclip'"
            :class="uploadMode === 'webclip' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <span class="material-icons text-sm mr-2">language</span>
            Webクリップ
        </button>
        <button 
            type="button"
            @click="uploadMode = 'camera'"
            :class="uploadMode === 'camera' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center"
        >
            <span class="material-icons text-sm mr-2">photo_camera</span>
            カメラ撮影
        </button>
          </nav>
      </div>
      </div>

      <!-- ファイルアップロードセクション -->
      <div x-show="uploadMode === 'file'" x-cloak class="mb-8">
      <div 
          class="border-2 border-dashed rounded-lg p-8 text-center"
          :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleFileDrop($event)"
      >
          <template x-if="!uploadedFile">
        <div>
            <span class="material-icons text-6xl text-gray-400">cloud_upload</span>
            <p class="mt-4 text-lg font-medium text-gray-700">ファイルをドラッグ＆ドロップ</p>
            <p class="mt-2 text-sm text-gray-500">または</p>
            <button 
        type="button"
        @click="$refs.fileInput.click()"
        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
        ファイルを選択
            </button>
            <input 
        type="file" 
        x-ref="fileInput"
        @change="handleFileSelect($event)"
        accept="image/*,.pdf"
        class="hidden"
            >
            <p class="mt-4 text-xs text-gray-500">対応形式: JPG, PNG, PDF (最大10MB)</p>
        </div>
          </template>
          
          <template x-if="uploadedFile">
        <div>
            <div class="flex items-center justify-center mb-4">
        <span class="material-icons text-4xl text-green-600">check_circle</span>
            </div>
            <p class="font-medium text-gray-900" x-text="uploadedFile.name"></p>
            <p class="text-sm text-gray-500" x-text="formatFileSize(uploadedFile.size)"></p>
            
            <template x-if="filePreview">
        <img :src="filePreview" class="mt-4 max-w-md mx-auto rounded-lg shadow">
            </template>
            
            <template x-if="isProcessing">
        <div class="mt-4">
            <div class="flex items-center justify-center mb-2">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <p class="text-sm text-gray-600">テキストを抽出しています...</p>
        </div>
            </template>
            
            <template x-if="extractedText && !isProcessing">
        <div class="mt-4 p-4 bg-gray-50 rounded-lg text-left">
            <h4 class="font-medium text-gray-900 mb-2">抽出されたテキスト:</h4>
            <p class="text-sm text-gray-700 whitespace-pre-wrap max-h-40 overflow-y-auto" x-text="extractedText.substring(0, 500) + (extractedText.length > 500 ? '...' : '')"></p>
        </div>
            </template>
            
            <button 
        type="button"
        @click="resetFile()"
        class="mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
            >
        別のファイルを選択
            </button>
        </div>
          </template>
      </div>
      </div>

      <!-- Webクリップセクション -->
      <div x-show="uploadMode === 'webclip'" x-cloak class="mb-8">
      <div class="border-2 border-gray-300 rounded-lg p-6">
          <div class="flex gap-2 mb-4">
        <input 
            type="url" 
            x-model="webclipUrl"
            placeholder="https://example.com/article"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        >
        <button 
            type="button"
            @click="fetchWebContent()"
            :disabled="isProcessing || !webclipUrl"
            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:bg-gray-400 flex items-center"
        >
            <span class="material-icons text-sm mr-1" x-show="!isProcessing">download</span>
            <span x-show="!isProcessing">取得</span>
            <span x-show="isProcessing">処理中...</span>
        </button>
          </div>
          
          <template x-if="webclipContent">
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center mb-3">
        <span class="material-icons text-green-600 mr-2">check_circle</span>
        <span class="font-medium text-green-900">Webページを取得しました</span>
            </div>
            <div class="mt-3 space-y-3">
        <div class="p-3 bg-white rounded">
            <p class="text-xs text-gray-500 mb-1">📄 要約（OpenAI生成）</p>
            <p class="text-sm text-gray-700" x-text="webclipContent"></p>
        </div>
        <p class="text-xs text-gray-500">
            ℹ️ タイトルに自動入力されました。メモは自由に追記できます。
        </p>
            </div>
        </div>
          </template>
      </div>
      </div>

      <!-- カメラ撮影セクション -->
      <div x-show="uploadMode === 'camera'" x-cloak class="mb-8">
      <div class="border-2 border-gray-300 rounded-lg p-6 text-center">
          <div x-show="!cameraImage && !showCamera">
        <span class="material-icons text-6xl text-gray-400">photo_camera</span>
        <p class="mt-4 text-lg font-medium text-gray-700">カメラで撮影する</p>
        <button 
            type="button"
            @click="startCamera()"
            class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center mx-auto"
        >
            <span class="material-icons text-sm mr-2">videocam</span>
            カメラを起動
        </button>
          </div>
          
          <div x-show="showCamera" x-cloak>
        <video x-ref="video" autoplay playsinline class="w-full max-w-2xl mx-auto rounded-lg mb-4 bg-black"></video>
        <div class="flex justify-center gap-3">
            <button 
        type="button"
        @click="captureImage()"
        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center"
            >
        <span class="material-icons mr-2">camera</span>
        撮影
            </button>
            <button 
        type="button"
        @click="stopCamera()"
        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
            >
        キャンセル
            </button>
        </div>
          </div>
          
          <div x-show="cameraImage" x-cloak>
        <img :src="cameraImage" class="w-full max-w-2xl mx-auto rounded-lg mb-4 shadow-lg">
        <button 
            type="button"
            @click="retakePhoto()"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
        >
            撮り直す
        </button>
          </div>
          <canvas x-ref="canvas" style="display:none;"></canvas>
      </div>
      </div>
      
      <form action="{{ route('cards.store') }}" method="POST" @submit="prepareFormData">
      @csrf
      
      <!-- 隠しフィールド -->
      <input type="hidden" name="ocrText" x-model="extractedText">
      <input type="hidden" name="cameraImage" x-model="cameraImage">
      <input type="hidden" name="webclipUrl" x-model="webclipUrl">
      <input type="hidden" name="webclipContent" x-model="webclipContent">
      <input type="hidden" name="documentId" x-model="documentId">
      
      <!-- タイトル -->
      <div class="mb-6">
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
        タイトル <span class="text-red-500">*</span>
          </label>
          <input 
        type="text" 
        id="title" 
        name="title" 
        required
        value="{{ old('title') }}"
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="カードのタイトルを入力"
          >
      </div>
      
      <!-- 会社名 -->
      <div class="mb-6">
          <label for="companyName" class="block text-sm font-medium text-gray-700 mb-2">
        会社名
          </label>
          <input 
        type="text" 
        id="companyName" 
        name="companyName" 
        value="{{ old('companyName') }}"
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="会社名を入力"
          >
      </div>
      
      <!-- メモ -->
      <div class="mb-6">
          <label for="memo" class="block text-sm font-medium text-gray-700 mb-2">
        メモ
          </label>
          <textarea 
        id="memo" 
        name="memo" 
        rows="6"
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="メモを入力"
          >{{ old('memo') }}</textarea>
      </div>
      
      <!-- イベントID -->
      <div class="mb-6">
          <label for="eventId" class="block text-sm font-medium text-gray-700 mb-2">
        イベント <span class="text-red-500">*</span>
          </label>
          <select 
        id="eventId" 
        name="eventId" 
        required
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
        <option value="">選択してください</option>
        @foreach($events as $event)
            <option value="{{ $event['id'] }}" {{ old('eventId') == $event['id'] ? 'selected' : '' }}>
        {{ $event['name'] }}
            </option>
        @endforeach
          </select>
      </div>
      
      <!-- タグ -->
      <div class="mb-6" x-data="{ tagInput: '', tags: [] }">
          <label class="block text-sm font-medium text-gray-700 mb-2">
        タグ
          </label>
          <div class="flex gap-2 mb-2">
        <input 
            type="text" 
            x-model="tagInput"
            @keydown.enter.prevent="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="タグを入力してEnter"
        >
        <button 
            type="button"
            @click="if(tagInput.trim()) { tags.push({id: tagInput.trim(), label: tagInput.trim()}); tagInput = ''; }"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
        >
            追加
        </button>
          </div>
          <div class="flex flex-wrap gap-2">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
        <span x-text="tag.label"></span>
        <button 
            type="button"
            @click="tags.splice(index, 1)"
            class="ml-2 text-indigo-600 hover:text-indigo-800"
        >
            ×
        </button>
        <input type="hidden" :name="'tags[' + index + '][id]'" :value="tag.id">
        <input type="hidden" :name="'tags[' + index + '][label]'" :value="tag.label">
            </span>
        </template>
          </div>
      </div>
      
      <!-- ボタン -->
      <div class="flex justify-end gap-3">
          <a href="{{ route('cards.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
        キャンセル
          </a>
          <button 
        type="submit" 
        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center"
          >
        <span class="material-icons text-sm mr-2">add</span>
        作成
          </button>
      </div>
      </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function cardFormMain() {
  return {
      uploadMode: 'file',
      isDragging: false,
      uploadedFile: null,
      filePreview: null,
      extractedText: '',
      isProcessing: false,
      webclipUrl: '',
      webclipContent: '',
      cameraImage: '',
      showCamera: false,
      stream: null,
      documentId: '',
      
      formatFileSize(bytes) {
      return Math.round(bytes / 1024) + ' KB';
      },
      
      handleFileDrop(event) {
      this.isDragging = false;
      const files = event.dataTransfer.files;
      if (files.length > 0) {
          this.processFile(files[0]);
      }
      },
      
      handleFileSelect(event) {
      const files = event.target.files;
      if (files.length > 0) {
          this.processFile(files[0]);
      }
      },
      
      async processFile(file) {
      this.uploadedFile = file;
      this.isProcessing = true;
      
      // 画像プレビュー
      if (file.type.startsWith('image/')) {
          this.filePreview = URL.createObjectURL(file);
      } else {
          this.filePreview = null;
      }
      
      // OCR処理（簡易版：実際のAPIは後で実装）
      try {
          const formData = new FormData();
          formData.append('file', file);
          formData.append('lang', 'jpn+eng');
          
          const response = await fetch('{{ url("/api/v1/documents") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
          });
          
          if (response.ok) {
        const data = await response.json();
        this.documentId = data.document_id;
        
        // ジョブ完了を待つ（簡易版）
        await this.waitForJob(data.job_id);
          } else {
        this.extractedText = 'ファイルをアップロードしました（OCR処理は後で実装予定）';
          }
      } catch (error) {
          console.error('ファイル処理エラー:', error);
          this.extractedText = 'ファイルをアップロードしました';
      } finally {
          this.isProcessing = false;
      }
      },
      
      async waitForJob(jobId) {
      // 簡易版：実際のジョブ監視は後で実装
      setTimeout(async () => {
          try {
        const response = await fetch(`{{ url("/api/v1/documents") }}/${this.documentId}/text`);
        console.log('OCRテキスト取得レスポンス:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('OCRテキストデータ:', data);
            console.log('data.text:', data.text);
            console.log('data.textの型:', typeof data.text);
            console.log('data.textの長さ:', data.text ? data.text.length : 'null/undefined');
            
            if (data.text && data.text.trim()) {
        this.extractedText = data.text;
            } else {
        this.extractedText = 'OCR処理が完了しましたが、テキストが抽出できませんでした。\n\n【デバッグ情報】\n' + 
                 'レスポンス: ' + JSON.stringify(data, null, 2) + '\n\n' +
                 '※サーバーにTesseract OCRがインストールされていない可能性があります。';
            }
        } else {
            const errorText = await response.text();
            console.error('テキスト取得失敗:', errorText);
            this.extractedText = `エラー: ${response.status} - テキスト取得に失敗しました`;
        }
          } catch (error) {
        console.error('テキスト取得エラー:', error);
        this.extractedText = `エラー: ${error.message}`;
          }
      }, 2000);
      },
      
      resetFile() {
      this.uploadedFile = null;
      this.filePreview = null;
      this.extractedText = '';
      this.documentId = '';
      },
      
      async fetchWebContent() {
      if (!this.webclipUrl) return;
      
      this.isProcessing = true;
      try {
          const response = await fetch('{{ url("/api/webclip/fetch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            url: this.webclipUrl
        })
          });
          
          const data = await response.json();
          
          if (data.success) {
        this.webclipContent = data.summary || data.description || data.content;
        
        // タイトルフィールドに自動入力
        const titleInput = document.getElementById('title');
        if (titleInput && !titleInput.value && data.title) {
            titleInput.value = data.title;
        }
        
        alert('Webページの内容を取得しました！タイトルに自動入力されています。');
          } else {
        throw new Error(data.error || 'Webページの取得に失敗しました');
          }
      } catch (error) {
          console.error('Webクリップエラー:', error);
          alert('Webページの取得に失敗しました: ' + error.message);
      } finally {
          this.isProcessing = false;
      }
      },
      
      async startCamera() {
      try {
          this.stream = await navigator.mediaDevices.getUserMedia({ 
        video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
          });
          this.showCamera = true;
          this.$nextTick(() => {
        this.$refs.video.srcObject = this.stream;
          });
      } catch (error) {
          alert('カメラへのアクセスに失敗しました: ' + error.message);
      }
      },
      
      captureImage() {
      const video = this.$refs.video;
      const canvas = this.$refs.canvas;
      const context = canvas.getContext('2d');
      
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      context.drawImage(video, 0, 0);
      
      this.cameraImage = canvas.toDataURL('image/jpeg', 0.8);
      this.stopCamera();
      },
      
      stopCamera() {
      if (this.stream) {
          this.stream.getTracks().forEach(track => track.stop());
          this.stream = null;
      }
      this.showCamera = false;
      },
      
      retakePhoto() {
      this.cameraImage = '';
      this.startCamera();
      },
      
      prepareFormData(event) {
      // フォーム送信前の処理
      console.log('フォーム送信:', {
          extractedText: this.extractedText,
          cameraImage: this.cameraImage ? 'あり' : 'なし',
          webclipUrl: this.webclipUrl
      });
      }
  };
}
</script>
@endsection
