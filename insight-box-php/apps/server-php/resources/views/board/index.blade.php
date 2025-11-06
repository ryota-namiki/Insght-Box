@extends('layouts.app')

@section('title', 'ボード - Insight-Box')

@section('styles')
<style>
  .two-column-layout {
      display: flex;
      gap: 1rem;
      height: calc(100vh - 240px);
      min-height: 600px;
  }
  
  .card-list-column {
      width: 320px;
      flex-shrink: 0;
      overflow-y: auto;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
      padding: 1rem;
  }
  
  .board-column {
      flex: 1;
      position: relative;
      overflow: auto;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }
  
  .board-container {
      position: relative;
      background: #ffffff;
  }
  
  .card-list-item {
      cursor: grab;
      transition: all 0.2s;
  }
  
  .card-list-item:hover {
      background-color: #f3f4f6;
  }
  
  .card-list-item.on-board {
      opacity: 0.5 !important;
      background-color: #f9fafb !important;
  }
  
  .card-list-item:active {
      cursor: grabbing;
  }
  
  .board-card {
      position: absolute;
      cursor: move;
      user-select: none;
      width: 280px;
  }
  
  .board-card:active {
      cursor: grabbing;
      z-index: 1001;
  }
  
  .card-list-column.drop-target,
  .board-column.drop-target {
      background-color: #e0e7ff;
      border: 2px dashed #4f46e5;
  }
  
  .dragging-card {
      opacity: 0.5;
  }
  
  .drag-ghost {
      position: fixed;
      pointer-events: none;
      z-index: 9999;
      opacity: 0.8;
      transform: rotate(3deg);
  }
  
  .card-list-column::-webkit-scrollbar,
  .board-column::-webkit-scrollbar {
      width: 8px;
      height: 8px;
  }
  
  .card-list-column::-webkit-scrollbar-track,
  .board-column::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
  }
  
  .card-list-column::-webkit-scrollbar-thumb,
  .board-column::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 4px;
  }
  
  .card-list-column::-webkit-scrollbar-thumb:hover,
  .board-column::-webkit-scrollbar-thumb:hover {
      background: #555;
  }
  
  .zoom-controls {
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      z-index: 1000;
      pointer-events: none;
  }
  
  .zoom-btn {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.15s;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      pointer-events: auto;
  }
  
  .zoom-btn:hover {
      background: #f3f4f6;
      border-color: #3A7BD5;
  }
  
  .zoom-level {
      font-size: 12px;
      color: #6B7280;
      text-align: center;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 4px 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      pointer-events: auto;
  }
</style>
@endsection

@section('content')
<div class="mb-6">
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-4">
        <a href="{{ route('board.list') }}" class="text-gray-600 hover:text-gray-900">
          <i data-lucide="chevron-left" class="w-6 h-6"></i>
        </a>
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ $board['name'] ?? 'ボード表示' }}</h1>
          @if(isset($board['description']) && $board['description'])
          <p class="mt-1 text-gray-600">{{ $board['description'] }}</p>
          @endif
        </div>
      </div>
      <p class="mt-2 text-sm text-gray-500">
        カードをドラッグ&ドロップで配置できます
      </p>
    </div>
    @if(isset($board))
    <div class="flex items-center gap-3">
      <div class="text-sm text-gray-600 flex items-center gap-1">
        <i data-lucide="layers" class="w-4 h-4"></i>
        {{ count($cards) }} カード
      </div>
    </div>
    @endif
  </div>
</div>

