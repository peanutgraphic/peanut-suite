import { Routes, Route } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import UTMBuilder from './pages/UTMBuilder';
import UTMLibrary from './pages/UTMLibrary';
import Links from './pages/Links';
import Contacts from './pages/Contacts';
import Webhooks from './pages/Webhooks';
import Visitors from './pages/Visitors';
import VisitorDetail from './pages/VisitorDetail';
import Attribution from './pages/Attribution';
import Analytics from './pages/Analytics';
import Popups from './pages/Popups';
import PopupBuilder from './pages/PopupBuilder';
import Monitor from './pages/Monitor';
import SiteDetail from './pages/SiteDetail';
import Settings from './pages/Settings';
import { FeatureTour, WelcomeModal, ToastProvider } from './components/common';

export default function App() {
  return (
    <ToastProvider>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/utm" element={<UTMBuilder />} />
        <Route path="/utm/library" element={<UTMLibrary />} />
        <Route path="/links" element={<Links />} />
        <Route path="/contacts" element={<Contacts />} />
        <Route path="/webhooks" element={<Webhooks />} />
        <Route path="/visitors" element={<Visitors />} />
        <Route path="/visitors/:id" element={<VisitorDetail />} />
        <Route path="/attribution" element={<Attribution />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/popups" element={<Popups />} />
        <Route path="/popups/new" element={<PopupBuilder />} />
        <Route path="/popups/:id/edit" element={<PopupBuilder />} />
        <Route path="/monitor" element={<Monitor />} />
        <Route path="/monitor/sites/:id" element={<SiteDetail />} />
        <Route path="/settings" element={<Settings />} />
      </Routes>

      {/* Feature Tour */}
      <WelcomeModal />
      <FeatureTour />
    </ToastProvider>
  );
}
