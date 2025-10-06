import { FormEvent, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import type { EventMeta } from '@insight-box/core';
import { getEvents, createEvent, updateEvent, deleteEvent } from '../api/client';

interface EventFormState {
  name: string;
  startDate: string;
  endDate: string;
  location: string;
}

const initialForm: EventFormState = {
  name: '',
  startDate: '',
  endDate: '',
  location: '',
};

export default function EventsPage(): JSX.Element {
  const navigate = useNavigate();
  const [events, setEvents] = useState<EventMeta[]>([]);
  const [form, setForm] = useState<EventFormState>(initialForm);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [editingEventId, setEditingEventId] = useState<string | null>(null);
  const [deletingEventId, setDeletingEventId] = useState<string | null>(null);

  useEffect(() => {
    void loadEvents();
  }, []);

  async function loadEvents(): Promise<void> {
    try {
      const eventList = await getEvents();
      setEvents(eventList);
    } catch (err) {
      setError((err as Error).message);
    }
  }

  const handleChange = (field: keyof EventFormState) => (event: React.ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, [field]: event.target.value }));
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setSuccessMessage(null);

    try {
      if (editingEventId) {
        // 編集モード
        const response = await updateEvent(editingEventId, {
          name: form.name,
          startDate: form.startDate,
          endDate: form.endDate,
          location: form.location || undefined,
        });
        setSuccessMessage(`イベント「${response.event.name}」を更新しました`);
        setEditingEventId(null);
      } else {
        // 新規作成モード
        const response = await createEvent({
          name: form.name,
          startDate: form.startDate,
          endDate: form.endDate,
          location: form.location || undefined,
        });
        setSuccessMessage(`イベント「${response.event.name}」を作成しました`);
      }
      
      setForm(initialForm);
      await loadEvents(); // イベント一覧を再読み込み
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (event: EventMeta) => {
    setForm({
      name: event.name,
      startDate: event.startDate,
      endDate: event.endDate,
      location: event.location || '',
    });
    setEditingEventId(event.id);
    setSuccessMessage(null);
    setError(null);
  };

  const handleCancelEdit = () => {
    setForm(initialForm);
    setEditingEventId(null);
  };

  const handleDelete = async (eventId: string, eventName: string) => {
    if (!confirm(`イベント「${eventName}」を削除してもよろしいですか？`)) {
      return;
    }

    setDeletingEventId(eventId);
    setError(null);
    setSuccessMessage(null);

    try {
      await deleteEvent(eventId);
      setSuccessMessage(`イベント「${eventName}」を削除しました`);
      await loadEvents();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setDeletingEventId(null);
    }
  };

  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  return (
    <div className="grid" style={{ gap: 24 }}>
      <section className="section-card">
        <h1 className="section-title" style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: 24 }}>
          <span className="material-icons" style={{ fontSize: '28px' }}>event</span>
          イベント管理
        </h1>

        {error && (
          <div style={{ 
            padding: 12, 
            backgroundColor: '#fee2e2', 
            color: '#dc2626', 
            borderRadius: 8, 
            marginBottom: 16 
          }}>
            {error}
          </div>
        )}

        {successMessage && (
          <div style={{ 
            padding: 12, 
            backgroundColor: '#dcfce7', 
            color: '#16a34a', 
            borderRadius: 8, 
            marginBottom: 16 
          }}>
            {successMessage}
          </div>
        )}

        <form onSubmit={handleSubmit} className="grid" style={{ gap: 16 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
            <h2 style={{ margin: 0, fontSize: '1.2rem', color: '#374151', display: 'flex', alignItems: 'center', gap: '6px' }}>
              {editingEventId ? (
                <>
                  <span className="material-icons" style={{ fontSize: '20px' }}>edit</span>
                  イベントを編集
                </>
              ) : (
                <>
                  <span className="material-icons" style={{ fontSize: '20px' }}>add_circle</span>
                  新しいイベントを作成
                </>
              )}
            </h2>
            {editingEventId && (
              <button
                type="button"
                onClick={handleCancelEdit}
                style={{
                  background: '#6c757d',
                  color: 'white',
                  border: 'none',
                  padding: '6px 12px',
                  borderRadius: '6px',
                  cursor: 'pointer',
                  fontSize: '14px',
                }}
              >
                キャンセル
              </button>
            )}
          </div>
          
          <input
            className="input"
            placeholder="イベント名 *"
            value={form.name}
            onChange={handleChange('name')}
            required
            maxLength={100}
          />
          
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <input
              className="input"
              type="date"
              placeholder="開始日 *"
              value={form.startDate}
              onChange={handleChange('startDate')}
              required
            />
            <input
              className="input"
              type="date"
              placeholder="終了日 *"
              value={form.endDate}
              onChange={handleChange('endDate')}
              required
            />
          </div>
          
          <input
            className="input"
            placeholder="開催場所 (任意)"
            value={form.location}
            onChange={handleChange('location')}
            maxLength={200}
          />
          
          <button 
            className="button" 
            type="submit" 
            disabled={loading}
            style={{ 
              opacity: loading ? 0.6 : 1,
              cursor: loading ? 'not-allowed' : 'pointer',
              background: editingEventId ? '#fbbf24' : undefined,
            }}
          >
            {loading ? (editingEventId ? '更新中...' : '作成中...') : (editingEventId ? 'イベントを更新' : 'イベントを作成')}
          </button>
        </form>
      </section>

      <section className="section-card">
        <h2 className="section-title">📋 イベント一覧</h2>
        
        {events.length === 0 ? (
          <p style={{ color: '#6b7280', textAlign: 'center', padding: 24 }}>
            イベントが登録されていません
          </p>
        ) : (
          <div style={{ 
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
            gap: '16px',
            justifyContent: 'start',
            alignItems: 'start'
          }}>
            {events.map((event) => (
              <article 
                key={event.id}
                className="section-card"
                style={{ 
                  border: '1px solid #e5e7eb',
                  boxShadow: 'none',
                  cursor: 'pointer',
                  transition: 'all 0.2s ease',
                  height: '200px',
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'space-between',
                }}
                onClick={() => navigate(`/event/${event.id}`)}
                onMouseEnter={(e) => {
                  e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                  e.currentTarget.style.borderColor = '#007bff';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.boxShadow = 'none';
                  e.currentTarget.style.borderColor = '#e5e7eb';
                }}
              >
                <div style={{ flex: '1', display: 'flex', flexDirection: 'column' }}>
                  <h3 style={{ margin: '0 0 12px 0', fontSize: '1.1rem', color: '#374151', lineHeight: '1.3' }}>
                    {event.name}
                  </h3>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', flex: '1' }}>
                    <span style={{ color: '#6b7280', fontSize: '0.9rem', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <span className="material-icons" style={{ fontSize: '16px' }}>calendar_today</span>
                      {formatDate(event.startDate)} 〜 {formatDate(event.endDate)}
                    </span>
                    {event.location && (
                      <span style={{ color: '#6b7280', fontSize: '0.9rem', display: 'flex', alignItems: 'center', gap: '4px' }}>
                        <span className="material-icons" style={{ fontSize: '16px' }}>place</span>
                        {event.location}
                      </span>
                    )}
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
