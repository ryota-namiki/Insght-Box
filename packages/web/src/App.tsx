import { NavLink, Route, Routes } from 'react-router-dom';
import UploadPage from './pages/UploadPage';
import MarketplacePage from './pages/MarketplacePage';
import CardDetailPage from './pages/CardDetailPage';
import AnalyticsPage from './pages/AnalyticsPage';
import SettingsPage from './pages/SettingsPage';
import EventsPage from './pages/EventsPage';
import EventDetailPage from './pages/EventDetailPage';

const navItems = [
  { path: '/', label: 'Upload' },
  { path: '/marketplace', label: 'Board' },
  { path: '/events', label: 'Events' },
  { path: '/analytics', label: 'Analytics' },
  { path: '/settings', label: 'Settings' },
];

export default function App(): JSX.Element {
  return (
    <div className="app-shell">
      <header className="app-header">
        <h1>Insight-Box</h1>
        <span>Unified Insights Workspace</span>
      </header>
      <aside className="app-sidebar">
        {navItems.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) => (isActive ? 'active' : undefined)}
            end={item.path === '/'}
          >
            {item.label}
          </NavLink>
        ))}
      </aside>
      <main className="app-content">
        <Routes>
          <Route path="/" element={<UploadPage />} />
          <Route path="/marketplace" element={<MarketplacePage />} />
          <Route path="/events" element={<EventsPage />} />
          <Route path="/event/:eventId" element={<EventDetailPage />} />
          <Route path="/card/:cardId" element={<CardDetailPage />} />
          <Route path="/analytics" element={<AnalyticsPage />} />
          <Route path="/settings" element={<SettingsPage />} />
        </Routes>
      </main>
    </div>
  );
}
