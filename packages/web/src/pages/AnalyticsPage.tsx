import { useEffect, useState } from 'react';
import type { AnalyticsCardOutputs, AnalyticsPersonalOutputs, CardSummary } from '@insight-box/core';
import { getCardAnalytics, getCards, getPersonalAnalytics } from '../api/client';

export default function AnalyticsPage(): JSX.Element {
  const [personal, setPersonal] = useState<AnalyticsPersonalOutputs | null>(null);
  const [cards, setCards] = useState<CardSummary[]>([]);
  const [selectedCardId, setSelectedCardId] = useState<string>('');
  const [cardAnalytics, setCardAnalytics] = useState<AnalyticsCardOutputs | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void loadInitial();
  }, []);

  useEffect(() => {
    if (selectedCardId) {
      void loadCardAnalytics(selectedCardId);
    }
  }, [selectedCardId]);

  async function loadInitial(): Promise<void> {
    try {
      const [personalData, cardList] = await Promise.all([getPersonalAnalytics(), getCards()]);
      setPersonal(personalData);
      setCards(cardList);
      if (cardList.length) {
        setSelectedCardId(cardList[0]!.id);
      }
    } catch (err) {
      setError((err as Error).message);
    }
  }

  async function loadCardAnalytics(cardId: string): Promise<void> {
    try {
      const data = await getCardAnalytics(cardId);
      setCardAnalytics(data);
    } catch (err) {
      setError((err as Error).message);
    }
  }

  return (
    <div className="grid" style={{ gap: 24 }}>
      <section className="section-card">
        <h2 className="section-title">個人ダッシュボード</h2>
        {personal ? (
          <div style={{ display: 'flex', gap: 16 }}>
            <span className="badge">閲覧: {personal.kpis.views}</span>
            <span className="badge">コメント: {personal.kpis.comments}</span>
            <span className="badge">いいね: {personal.kpis.likes}</span>
          </div>
        ) : (
          <p>読み込み中...</p>
        )}
      </section>

      <section className="section-card">
        <h2 className="section-title">カード別アナリティクス</h2>
        <select className="select" value={selectedCardId} onChange={(event) => setSelectedCardId(event.target.value)}>
          {cards.map((card) => (
            <option key={card.id} value={card.id}>
              {card.title}
            </option>
          ))}
        </select>
        {cardAnalytics ? (
          <div>
            <div style={{ display: 'flex', gap: 16 }}>
              <span className="badge">閲覧: {cardAnalytics.kpis.views}</span>
              <span className="badge">コメント: {cardAnalytics.kpis.comments}</span>
              <span className="badge">いいね: {cardAnalytics.kpis.likes}</span>
            </div>
            <h3 style={{ marginTop: 16 }}>推移</h3>
            <ul style={{ paddingLeft: 16 }}>
              {cardAnalytics.timeseries.map((row) => (
                <li key={row.date}>
                  {row.date}: {row.views} views / {row.comments} comments / {row.likes} likes
                </li>
              ))}
            </ul>
            <h3 style={{ marginTop: 16 }}>閲覧者の部署</h3>
            <ul style={{ paddingLeft: 16 }}>
              {cardAnalytics.audienceDistribution.map((row) => (
                <li key={row.department}>
                  {row.department}: {(row.ratio * 100).toFixed(0)}%
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <p>カードを選択してください。</p>
        )}
      </section>

      {error && <p style={{ color: '#dc2626' }}>{error}</p>}
    </div>
  );
}