<div class="two-column-layout" x-data="boardManager()" x-init="init()">
  <!-- 左カラム：カード一覧 -->
  <div 
      class="card-list-column"
  >
      <div class="mb-4 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">カード一覧</h2>
      <span class="text-sm text-gray-500" x-text="availableCards.length + '件'"></span>
      </div>
      
      <div class="space-y-2">
      <template x-for="card in availableCards" :key="card.id">
          <div 
          class="card-list-item rounded-lg border border-gray-200 p-3"
          :class="{ 
            'on-board': card.onThisBoard === true, 
            'dragging-card': draggedListCard && draggedListCard.id === card.id 
          }"
          x-effect="console.log('Card:', card.id.slice(0,8), 'onThisBoard:', card.onThisBoard, 'classes:', $el.className)"
          draggable="true"
          @dragstart="handleListCardDragStart($event, card)"
          @dragend="handleListCardDragEnd($event)"
          >
          <div class="flex items-start justify-between">
              <h3 class="text-sm font-medium text-gray-900 line-clamp-2" x-text="card.title"></h3>
          </div>
          
          <p x-show="card.company" class="mt-1 text-xs text-gray-600" x-text="card.company"></p>
          
          <div x-show="card.tags && card.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
              <template x-for="tag in card.tags.slice(0, 2)" :key="tag.id">
              <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 rounded" x-text="tag.label"></span>
              </template>
          </div>
          
          <div class="mt-2 text-xs text-gray-500">
              <span x-text="new Date(card.createdAt).toLocaleDateString('ja-JP')"></span>
          </div>
          </div>
      </template>
      </div>
  </div>
  
  <!-- 右カラム：ボード -->
  <div 
      class="board-column"
      @dragover.prevent="handleBoardDragOver($event)"
      @dragleave="handleBoardDragLeave($event)"
      @drop="handleBoardDrop($event)"
      @wheel="handleWheel($event)"
  >
  <div 
      class="board-container" 
      @mousemove="handleMouseMove"
      @mouseup="handleMouseUp"
      @touchmove.prevent="handleTouchMove"
      @touchend="handleTouchEnd"
      :style="`transform: scale(${zoom}); transform-origin: 0 0; min-width: ${100 / zoom}%; min-height: ${100 / zoom}%;`"
  >
      <template x-for="card in boardCards" :key="card.id">
      <div 
          class="board-card bg-white rounded-lg shadow-md p-4 border border-gray-200"
          :style="{ left: card.position.x + 'px', top: card.position.y + 'px' }"
          @mousedown="handleMouseDown($event, card)"
          @touchstart="handleTouchStart($event, card)"
      >
          <div class="flex items-start justify-between mb-2">
              <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 flex-1" x-text="card.title"></h3>
              <div class="flex items-center gap-1">
                  <button
                      @click.stop="removeCardFromBoard(card.id)"
                      class="text-red-500 hover:text-red-700 p-1"
                      title="ボードから削除"
                      type="button"
                  >
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                  </button>
                  <a 
                      :href="'{{ url('/cards') }}/' + card.id" 
                      class="text-indigo-600 hover:text-indigo-800 p-1"
                      target="_blank"
                      title="詳細を表示"
            @click.stop
                      @dragstart.stop
        >
                      <i data-lucide="external-link" class="w-4 h-4"></i>
        </a>
              </div>
          </div>
          
          <p x-show="card.company" class="text-xs text-gray-600 mb-2" x-text="card.company"></p>
          
          <div x-show="card.tags && card.tags.length > 0" class="flex flex-wrap gap-1 mb-2">
        <template x-for="tag in card.tags.slice(0, 2)" :key="tag.id">
            <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 rounded" x-text="tag.label"></span>
        </template>
          </div>
          
          <div class="text-xs text-gray-500 flex items-center justify-between">
        <span x-text="new Date(card.createdAt).toLocaleDateString('ja-JP')"></span>
          </div>
      </div>
      </template>
      </div>
      
      <!-- ズームコントロール -->
      <div class="zoom-controls">
      <button @click="zoomIn" class="zoom-btn" title="拡大">
          <i data-lucide="zoom-in" class="w-5 h-5"></i>
      </button>
      <div class="zoom-level" x-text="Math.round(zoom * 100) + '%'"></div>
      <button @click="zoomOut" class="zoom-btn" title="縮小">
          <i data-lucide="zoom-out" class="w-5 h-5"></i>
      </button>
      <button @click="resetZoom" class="zoom-btn" title="リセット">
          <i data-lucide="maximize-2" class="w-5 h-5"></i>
      </button>
      </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
