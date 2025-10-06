import { FormEvent, useEffect, useState } from 'react';
import type { SettingsRolesInputs } from '@insight-box/core';
import { getSettings, updateSettings } from '../api/client';

export default function SettingsPage(): JSX.Element {
  const [settings, setSettings] = useState<SettingsRolesInputs | null>(null);
  const [status, setStatus] = useState<string>('');
  const [error, setError] = useState<string>('');

  useEffect(() => {
    void loadSettings();
  }, []);

  async function loadSettings(): Promise<void> {
    try {
      const data = await getSettings();
      setSettings(data);
    } catch (err) {
      setError((err as Error).message);
    }
  }

  const handleChange = (field: keyof SettingsRolesInputs) => (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    if (!settings) return;
    const value = field === 'eventUploadMax' ? Number(event.target.value) : event.target.value;
    setSettings({ ...settings, [field]: value });
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!settings) return;
    setStatus('');
    setError('');
    try {
      await updateSettings(settings);
      setStatus('設定を保存しました');
    } catch (err) {
      setError((err as Error).message);
    }
  };

  return (
    <section className="section-card">
      <h2 className="section-title">権限・上限設定</h2>
      {settings ? (
        <form onSubmit={handleSubmit} className="grid" style={{ gap: 12, maxWidth: 360 }}>
          <label>
            既定公開範囲
            <select className="select" value={settings.defaultScope} onChange={handleChange('defaultScope')}>
              <option value="team">チーム</option>
              <option value="org">全社</option>
            </select>
          </label>
          <label>
            イベントあたりのアップロード上限
            <input
              className="input"
              type="number"
              min={1}
              max={200}
              value={settings.eventUploadMax}
              onChange={handleChange('eventUploadMax')}
            />
          </label>
          <button className="button" type="submit">
            保存
          </button>
          {status && <p style={{ color: '#059669' }}>{status}</p>}
          {error && <p style={{ color: '#dc2626' }}>{error}</p>}
        </form>
      ) : (
        <p>読み込み中...</p>
      )}
    </section>
  );
}
