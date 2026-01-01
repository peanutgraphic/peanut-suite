import { lazy, Suspense } from 'react';
import { Routes, Route } from 'react-router-dom';
import { FeatureTour, WelcomeModal, ToastProvider, ErrorBoundary } from './components/common';

// Loading fallback component
function PageLoader() {
  return (
    <div className="flex items-center justify-center min-h-[400px]">
      <div className="flex flex-col items-center gap-3">
        <div className="w-8 h-8 border-3 border-primary-600 border-t-transparent rounded-full animate-spin" />
        <span className="text-sm text-slate-500">Loading...</span>
      </div>
    </div>
  );
}

// Lazy load all page components for code splitting
const Dashboard = lazy(() => import('./pages/Dashboard'));
const UTMBuilder = lazy(() => import('./pages/UTMBuilder'));
const UTMLibrary = lazy(() => import('./pages/UTMLibrary'));
const Links = lazy(() => import('./pages/Links'));
const Contacts = lazy(() => import('./pages/Contacts'));
const Webhooks = lazy(() => import('./pages/Webhooks'));
const Visitors = lazy(() => import('./pages/Visitors'));
const VisitorDetail = lazy(() => import('./pages/VisitorDetail'));
const Attribution = lazy(() => import('./pages/Attribution'));
const Analytics = lazy(() => import('./pages/Analytics'));
const Popups = lazy(() => import('./pages/Popups'));
const PopupBuilder = lazy(() => import('./pages/PopupBuilder'));
const Monitor = lazy(() => import('./pages/Monitor'));
const SiteDetail = lazy(() => import('./pages/SiteDetail'));
const Servers = lazy(() => import('./pages/Servers'));
const ServerDetail = lazy(() => import('./pages/ServerDetail'));
const HealthReports = lazy(() => import('./pages/HealthReports'));
const Settings = lazy(() => import('./pages/Settings'));
const Security = lazy(() => import('./pages/Security'));
const Backlinks = lazy(() => import('./pages/Backlinks'));
const Sequences = lazy(() => import('./pages/Sequences'));
const Keywords = lazy(() => import('./pages/Keywords'));
const WooCommerce = lazy(() => import('./pages/WooCommerce'));
const Performance = lazy(() => import('./pages/Performance'));
const Team = lazy(() => import('./pages/Team'));
const TeamMemberProfile = lazy(() => import('./pages/TeamMemberProfile'));
const AuditLog = lazy(() => import('./pages/AuditLog'));
const ApiKeys = lazy(() => import('./pages/ApiKeys'));

export default function App() {
  return (
    <ErrorBoundary>
      <ToastProvider>
        <Suspense fallback={<PageLoader />}>
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
            <Route path="/servers" element={<Servers />} />
            <Route path="/servers/:id" element={<ServerDetail />} />
            <Route path="/health-reports" element={<HealthReports />} />
            <Route path="/security" element={<Security />} />
            <Route path="/backlinks" element={<Backlinks />} />
            <Route path="/sequences" element={<Sequences />} />
            <Route path="/keywords" element={<Keywords />} />
            <Route path="/woocommerce" element={<WooCommerce />} />
            <Route path="/performance" element={<Performance />} />
            <Route path="/team" element={<Team />} />
            <Route path="/team/:userId" element={<TeamMemberProfile />} />
            <Route path="/audit-log" element={<AuditLog />} />
            <Route path="/api-keys" element={<ApiKeys />} />
            <Route path="/settings" element={<Settings />} />
          </Routes>
        </Suspense>

        {/* Feature Tour */}
        <WelcomeModal />
        <FeatureTour />
      </ToastProvider>
    </ErrorBoundary>
  );
}
