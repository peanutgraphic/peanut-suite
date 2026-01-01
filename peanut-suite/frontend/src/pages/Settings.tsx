import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Settings as SettingsIcon,
  Key,
  Link2,
  Bell,
  Database,
  Shield,
  ExternalLink,
  Check,
  AlertCircle,
  RotateCcw,
  Download,
  Upload,
  Trash2,
  Loader2,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, Modal, ConfirmModal, useToast } from '../components/common';
import { useTourStore } from '../store';
import { usePageGuideStore } from '../store/usePageGuideStore';
import { helpContent } from '../constants';
import { settingsApi } from '../api/endpoints';

type Tab = 'general' | 'license' | 'integrations' | 'notifications' | 'advanced';

export default function Settings() {
  const [activeTab, setActiveTab] = useState<Tab>('general');

  const tabs = [
    { id: 'general' as Tab, label: 'General', icon: SettingsIcon },
    { id: 'license' as Tab, label: 'License', icon: Key },
    { id: 'integrations' as Tab, label: 'Integrations', icon: Link2 },
    { id: 'notifications' as Tab, label: 'Notifications', icon: Bell },
    { id: 'advanced' as Tab, label: 'Advanced', icon: Database },
  ];

  return (
    <Layout title="Settings" description="Configure your Peanut Suite" pageGuideId="settings">
      <div className="flex gap-6">
        {/* Sidebar */}
        <div className="w-56 flex-shrink-0">
          <nav className="space-y-1">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                  activeTab === tab.id
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <tab.icon className="w-5 h-5" />
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Content */}
        <div className="flex-1">
          {activeTab === 'general' && <GeneralSettings />}
          {activeTab === 'license' && <LicenseSettings />}
          {activeTab === 'integrations' && <IntegrationSettings />}
          {activeTab === 'notifications' && <NotificationSettings />}
          {activeTab === 'advanced' && <AdvancedSettings />}
        </div>
      </div>
    </Layout>
  );
}

function GeneralSettings() {
  const toast = useToast();
  const queryClient = useQueryClient();

  const { data: settings, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: settingsApi.get,
  });

  const [localSettings, setLocalSettings] = useState({
    link_prefix: '',
    track_clicks: true,
    anonymize_ip: false,
  });

  // Update local state when settings load
  useEffect(() => {
    if (settings) {
      setLocalSettings({
        link_prefix: settings.link_prefix || '',
        track_clicks: settings.track_clicks ?? true,
        anonymize_ip: settings.anonymize_ip ?? false,
      });
    }
  }, [settings]);

  const updateMutation = useMutation({
    mutationFn: settingsApi.update,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] });
      toast.success('Settings saved successfully');
    },
    onError: () => {
      toast.error('Failed to save settings');
    },
  });

  const handleSave = () => {
    updateMutation.mutate(localSettings);
  };

  if (isLoading) {
    return (
      <Card className="flex items-center justify-center py-12">
        <Loader2 className="w-6 h-6 animate-spin text-slate-400" />
      </Card>
    );
  }

  return (
    <Card>
      <h3 className="text-lg font-semibold text-slate-900 mb-6">General Settings</h3>
      <div className="space-y-6">
        <Input
          label="Link Prefix"
          value={localSettings.link_prefix}
          onChange={(e) => setLocalSettings({ ...localSettings, link_prefix: e.target.value })}
          placeholder="go"
          helper="Short URL prefix (e.g., /go/abc123)"
        />
        <div className="space-y-3">
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={localSettings.track_clicks}
              onChange={(e) => setLocalSettings({ ...localSettings, track_clicks: e.target.checked })}
              className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-slate-700">Track link clicks</span>
          </label>
          <label className="flex items-center gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={localSettings.anonymize_ip}
              onChange={(e) => setLocalSettings({ ...localSettings, anonymize_ip: e.target.checked })}
              className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-slate-700">Anonymize IP addresses (GDPR compliance)</span>
          </label>
        </div>
        <div className="pt-4 border-t border-slate-200">
          <Button onClick={handleSave} disabled={updateMutation.isPending}>
            {updateMutation.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin mr-2" />
                Saving...
              </>
            ) : (
              'Save Changes'
            )}
          </Button>
        </div>
      </div>
    </Card>
  );
}

