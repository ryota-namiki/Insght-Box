import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { getBoard, updateCardPosition, type CardSummary } from '../api/client';

interface CardWithPosition extends CardSummary {
  position: { x: number; y: number };
}

const ITEM_TYPE = 'CARD';

interface DraggableCardProps {
  card: CardWithPosition;
  onMove: (cardId: string, x: number, y: number) => void;
  navigate: (path: string) => void;
}

function DraggableCard({ card, onMove, navigate }: DraggableCardProps): JSX.Element {
  const [{ isDragging }, drag] = useDrag({
    type: ITEM_TYPE,
    item: { id: card.id, x: card.position.x, y: card.position.y },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  });

  return (
    <article
      ref={drag}
      className="section-card"
      style={{
        position: 'absolute',
        left: card.position.x,
        top: card.position.y,
        width: '300px',
        border: '1px solid #e5e7eb',
        boxShadow: isDragging ? '0 8px 25px rgba(0,0,0,0.15)' : 'none',
        cursor: isDragging ? 'grabbing' : 'grab',
        transition: isDragging ? 'none' : 'transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease', // ドラッグ中のみアニメーション無効化
        opacity: 1, // ドラッグ中も不透明度を1に保つ
        backgroundColor: '#fff',
        zIndex: isDragging ? 1000 : 1,
        transform: 'none', // ドラッグ中の回転効果を削除
      }}
      onClick={() => navigate(`/card/${card.id}`)}
      onMouseEnter={(e) => {
        if (!isDragging) {
          e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
          e.currentTarget.style.borderColor = '#007bff';
          e.currentTarget.style.transform = 'translateY(-2px)';
        }
      }}
      onMouseLeave={(e) => {
        if (!isDragging) {
          e.currentTarget.style.boxShadow = 'none';
          e.currentTarget.style.borderColor = '#e5e7eb';
          e.currentTarget.style.transform = 'none';
        }
      }}
    >
      <h3 style={{ marginBottom: 8 }}>
        <Link
          to={`/card/${card.id}`}
          onClick={(e) => e.stopPropagation()}
          style={{ textDecoration: 'none', color: '#111827' }}
        >
          {card.title}
        </Link>
      </h3>
      <p style={{ color: '#6b7280', fontSize: '0.85rem' }}>{card.company}</p>
      <p style={{ color: '#9ca3af', fontSize: '0.8rem' }}>{new Date(card.createdAt).toLocaleString()}</p>
      <div style={{ marginTop: 12, display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        {(card.tags ?? []).map((tag, index) => (
          <span key={`${card.id}-${typeof tag === 'string' ? tag : tag.id}-${index}`} className="tag-pill">
            #{typeof tag === 'string' ? tag : tag.label}
          </span>
        ))}
      </div>
    </article>
  );
}

function BoardContent(): JSX.Element {
  const [cards, setCards] = useState<CardWithPosition[]>([]);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    void loadBoard();
  }, []);

  async function loadBoard(): Promise<void> {
    try {
      const result = await getBoard();
      // 保存された位置情報がある場合はそれを使用、なければ初期位置を設定
      const cardsWithPosition = result.map((card: any, index: number) => ({
        ...card,
        position: card.position || {
          x: (index % 3) * 320, // 3列で配置、カード幅300px + 間隔20px
          y: Math.floor(index / 3) * 200, // 行間隔200px
        },
      }));
      setCards(cardsWithPosition);
    } catch (err) {
      setError((err as Error).message);
    }
  }

  async function handleMoveCard(cardId: string, x: number, y: number): Promise<void> {
    console.log('Moving card:', cardId, 'to position:', x, y);
    
    // フロントエンドの状態を即座に更新（元の位置を保存）
    const originalPosition = cards.find(card => card.id === cardId)?.position || { x: 0, y: 0 };
    
    setCards((prevCards) =>
      prevCards.map((card) =>
        card.id === cardId ? { ...card, position: { x, y } } : card
      )
    );
    
    // バックエンドに位置情報を保存（非同期で実行、エラー時のみ元に戻す）
    updateCardPosition(cardId, x, y)
      .then(() => {
        console.log('Position update successful');
      })
      .catch((err) => {
        console.error('位置情報の保存に失敗しました:', err);
        // エラーが発生した場合のみ元の位置に戻す
        setCards((prevCards) =>
          prevCards.map((card) =>
            card.id === cardId ? { ...card, position: originalPosition } : card
          )
        );
      });
  }

  const [{ isOver }, drop] = useDrop({
    accept: ITEM_TYPE,
    drop: (item: { id: string; x: number; y: number }, monitor) => {
      const delta = monitor.getDifferenceFromInitialOffset();
      if (delta) {
        // ドロップ位置を正確に計算
        const dropResult = monitor.getDropResult();
        const clientOffset = monitor.getClientOffset();
        const initialClientOffset = monitor.getInitialClientOffset();
        
        if (clientOffset && initialClientOffset) {
          // ドロップゾーン内での相対位置を計算
          const dropZoneRect = (monitor.getDropResult() as any)?.dropZoneRect;
          const newX = Math.max(0, item.x + delta.x);
          const newY = Math.max(0, item.y + delta.y);
          
          console.log('Drop zone: moving card:', item.id, 'to:', newX, newY);
          handleMoveCard(item.id, newX, newY);
        }
      }
    },
    collect: (monitor) => ({
      isOver: monitor.isOver(),
    }),
  });

  return (
    <section className="section-card">
      <h2 className="section-title">Board</h2>
      {error && <p style={{ color: '#dc2626' }}>{error}</p>}
      <div
        ref={drop}
        style={{
          position: 'relative',
          minHeight: '600px',
          width: '100%',
          backgroundColor: isOver ? '#f0f9ff' : '#f8fafc',
          border: '2px dashed #e2e8f0',
          borderRadius: '8px',
          padding: '20px',
        }}
      >
        {cards.map((card) => (
          <DraggableCard
            key={card.id}
            card={card}
            onMove={handleMoveCard}
            navigate={navigate}
          />
        ))}
      </div>
    </section>
  );
}

export default function MarketplacePage(): JSX.Element {
  return (
    <DndProvider backend={HTML5Backend}>
      <BoardContent />
    </DndProvider>
  );
}
