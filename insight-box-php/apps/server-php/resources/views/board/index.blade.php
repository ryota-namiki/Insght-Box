@extends('layouts.app')

@section('title', 'ボード - Insight-Box')

@section('styles')
<style>
    .board-container {
        position: relative;
        min-height: 600px;
        background: linear-gradient(to right, #f0f0f0 1px, transparent 1px),
                    linear-gradient(to bottom, #f0f0f0 1px, transparent 1px);
        background-size: 50px 50px;
    }
    
    .board-card {
        position: absolute;
        cursor: move;
        user-select: none;
    }
    
    .board-card:active {
        cursor: grabbing;
    }
</style>
@endsection

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">ボード表示</h1>
    <p class="mt-2 text-gray-600">カードをドラッグして配置を変更できます</p>
</div>

<div class="bg-white rounded-lg shadow-lg p-4">
    <div 
        class="board-container" 
        x-data="boardManager()" 
        x-init="init()"
        @mousemove="handleMouseMove"
        @mouseup="handleMouseUp"
        @touchmove.prevent="handleTouchMove"
        @touchend="handleTouchEnd"
    >
        <template x-for="card in cards" :key="card.id">
            <div 
                class="board-card bg-white rounded-lg shadow-md p-4 w-64"
                :style="{ left: card.position.x + 'px', top: card.position.y + 'px' }"
                @mousedown="handleMouseDown($event, card)"
                @touchstart="handleTouchStart($event, card)"
            >
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-900 line-clamp-2" x-text="card.title"></h3>
                    <a 
                        :href="'/cards/' + card.id" 
                        class="ml-2 text-indigo-600 hover:text-indigo-800"
                        @click.stop
                    >
                        <span class="material-icons text-sm">open_in_new</span>
                    </a>
                </div>
                
                <p x-show="card.company" class="text-xs text-gray-600 mb-2" x-text="card.company"></p>
                
                <div x-show="card.tags && card.tags.length > 0" class="flex flex-wrap gap-1 mb-2">
                    <template x-for="tag in card.tags.slice(0, 2)" :key="tag.id">
                        <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-800 rounded" x-text="tag.label"></span>
                    </template>
                </div>
                
                <div class="text-xs text-gray-500 flex items-center justify-between">
                    <span x-text="new Date(card.createdAt).toLocaleDateString('ja-JP')"></span>
                    <span class="material-icons text-sm">drag_indicator</span>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@section('scripts')
<script>
function boardManager() {
    return {
        cards: @json($cards),
        draggedCard: null,
        offset: { x: 0, y: 0 },
        
        init() {
            console.log('Board initialized with ' + this.cards.length + ' cards');
        },
        
        handleMouseDown(event, card) {
            this.startDrag(event.clientX, event.clientY, card);
        },
        
        handleTouchStart(event, card) {
            const touch = event.touches[0];
            this.startDrag(touch.clientX, touch.clientY, card);
        },
        
        startDrag(clientX, clientY, card) {
            this.draggedCard = card;
            const rect = event.target.getBoundingClientRect();
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
            const container = event.currentTarget.getBoundingClientRect();
            this.draggedCard.position.x = Math.max(0, Math.min(
                clientX - container.left - this.offset.x,
                container.width - 256
            ));
            this.draggedCard.position.y = Math.max(0, Math.min(
                clientY - container.top - this.offset.y,
                container.height - 200
            ));
        },
        
        async handleMouseUp() {
            if (this.draggedCard) {
                await this.savePosition(this.draggedCard);
                this.draggedCard = null;
            }
        },
        
        async handleTouchEnd() {
            if (this.draggedCard) {
                await this.savePosition(this.draggedCard);
                this.draggedCard = null;
            }
        },
        
        async savePosition(card) {
            try {
                const response = await fetch(`/api/cards/${card.id}/position`, {
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
        }
    };
}
</script>
@endsection