function boardManager() {
  return {
      allCards: @json($cards),
      boardCards: [],
      boardId: @json($board['id'] ?? null),
      draggedListCard: null,
      draggedBoardCard: null,
      draggedCard: null, // マウスドラッグ用（既存の機能）
      offset: { x: 0, y: 0 },
      boardContainer: null,
      isDragging: false,
      dragStartTime: 0,
      zoom: 1, // ズームレベル（0.25〜2.0）
      minZoom: 0.25,
      maxZoom: 2.0,
      zoomStep: 0.1,
      
      get availableCards() {
      // 全カードを返す（ボード上のカードも含む）
      return this.allCards;
      },
      
      init() {
      // このボードに配置されているカードを初期表示
      this.boardCards = this.allCards.filter(card => {
          // onThisBoardフラグがtrueで、positionが設定されているカード
          return card.onThisBoard === true && card.position !== null && card.position !== undefined;
      }).map(card => ({
          ...card,
          position: card.position || { x: 50, y: 50 }
      }));
      console.log('Board initialized. Total cards: ' + this.allCards.length + ', On board: ' + this.boardCards.length);
      
      // Lucide Iconsを初期化
      this.$nextTick(() => {
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }
      });
      },
      
      isOnBoard(cardId) {
      // ボードカードの配列に存在するか、またはallCardsのonThisBoardフラグで確認
      if (this.boardCards.some(card => card.id === cardId)) {
          return true;
      }
      const card = this.allCards.find(c => c.id === cardId);
      const result = card && card.onThisBoard === true;
      // console.log('isOnBoard(' + cardId + '):', result, 'onThisBoard:', card?.onThisBoard);
      return result;
      },
      
      // === ドラッグ&ドロップ（左カラムのカード） ===
      handleListCardDragStart(event, card) {
      this.draggedListCard = card;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', card.id);
      
      // ドラッグ中のゴーストイメージをカスタマイズ
      event.target.style.opacity = '0.5';
      },
      
      handleListCardDragEnd(event) {
      event.target.style.opacity = '1';
      this.draggedListCard = null;
      },
      
      // === ドラッグ&ドロップ（ボードのカード） ===
      handleBoardCardDragStart(event, card) {
      console.log('🟢 Board card drag start:', card.id);
      // マウスイベントとの競合を防ぐため、ドラッグ開始時にリセット
      this.isDragging = false;
      this.draggedCard = null;
      
      this.draggedBoardCard = card;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', card.id);
      
      event.target.style.opacity = '0.5';
      },
      
      handleBoardCardDragEnd(event) {
      console.log('🟢 Board card drag end');
      event.target.style.opacity = '1';
      this.draggedBoardCard = null;
      },
      
      // === ボードエリアのドロップゾーン ===
      handleBoardDragOver(event) {
      if (this.draggedListCard && !this.draggedListCard.onThisBoard) {
          event.currentTarget.classList.add('drop-target');
      }
      },
      
      handleBoardDragLeave(event) {
      event.currentTarget.classList.remove('drop-target');
      },
      
      async handleBoardDrop(event) {
      event.preventDefault();
      event.currentTarget.classList.remove('drop-target');
      
      if (!this.draggedListCard) return;
      if (this.draggedListCard.onThisBoard) return;
      
      const card = this.draggedListCard;
      
      // ボードコンテナの座標を取得
      const boardColumn = event.currentTarget;
      const boardContainer = boardColumn.querySelector('.board-container');
      const rect = boardColumn.getBoundingClientRect();
      
      // スクロール量を考慮
      const scrollLeft = boardColumn.scrollLeft;
      const scrollTop = boardColumn.scrollTop;
      
      // ドロップした位置にカードを配置（ズームを考慮）
      const x = Math.max(0, (event.clientX - rect.left - 140 + scrollLeft) / this.zoom); // カード幅の半分
      const y = Math.max(0, (event.clientY - rect.top - 80 + scrollTop) / this.zoom);
      
      await this.addCardToBoardAtPosition(card, x, y);
      },
      
      // === 左カラムのドロップゾーン ===
      handleListDragOver(event) {
      console.log('🟡 List drag over. draggedBoardCard:', !!this.draggedBoardCard);
      if (this.draggedBoardCard) {
          event.currentTarget.classList.add('drop-target');
      }
      },
      
      handleListDragLeave(event) {
      event.currentTarget.classList.remove('drop-target');
      },
      
      async handleListDrop(event) {
      event.preventDefault();
      event.currentTarget.classList.remove('drop-target');
      
      console.log('🟡 List drop. draggedBoardCard:', this.draggedBoardCard?.id);
      
      if (!this.draggedBoardCard) {
          console.log('❌ No draggedBoardCard, cannot drop');
          return;
      }
      
      const cardId = this.draggedBoardCard.id;
      await this.removeCardFromBoard(cardId);
      
      // ドラッグ状態をクリア
      this.draggedBoardCard = null;
      },
      
      async addCardToBoardAtPosition(card, x, y) {
      if (!this.boardId) {
          alert('ボードIDが指定されていません');
          return;
      }
      
      console.log('🔵 Adding card to board:', card.id);
      
      try {
          const response = await fetch(`{{ url('/api/boards') }}/${this.boardId}/cards/${card.id}`, {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ x: Math.round(x), y: Math.round(y) })
          });
          
          if (!response.ok) {
          throw new Error('Failed to add card to board');
          }
          
          // ボードに追加
          const cardWithPosition = {
          ...card,
          position: { x: Math.round(x), y: Math.round(y) }
          };
          this.boardCards.push(cardWithPosition);
          
          // allCardsのonThisBoardフラグも更新（Alpine.jsに変更を通知するため配列を再作成）
          this.allCards = this.allCards.map(c => {
          if (c.id === card.id) {
              console.log('✅ Setting onThisBoard = true for card:', c.id);
              return { ...c, onThisBoard: true };
          }
          return c;
          });
          
          console.log('🔵 Card added. isOnBoard:', this.isOnBoard(card.id));
          
          // Lucide Iconsを再初期化
          this.$nextTick(() => {
          if (typeof lucide !== 'undefined') {
              lucide.createIcons();
          }
          });
          
      } catch (error) {
          console.error('Error adding card to board:', error);
          alert('カードの追加に失敗しました');
      }
      },

      
      async removeCardFromBoard(cardId) {
      if (!this.boardId) {
          alert('ボードIDが指定されていません');
          return;
      }
      
      console.log('🔴 Removing card from board:', cardId);
      
      try {
          const response = await fetch(`{{ url('/api/boards') }}/${this.boardId}/cards/${cardId}`, {
          method: 'DELETE',
          headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
          });
          
          if (!response.ok) {
          throw new Error('Failed to remove card from board');
          }
          
          // ボードから削除
          this.boardCards = this.boardCards.filter(card => card.id !== cardId);
          
          // allCardsのonThisBoardフラグも更新（Alpine.jsに変更を通知するため配列を再作成）
          this.allCards = this.allCards.map(c => {
          if (c.id === cardId) {
              console.log('✅ Setting onThisBoard = false for card:', c.id);
              return { ...c, onThisBoard: false };
          }
          return c;
          });
          
          console.log('🔴 Card removed. isOnBoard:', this.isOnBoard(cardId));
          
          // Lucide Iconsを再初期化
          this.$nextTick(() => {
          if (typeof lucide !== 'undefined') {
              lucide.createIcons();
          }
          });
          
      } catch (error) {
          console.error('Error removing card from board:', error);
          alert('カードの削除に失敗しました');
      }
      },
      
      handleMouseDown(event, card) {
      // リンクやボタンのクリックは無視
      if (event.target.closest('a') || event.target.closest('button')) {
          return;
      }
      
      this.startDrag(event, event.clientX, event.clientY, card);
      },
      
      handleTouchStart(event, card) {
      const touch = event.touches[0];
      this.startDrag(event, touch.clientX, touch.clientY, card);
      },
      
      startDrag(event, clientX, clientY, card) {
      this.isDragging = true;
      this.draggedCard = card;
      const rect = event.target.closest('.board-card').getBoundingClientRect();
      this.boardContainer = event.target.closest('.board-container');
      this.offset = {
          x: clientX - rect.left,
          y: clientY - rect.top
      };
      },
      
      handleMouseMove(event) {
      if (!this.draggedCard) return;
      this.updatePosition(event.clientX, event.clientY);
      },
      
      handleTouchMove(event) {
      if (!this.draggedCard) return;
      const touch = event.touches[0];
      this.updatePosition(touch.clientX, touch.clientY);
      },
      
      updatePosition(clientX, clientY) {
      if (!this.boardContainer || !this.isDragging) return;
      
      const containerRect = this.boardContainer.getBoundingClientRect();
      const scrollContainer = this.boardContainer.closest('.board-column');
      
      // スクロール量を考慮
      const scrollLeft = scrollContainer ? scrollContainer.scrollLeft : 0;
      const scrollTop = scrollContainer ? scrollContainer.scrollTop : 0;
      
      // 新しい位置を計算（スクロール量を加算し、ズームを考慮）
      let newX = (clientX - containerRect.left - this.offset.x + scrollLeft) / this.zoom;
      let newY = (clientY - containerRect.top - this.offset.y + scrollTop) / this.zoom;
      
      // 境界チェック（最小値のみ、最大値は制限しない）
      newX = Math.max(0, newX);
      newY = Math.max(0, newY);
      
      this.draggedCard.position.x = newX;
      this.draggedCard.position.y = newY;
      },
      
      async handleMouseUp() {
      if (this.draggedCard && this.isDragging) {
          await this.savePosition(this.draggedCard);
          
          // draggableを再度有効化
          const boardCards = document.querySelectorAll('.board-card');
          boardCards.forEach(card => {
          card.setAttribute('draggable', 'true');
          });
      }
      
      // ドラッグ状態をリセット
      this.draggedCard = null;
      this.boardContainer = null;
      this.isDragging = false;
      },
      
      async handleTouchEnd() {
      if (this.draggedCard && this.isDragging) {
          await this.savePosition(this.draggedCard);
      }
      
      // ドラッグ状態をリセット
      this.draggedCard = null;
      this.boardContainer = null;
      this.isDragging = false;
      },
      
      async savePosition(card) {
      if (!this.boardId) {
          alert('ボードIDが指定されていません');
          return;
      }
      
      try {
          const response = await fetch(`{{ url('/api/boards') }}/${this.boardId}/cards/${card.id}/position`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            x: Math.round(card.position.x),
            y: Math.round(card.position.y)
        })
          });
          
          if (!response.ok) {
        throw new Error('Failed to save position');
          }
      } catch (error) {
          console.error('Error saving position:', error);
          alert('位置の保存に失敗しました');
      }
      },
      
      // ズーム関連のメソッド
      handleWheel(event) {
      // Ctrl/Cmd キーが押されている場合のみズーム
      if (!event.ctrlKey && !event.metaKey) {
          return;
      }
      
      // ズーム時はスクロールをキャンセル
      event.preventDefault();
      
      const delta = event.deltaY;
      if (delta < 0) {
          this.zoomIn();
      } else {
          this.zoomOut();
      }
      },
      
      zoomIn() {
      this.zoom = Math.min(this.maxZoom, this.zoom + this.zoomStep);
      },
      
      zoomOut() {
      this.zoom = Math.max(this.minZoom, this.zoom - this.zoomStep);
      },
      
      resetZoom() {
      this.zoom = 1;
      }
  };
}

// ページロード時にLucide Iconsを初期化
document.addEventListener('DOMContentLoaded', function() {
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
});
</script>
@endsection