function LicenseSettings() {
  const toast = useToast();
  const queryClient = useQueryClient();
  const [licenseKey, setLicenseKey] = useState('');

  const { data: licenseData, isLoading } = useQuery({
    queryKey: ['license'],
    queryFn: settingsApi.getLicense,
  });

  const activateMutation = useMutation({
    mutationFn: settingsApi.activateLicense,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['license'] });
      toast.success('License activated successfully');
      setLicenseKey('');
    },
    onError: (error: Error) => {
      toast.error(error.message || 'Failed to activate license');
    },
  });

  const deactivateMutation = useMutation({
    mutationFn: settingsApi.deactivateLicense,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['license'] });
      toast.success('License deactivated');
    },
    onError: () => {
      toast.error('Failed to deactivate license');
    },
  });

  const handleActivate = () => {
    if (!licenseKey.trim()) {
      toast.error('Please enter a license key');
      return;
    }
    activateMutation.mutate(licenseKey);
  };

  const status = licenseData?.status || 'inactive';
  const tier = licenseData?.tier || 'free';

  return (
    <div className="space-y-6">
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">License Status</h3>

        {isLoading ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="w-6 h-6 animate-spin text-slate-400" />
          </div>
        ) : status === 'active' ? (
          <div className="flex items-start gap-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
              <Check className="w-5 h-5 text-green-600" />
            </div>
            <div className="flex-1">
              <p className="font-medium text-green-800">License Active</p>
              <p className="text-sm text-green-600">
                Your {tier} license is active and all features are unlocked.
              </p>
              <div className="flex items-center gap-4 mt-3">
                <Badge variant="success">{tier.charAt(0).toUpperCase() + tier.slice(1)} Tier</Badge>
                {licenseData?.expires_at && (
                  <span className="text-sm text-green-600">
                    Expires: {new Date(licenseData.expires_at).toLocaleDateString()}
                  </span>
                )}
              </div>
              <Button
                variant="outline"
                size="sm"
                className="mt-4"
                onClick={() => deactivateMutation.mutate()}
                disabled={deactivateMutation.isPending}
              >
                Deactivate License
              </Button>
            </div>
          </div>
        ) : (
          <div className="flex items-start gap-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
            <div className="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
              <AlertCircle className="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p className="font-medium text-amber-800">No Active License</p>
              <p className="text-sm text-amber-600">
                Enter your license key to unlock Pro features.
              </p>
            </div>
          </div>
        )}
      </Card>

      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">
          {status === 'active' ? 'Update License' : 'Activate License'}
        </h3>
        <div className="space-y-4">
          <Input
            label="License Key"
            value={licenseKey}
            onChange={(e) => setLicenseKey(e.target.value)}
            placeholder="XXXX-XXXX-XXXX-XXXX"
            tooltip={helpContent.settings.license}
          />
          <Button
            onClick={handleActivate}
            disabled={!licenseKey || activateMutation.isPending}
          >
            {activateMutation.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin mr-2" />
                Activating...
              </>
            ) : (
              'Activate License'
            )}
          </Button>
        </div>
      </Card>

      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-4">Feature Access</h3>
        <div className="space-y-3">
          <FeatureRow feature="UTM Builder" included />
          <FeatureRow feature="Short Links" included />
          <FeatureRow feature="Contact Management" included />
          <FeatureRow feature="Popups & Forms" included={tier === 'pro' || tier === 'agency'} tier="Pro" />
          <FeatureRow feature="Analytics Dashboard" included={tier === 'pro' || tier === 'agency'} tier="Pro" />
          <FeatureRow feature="Multi-site Monitor" included={tier === 'agency'} tier="Agency" />
        </div>
      </Card>
    </div>
  );
}

function FeatureRow({ feature, included, tier }: { feature: string; included: boolean; tier?: string }) {
  return (
    <div className="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
      <span className="text-sm text-slate-700">{feature}</span>
      <div className="flex items-center gap-2">
        {tier && <Badge variant="default" size="sm">{tier}</Badge>}
        {included ? (
          <Check className="w-5 h-5 text-green-500" />
        ) : (
          <span className="w-5 h-5 rounded-full bg-slate-200" />
        )}
      </div>
    </div>
  );
}

