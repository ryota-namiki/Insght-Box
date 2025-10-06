import { useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import type { EventMeta } from '@insight-box/core';
import { getEvents, updateEvent, deleteEvent } from '../api/client';
import Toast from '../components/Toast';

export default function EventDetailPage(): JSX.Element {
  const { eventId } = useParams<{ eventId: string }>();
  const navigate = useNavigate();
  const [event, setEvent] = useState<EventMeta | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [isDeleting, setIsDeleting] = useState<boolean>(false);
  const [editForm, setEditForm] = useState<{
    name: string;
    startDate: string;
    endDate: string;
    location: string;
  }>({
    name: '',
    startDate: '',
    endDate: '',
    location: ''
  });

  useEffect(() => {
    if (!eventId) return;
    void loadEvent(eventId);
  }, [eventId]);

  async function loadEvent(id: string): Promise<void> {
    try {
      const eventList = await getEvents();
      const foundEvent = eventList.find(e => e.id === id);
      if (!foundEvent) {
        setError('イベントが見つかりませんでした');
        return;
      }
      setEvent(foundEvent);
    } catch (err) {
      setError((err as Error).message);
    }
  }

  function startEditing(): void {
    if (!event) return;
    
    setEditForm({
      name: event.name,
      startDate: event.startDate,
      endDate: event.endDate,
      location: event.location || ''
    });
    setIsEditing(true);
  }

  function cancelEditing(): void {
    setIsEditing(false);
    setEditForm({
      name: '',
      startDate: '',
      endDate: '',
      location: ''
    });
  }

  async function handleUpdate(): Promise<void> {
    if (!eventId || !event) return;

    try {
      await updateEvent(eventId, {
        name: editForm.name,
        startDate: editForm.startDate,
        endDate: editForm.endDate,
        location: editForm.location || undefined
      });

      setToast({ message: 'イベントが正常に更新されました', type: 'success' });
      setIsEditing(false);
      // イベント情報を再読み込み
      await loadEvent(eventId);
    } catch (err) {
      setToast({ message: `更新エラー: ${(err as Error).message}`, type: 'error' });
    }
  }

  async function handleDelete(): Promise<void> {
    if (!eventId || !event) return;

    if (!confirm(`イベント「${event.name}」を削除してもよろしいですか？この操作は取り消せません。`)) {
      return;
    }

    try {
      setIsDeleting(true);
      await deleteEvent(eventId);
      setToast({ message: 'イベントが正常に削除されました', type: 'success' });
      
      // 削除後、イベント一覧ページにリダイレクト
      setTimeout(() => {
        navigate('/events', { state: { toast: 'イベントが削除されました', toastType: 'success' } });
      }, 1000);
    } catch (err) {
      setToast({ message: `削除エラー: ${(err as Error).message}`, type: 'error' });
      setIsDeleting(false);
    }
  }

  function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  if (error) {
    return (
      <div className="section-card">
        <p style={{ color: '#dc2626' }}>エラー: {error}</p>
        <Link to="/events" className="button" style={{ textDecoration: 'none' }}>
          イベント一覧に戻る
        </Link>
      </div>
    );
  }

  if (!event) {
    return (
      <div className="section-card">
        <p>読み込み中...</p>
      </div>
    );
  }

  return (
    <div>
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}
      
      <section className="section-card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 32 }}>
          <Link to="/events" className="button" style={{ textDecoration: 'none', fontSize: '14px', display: 'flex', alignItems: 'center', gap: '4px' }}>
            <span className="material-icons" style={{ fontSize: '18px' }}>chevron_left</span>
            戻る
          </Link>
          <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
            {!isEditing && (
              <>
                <button 
                  onClick={startEditing}
                  style={{
                    background: '#007bff',
                    color: 'white',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '6px',
                    cursor: 'pointer',
                    fontSize: '14px',
                    fontWeight: 'bold',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}
                >
                  <span className="material-icons" style={{ fontSize: '18px' }}>edit</span>
                  編集
                </button>
                <button 
                  onClick={handleDelete}
                  disabled={isDeleting}
                  style={{
                    background: isDeleting ? '#6c757d' : '#dc3545',
                    color: 'white',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '6px',
                    cursor: isDeleting ? 'not-allowed' : 'pointer',
                    fontSize: '14px',
                    fontWeight: 'bold',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}
                >
                  <span className="material-icons" style={{ fontSize: '18px' }}>delete</span>
                  {isDeleting ? '削除中...' : '削除'}
                </button>
              </>
            )}
          </div>
        </div>

        <div style={{ marginBottom: 32 }}>
          <h2 className="section-title">{event.name}</h2>
          <div style={{ display: 'flex', gap: 16, alignItems: 'center', marginTop: 8 }}>
            <span className="badge" style={{ backgroundColor: '#e3f2fd', color: '#1976d2', display: 'flex', alignItems: 'center', gap: '4px' }}>
              <span className="material-icons" style={{ fontSize: '16px' }}>calendar_today</span>
              {formatDate(event.startDate)} 〜 {formatDate(event.endDate)}
            </span>
            {event.location && (
              <span className="badge" style={{ backgroundColor: '#f3e5f5', color: '#7b1fa2', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <span className="material-icons" style={{ fontSize: '16px' }}>place</span>
                {event.location}
              </span>
            )}
          </div>
        </div>

        {/* 編集フォーム */}
        {isEditing && (
          <div style={{ marginBottom: 24, padding: 20, backgroundColor: '#f8f9fa', borderRadius: 8, border: '2px solid #007bff' }}>
            <h3 style={{ margin: '0 0 16px 0', color: '#007bff' }}>✏️ イベントを編集</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
              <div>
                <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                  イベント名
                </label>
                <input
                  type="text"
                  value={editForm.name}
                  onChange={(e) => setEditForm(prev => ({ ...prev, name: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                  placeholder="イベント名を入力"
                />
              </div>
              
              <div style={{ display: 'flex', gap: 16 }}>
                <div style={{ flex: 1 }}>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    開始日
                  </label>
                  <input
                    type="date"
                    value={editForm.startDate}
                    onChange={(e) => setEditForm(prev => ({ ...prev, startDate: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                  />
                </div>
                <div style={{ flex: 1 }}>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    終了日
                  </label>
                  <input
                    type="date"
                    value={editForm.endDate}
                    onChange={(e) => setEditForm(prev => ({ ...prev, endDate: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                  />
                </div>
              </div>

              <div>
                <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                  場所（任意）
                </label>
                <input
                  type="text"
                  value={editForm.location}
                  onChange={(e) => setEditForm(prev => ({ ...prev, location: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '8px 12px',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px'
                  }}
                  placeholder="場所を入力（任意）"
                />
              </div>
              
              <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                <button
                  onClick={cancelEditing}
                  style={{
                    background: '#6c757d',
                    color: 'white',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '6px',
                    cursor: 'pointer',
                    fontSize: '14px',
                    fontWeight: 'bold'
                  }}
                >
                  キャンセル
                </button>
                <button
                  onClick={handleUpdate}
                  style={{
                    background: '#28a745',
                    color: 'white',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '6px',
                    cursor: 'pointer',
                    fontSize: '14px',
                    fontWeight: 'bold',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}
                >
                  <span className="material-icons" style={{ fontSize: '18px' }}>save</span>
                  保存
                </button>
              </div>
            </div>
          </div>
        )}
      </section>
    </div>
  );
}