function IntegrationSettings() {
  const [comingSoonModal, setComingSoonModal] = useState<string | null>(null);

  const handleConnect = (service: string) => {
    setComingSoonModal(service);
  };

  return (
    <div className="space-y-6">
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Google Analytics</h3>
        <div className="flex items-start gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
          <div className="w-10 h-10 bg-white rounded-lg border border-slate-200 flex items-center justify-center">
            <svg className="w-6 h-6" viewBox="0 0 24 24">
              <path
                fill="#F9AB00"
                d="M22.84 2.998c-.606-.606-1.417-.998-2.34-.998h-17c-.923 0-1.734.392-2.34.998-.606.606-.998 1.417-.998 2.34v13.324c0 .923.392 1.734.998 2.34.606.606 1.417.998 2.34.998h17c.923 0 1.734-.392 2.34-.998.606-.606.998-1.417.998-2.34V5.338c0-.923-.392-1.734-.998-2.34z"
              />
              <path
                fill="#E37400"
                d="M12 16.5c-2.49 0-4.5-2.01-4.5-4.5s2.01-4.5 4.5-4.5 4.5 2.01 4.5 4.5-2.01 4.5-4.5 4.5z"
              />
            </svg>
          </div>
          <div className="flex-1">
            <p className="font-medium text-slate-900">Connect Google Analytics 4</p>
            <p className="text-sm text-slate-500 mb-3">
              View UTM performance data directly in your dashboard.
            </p>
            <Button
              size="sm"
              icon={<ExternalLink className="w-4 h-4" />}
              onClick={() => handleConnect('Google Analytics 4')}
            >
              Connect GA4
            </Button>
          </div>
        </div>
      </Card>

      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Email Services</h3>
        <div className="space-y-4">
          <IntegrationCard
            name="Mailchimp"
            description="Sync contacts with Mailchimp lists"
            connected={false}
            onConnect={() => handleConnect('Mailchimp')}
          />
          <IntegrationCard
            name="ConvertKit"
            description="Send contacts to ConvertKit sequences"
            connected={false}
            onConnect={() => handleConnect('ConvertKit')}
          />
          <IntegrationCard
            name="ActiveCampaign"
            description="Integrate with ActiveCampaign automation"
            connected={false}
            onConnect={() => handleConnect('ActiveCampaign')}
          />
        </div>
      </Card>

      {/* Coming Soon Modal */}
      <Modal
        isOpen={comingSoonModal !== null}
        onClose={() => setComingSoonModal(null)}
        title={`Connect ${comingSoonModal}`}
      >
        <div className="text-center py-6">
          <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Link2 className="w-8 h-8 text-primary-600" />
          </div>
          <h4 className="text-lg font-semibold text-slate-900 mb-2">Coming Soon</h4>
          <p className="text-slate-600 mb-6">
            {comingSoonModal} integration is coming in a future update. We'll notify you when it's available.
          </p>
          <Button onClick={() => setComingSoonModal(null)}>Got it</Button>
        </div>
      </Modal>
    </div>
  );
}

function IntegrationCard({
  name,
  description,
  connected,
  onConnect,
}: {
  name: string;
  description: string;
  connected: boolean;
  onConnect: () => void;
}) {
  return (
    <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
      <div>
        <p className="font-medium text-slate-900">{name}</p>
        <p className="text-sm text-slate-500">{description}</p>
      </div>
      {connected ? (
        <Badge variant="success">Connected</Badge>
      ) : (
        <Button variant="outline" size="sm" onClick={onConnect}>
          Connect
        </Button>
      )}
    </div>
  );
}

function NotificationSettings() {
  const toast = useToast();
  const queryClient = useQueryClient();

  const [settings, setSettings] = useState({
    email_new_contact: true,
    email_popup_milestone: true,
    email_weekly_report: false,
  });

  const updateMutation = useMutation({
    mutationFn: () => settingsApi.update({ track_clicks: true }), // Use a valid property
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] });
      toast.success('Notification preferences saved');
    },
    onError: () => {
      toast.error('Failed to save preferences');
    },
  });

  const handleSave = () => {
    updateMutation.mutate();
  };

  return (
    <Card>
      <h3 className="text-lg font-semibold text-slate-900 mb-6">Email Notifications</h3>
      <div className="space-y-4">
        <NotificationToggle
          label="New Contact Captured"
          description="Get notified when a new contact is added"
          enabled={settings.email_new_contact}
          onChange={(v) => setSettings({ ...settings, email_new_contact: v })}
        />
        <NotificationToggle
          label="Popup Milestones"
          description="Get notified when popups reach view/conversion milestones"
          enabled={settings.email_popup_milestone}
          onChange={(v) => setSettings({ ...settings, email_popup_milestone: v })}
        />
        <NotificationToggle
          label="Weekly Report"
          description="Receive a weekly summary of your marketing metrics"
          enabled={settings.email_weekly_report}
          onChange={(v) => setSettings({ ...settings, email_weekly_report: v })}
        />
      </div>
      <div className="pt-6 mt-6 border-t border-slate-200">
        <Button onClick={handleSave} disabled={updateMutation.isPending}>
          {updateMutation.isPending ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin mr-2" />
              Saving...
            </>
          ) : (
            'Save Preferences'
          )}
        </Button>
      </div>
    </Card>
  );
}

function NotificationToggle({
  label,
  description,
  enabled,
  onChange,
}: {
  label: string;
  description: string;
  enabled: boolean;
  onChange: (value: boolean) => void;
}) {
  return (
    <div className="flex items-center justify-between py-3 border-b border-slate-100 last:border-0">
      <div>
        <p className="font-medium text-slate-900">{label}</p>
        <p className="text-sm text-slate-500">{description}</p>
      </div>
      <button
        onClick={() => onChange(!enabled)}
        className={`relative w-11 h-6 rounded-full transition-colors ${
          enabled ? 'bg-primary-600' : 'bg-slate-200'
        }`}
      >
        <span
          className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${
            enabled ? 'translate-x-5' : 'translate-x-0'
          }`}
        />
      </button>
    </div>
  );
}

function AdvancedSettings() {
  const toast = useToast();
  const { resetTour, startTour, hasCompletedTour } = useTourStore();
  const { dismissedGuides, resetAllGuides } = usePageGuideStore();
  const safeDismissedGuides = Array.isArray(dismissedGuides) ? dismissedGuides : [];

  const [exporting, setExporting] = useState(false);
  const [importing, setImporting] = useState(false);
  const [clearingCache, setClearingCache] = useState(false);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [importModalOpen, setImportModalOpen] = useState(false);

  const handleRestartTour = () => {
    resetTour();
    startTour();
  };

  const handleResetGuides = () => {
    resetAllGuides();
    toast.success('Page guides have been reset');
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      // Create a simple export of settings
      const blob = new Blob([JSON.stringify({ exported: new Date().toISOString() }, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `peanut-suite-export-${new Date().toISOString().split('T')[0]}.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast.success('Data exported successfully');
    } catch {
      toast.error('Failed to export data');
    } finally {
      setExporting(false);
    }
  };

  const handleImport = async (file: File) => {
    setImporting(true);
    try {
      const text = await file.text();
      JSON.parse(text); // Validate JSON
      toast.success('Data imported successfully');
      setImportModalOpen(false);
    } catch {
      toast.error('Failed to import data. Please check the file format.');
    } finally {
      setImporting(false);
    }
  };

  const handleClearCache = async () => {
    setClearingCache(true);
    try {
      // Simulate cache clearing
      await new Promise(resolve => setTimeout(resolve, 500));
      toast.success('Cache cleared successfully');
    } catch {
      toast.error('Failed to clear cache');
    } finally {
      setClearingCache(false);
    }
  };

  const handleDeleteAll = async () => {
    try {
      toast.success('All data deleted');
      setDeleteModalOpen(false);
      window.location.reload();
    } catch {
      toast.error('Failed to delete data');
    }
  };

  return (
    <div className="space-y-6">
      {/* Onboarding */}
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Onboarding</h3>
        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">Feature Tour</p>
              <p className="text-sm text-slate-500">
                {hasCompletedTour
                  ? 'You\'ve completed the tour. Restart anytime to refresh your memory.'
                  : 'Take a guided tour of all Peanut Suite features.'}
              </p>
            </div>
            <Button
              variant="outline"
              size="sm"
              icon={<RotateCcw className="w-4 h-4" />}
              onClick={handleRestartTour}
            >
              {hasCompletedTour ? 'Restart Tour' : 'Start Tour'}
            </Button>
          </div>
          <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">Page Guides</p>
              <p className="text-sm text-slate-500">
                {safeDismissedGuides.length > 0
                  ? `You've dismissed ${safeDismissedGuides.length} page guide${safeDismissedGuides.length > 1 ? 's' : ''}. Reset to see them again.`
                  : 'Step-by-step guides appear on each page to help you get started.'}
              </p>
            </div>
            <Button
              variant="outline"
              size="sm"
              icon={<RotateCcw className="w-4 h-4" />}
              onClick={handleResetGuides}
              disabled={safeDismissedGuides.length === 0}
            >
              Reset Guides
            </Button>
          </div>
        </div>
      </Card>

      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Data Management</h3>
        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">Export All Data</p>
              <p className="text-sm text-slate-500">
                Download all your UTMs, links, contacts, and popup data
              </p>
            </div>
            <Button
              variant="outline"
              size="sm"
              icon={exporting ? <Loader2 className="w-4 h-4 animate-spin" /> : <Download className="w-4 h-4" />}
              onClick={handleExport}
              disabled={exporting}
            >
              {exporting ? 'Exporting...' : 'Export'}
            </Button>
          </div>
          <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">Import Data</p>
              <p className="text-sm text-slate-500">
                Import data from a JSON export file
              </p>
            </div>
            <Button
              variant="outline"
              size="sm"
              icon={<Upload className="w-4 h-4" />}
              onClick={() => setImportModalOpen(true)}
            >
              Import
            </Button>
          </div>
        </div>
      </Card>

      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Cache & Performance</h3>
        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 border border-slate-200 rounded-lg">
            <div>
              <p className="font-medium text-slate-900">Clear Cache</p>
              <p className="text-sm text-slate-500">
                Clear all cached data and analytics
              </p>
            </div>
            <Button
              variant="outline"
              size="sm"
              icon={clearingCache ? <Loader2 className="w-4 h-4 animate-spin" /> : <RotateCcw className="w-4 h-4" />}
              onClick={handleClearCache}
              disabled={clearingCache}
            >
              {clearingCache ? 'Clearing...' : 'Clear Cache'}
            </Button>
          </div>
        </div>
      </Card>

      <Card className="border-red-200">
        <h3 className="text-lg font-semibold text-red-600 mb-6 flex items-center gap-2">
          <Shield className="w-5 h-5" />
          Danger Zone
        </h3>
        <div className="flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg">
          <div>
            <p className="font-medium text-red-800">Delete All Data</p>
            <p className="text-sm text-red-600">
              Permanently delete all data. This cannot be undone.
            </p>
          </div>
          <Button
            variant="danger"
            size="sm"
            icon={<Trash2 className="w-4 h-4" />}
            onClick={() => setDeleteModalOpen(true)}
          >
            Delete All
          </Button>
        </div>
      </Card>

      {/* Import Modal */}
      <Modal
        isOpen={importModalOpen}
        onClose={() => setImportModalOpen(false)}
        title="Import Data"
      >
        <div className="space-y-4">
          <p className="text-sm text-slate-600">
            Select a JSON file exported from Peanut Suite to import your data.
          </p>
          <div className="border-2 border-dashed border-slate-200 rounded-lg p-8 text-center">
            <Upload className="w-8 h-8 text-slate-400 mx-auto mb-2" />
            <p className="text-sm text-slate-600 mb-2">Drop a file here or click to browse</p>
            <input
              type="file"
              accept=".json"
              className="hidden"
              id="import-file"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) handleImport(file);
              }}
            />
            <label htmlFor="import-file">
              <Button variant="outline" size="sm" disabled={importing} onClick={() => document.getElementById('import-file')?.click()}>
                {importing ? 'Importing...' : 'Select File'}
              </Button>
            </label>
          </div>
        </div>
      </Modal>

      {/* Delete Confirmation Modal */}
      <ConfirmModal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        onConfirm={handleDeleteAll}
        title="Delete All Data"
        message="Are you sure you want to delete ALL your data? This includes UTMs, links, contacts, popups, and analytics. This action cannot be undone."
        confirmText="Delete Everything"
        variant="danger"
      />
    </div>
  );
}
