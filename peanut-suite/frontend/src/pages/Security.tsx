import { useState, createContext, useContext } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Shield,
  Lock,
  Key,
  UserX,
  Bell,
  AlertTriangle,
  Check,
  X,
  RefreshCw,
  Copy,
  Eye,
  EyeOff,
  Unlock,
  Plus,
  Trash2,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Badge, useToast, SampleDataBanner } from '../components/common';
import { securityApi } from '../api/endpoints';
import { pageDescriptions, sampleSecuritySettings, sampleLoginAttempts, sampleLockouts } from '../constants';

// Context to share sample data state across components
const SampleDataContext = createContext<{ showSampleData: boolean; displaySampleData: boolean; setShowSampleData: (v: boolean) => void }>({
  showSampleData: true,
  displaySampleData: false,
  setShowSampleData: () => {},
});

type Tab = 'login-protection' | 'attempts' | 'ip-management' | '2fa' | 'notifications';

export default function Security() {
  const [activeTab, setActiveTab] = useState<Tab>('login-protection');
  const [showSampleData, setShowSampleData] = useState(true);

  // Check if we have real data by querying settings
  const { data: settings, isLoading: settingsLoading } = useQuery({
    queryKey: ['security-settings'],
    queryFn: securityApi.getSettings,
  });

  const { data: attempts, isLoading: attemptsLoading } = useQuery({
    queryKey: ['login-attempts'],
    queryFn: securityApi.getLoginAttempts,
  });

  // Determine if we should show sample data
  const hasNoRealData = !settingsLoading && !attemptsLoading &&
    (!settings || (!settings.hide_login_enabled && !settings.limit_login_enabled && !settings['2fa_enabled'])) &&
    (!attempts || attempts.length === 0);
  const displaySampleData = hasNoRealData && showSampleData;

  const tabs = [
    { id: 'login-protection' as Tab, label: 'Login Protection', icon: Lock },
    { id: 'attempts' as Tab, label: 'Login Attempts', icon: UserX },
    { id: 'ip-management' as Tab, label: 'IP Management', icon: Shield },
    { id: '2fa' as Tab, label: 'Two-Factor Auth', icon: Key },
    { id: 'notifications' as Tab, label: 'Notifications', icon: Bell },
  ];

  const pageInfo = pageDescriptions.security || {
    title: 'Security',
    description: 'Protect your WordPress login and admin area',
    howTo: ['Configure login URL hiding', 'Set up login attempt limits', 'Manage IP whitelist/blacklist'],
    tips: ['Use a unique login slug that\'s hard to guess', 'Keep your IP address whitelisted'],
    useCases: ['Protect against brute force attacks', 'Hide wp-login.php from bots'],
  };

  return (
    <SampleDataContext.Provider value={{ showSampleData, displaySampleData, setShowSampleData }}>
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={{ howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases }} pageGuideId="security">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

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
          {activeTab === 'login-protection' && <LoginProtection />}
          {activeTab === 'attempts' && <LoginAttempts />}
          {activeTab === 'ip-management' && <IPManagement />}
          {activeTab === '2fa' && <TwoFactorAuth />}
          {activeTab === 'notifications' && <NotificationSettings />}
        </div>
      </div>
    </Layout>
    </SampleDataContext.Provider>
  );
}

function LoginProtection() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [showSlug, setShowSlug] = useState(false);
  const { displaySampleData } = useContext(SampleDataContext);

  const { data: settings, isLoading } = useQuery({
    queryKey: ['security-settings'],
    queryFn: securityApi.getSettings,
  });

  // Use sample data if needed
  const effectiveSettings = displaySampleData ? sampleSecuritySettings : settings;

  const [formData, setFormData] = useState({
    hide_login_enabled: false,
    login_slug: '',
    redirect_slug: '',
    limit_login_enabled: false,
    max_attempts: 5,
    lockout_duration: 30,
    lockout_increment: true,
  });

  // Update form when settings load
  useState(() => {
    if (effectiveSettings) {
      setFormData({
        hide_login_enabled: effectiveSettings.hide_login_enabled,
        login_slug: effectiveSettings.login_slug,
        redirect_slug: effectiveSettings.redirect_slug,
        limit_login_enabled: effectiveSettings.limit_login_enabled,
        max_attempts: effectiveSettings.max_attempts,
        lockout_duration: effectiveSettings.lockout_duration,
        lockout_increment: effectiveSettings.lockout_increment,
      });
    }
  });

  const saveMutation = useMutation({
    mutationFn: securityApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-settings'] });
      toast.success('Security settings saved');
    },
    onError: () => {
      toast.error('Failed to save settings');
    },
  });

  const handleSave = () => {
    saveMutation.mutate(formData);
  };

  const copyLoginUrl = () => {
    const url = `${window.location.origin}/${formData.login_slug}`;
    navigator.clipboard.writeText(url);
    toast.success('Login URL copied to clipboard');
  };

  if (isLoading) {
    return <Card><div className="animate-pulse h-64 bg-slate-100 rounded-lg" /></Card>;
  }

  return (
    <div className="space-y-6">
      {/* Hide Login URL */}
      <Card>
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Hide Login URL</h3>
            <p className="text-sm text-slate-500">Replace wp-login.php with a custom URL</p>
          </div>
          <Toggle
            enabled={formData.hide_login_enabled}
            onChange={(v) => setFormData({ ...formData, hide_login_enabled: v })}
          />
        </div>

        {formData.hide_login_enabled && (
          <div className="space-y-4 pt-4 border-t border-slate-200">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1.5">
                Custom Login Slug
              </label>
              <div className="flex gap-2">
                <div className="flex-1 relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                    {window.location.origin}/
                  </span>
                  <Input
                    type={showSlug ? 'text' : 'password'}
                    value={formData.login_slug}
                    onChange={(e) => setFormData({ ...formData, login_slug: e.target.value })}
                    className="pl-[140px]"
                    placeholder="my-secret-login"
                  />
                </div>
                <Button
                  variant="outline"
                  icon={showSlug ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                  onClick={() => setShowSlug(!showSlug)}
                />
                <Button
                  variant="outline"
                  icon={<Copy className="w-4 h-4" />}
                  onClick={copyLoginUrl}
                />
              </div>
              <p className="text-xs text-slate-500 mt-1">
                This will be your new login URL. Keep it secret!
              </p>
            </div>

            <Input
              label="Redirect Slug (404 page)"
              value={formData.redirect_slug}
              onChange={(e) => setFormData({ ...formData, redirect_slug: e.target.value })}
              placeholder="not-found"
              helper="Users accessing wp-login.php directly will be redirected here"
            />

            <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg">
              <div className="flex gap-3">
                <AlertTriangle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="font-medium text-amber-800">Important</p>
                  <p className="text-sm text-amber-700">
                    Make sure to bookmark your new login URL before enabling this feature.
                    If you get locked out, you can disable this by adding{' '}
                    <code className="bg-amber-100 px-1 rounded">define('PEANUT_DISABLE_LOGIN_HIDE', true);</code>{' '}
                    to your wp-config.php file.
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}
      </Card>

      {/* Login Limiting */}
      <Card>
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Limit Login Attempts</h3>
            <p className="text-sm text-slate-500">Block IPs after too many failed login attempts</p>
          </div>
          <Toggle
            enabled={formData.limit_login_enabled}
            onChange={(v) => setFormData({ ...formData, limit_login_enabled: v })}
          />
        </div>

        {formData.limit_login_enabled && (
          <div className="space-y-4 pt-4 border-t border-slate-200">
            <div className="grid grid-cols-2 gap-4">
              <Input
                type="number"
                label="Max Attempts"
                value={formData.max_attempts}
                onChange={(e) => setFormData({ ...formData, max_attempts: parseInt(e.target.value) })}
                min={1}
                max={20}
              />
              <Input
                type="number"
                label="Lockout Duration (minutes)"
                value={formData.lockout_duration}
                onChange={(e) => setFormData({ ...formData, lockout_duration: parseInt(e.target.value) })}
                min={5}
                max={1440}
              />
            </div>

            <div className="flex items-center justify-between py-3 border-t border-slate-100">
              <div>
                <p className="font-medium text-slate-900">Progressive Lockout</p>
                <p className="text-sm text-slate-500">
                  Increase lockout duration with each subsequent block
                </p>
              </div>
              <Toggle
                enabled={formData.lockout_increment}
                onChange={(v) => setFormData({ ...formData, lockout_increment: v })}
              />
            </div>
          </div>
        )}
      </Card>

      <div className="flex justify-end">
        <Button onClick={handleSave} loading={saveMutation.isPending}>
          Save Settings
        </Button>
      </div>
    </div>
  );
}

function LoginAttempts() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const { displaySampleData } = useContext(SampleDataContext);

  const { data: attempts, isLoading: attemptsLoading } = useQuery({
    queryKey: ['login-attempts'],
    queryFn: securityApi.getLoginAttempts,
  });

  const { data: lockouts, isLoading: lockoutsLoading } = useQuery({
    queryKey: ['lockouts'],
    queryFn: securityApi.getLockouts,
  });

  // Use sample data if needed
  const displayAttempts = displaySampleData ? sampleLoginAttempts : (attempts || []);
  const displayLockouts = displaySampleData ? sampleLockouts : (lockouts || []);

  const unlockMutation = useMutation({
    mutationFn: securityApi.unlockIp,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lockouts'] });
      toast.success('IP address unlocked');
    },
    onError: () => {
      toast.error('Failed to unlock IP');
    },
  });

  return (
    <div className="space-y-6">
      {/* Active Lockouts */}
      <Card>
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-slate-900">Active Lockouts</h3>
          <Button
            variant="outline"
            size="sm"
            icon={<RefreshCw className="w-4 h-4" />}
            onClick={() => queryClient.invalidateQueries({ queryKey: ['lockouts'] })}
          >
            Refresh
          </Button>
        </div>

        {lockoutsLoading ? (
          <div className="animate-pulse h-24 bg-slate-100 rounded-lg" />
        ) : displayLockouts.length === 0 ? (
          <div className="text-center py-8 text-slate-500">
            <Shield className="w-12 h-12 mx-auto mb-3 text-slate-300" />
            <p>No active lockouts</p>
          </div>
        ) : (
          <div className="space-y-2">
            {displayLockouts.map((lockout) => (
              <div
                key={lockout.id}
                className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg"
              >
                <div>
                  <p className="font-medium text-red-800">{lockout.ip_address}</p>
                  <p className="text-sm text-red-600">
                    {lockout.attempts} failed attempts • Locked until{' '}
                    {new Date(lockout.lockout_until).toLocaleString()}
                  </p>
                </div>
                {!displaySampleData && (
                  <Button
                    variant="outline"
                    size="sm"
                    icon={<Unlock className="w-4 h-4" />}
                    onClick={() => unlockMutation.mutate(lockout.ip_address)}
                    loading={unlockMutation.isPending}
                  >
                    Unlock
                  </Button>
                )}
              </div>
            ))}
          </div>
        )}
      </Card>

      {/* Recent Attempts */}
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-4">Recent Login Attempts</h3>

        {attemptsLoading ? (
          <div className="animate-pulse h-48 bg-slate-100 rounded-lg" />
        ) : displayAttempts.length === 0 ? (
          <div className="text-center py-8 text-slate-500">
            <UserX className="w-12 h-12 mx-auto mb-3 text-slate-300" />
            <p>No login attempts recorded</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-200">
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Time</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">IP Address</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Username</th>
                  <th className="text-left py-3 px-4 text-sm font-medium text-slate-500">Status</th>
                </tr>
              </thead>
              <tbody>
                {displayAttempts.map((attempt) => (
                  <tr key={attempt.id} className="border-b border-slate-100">
                    <td className="py-3 px-4 text-sm text-slate-600">
                      {new Date(attempt.attempt_time).toLocaleString()}
                    </td>
                    <td className="py-3 px-4 text-sm font-mono text-slate-900">
                      {attempt.ip_address}
                    </td>
                    <td className="py-3 px-4 text-sm text-slate-600">
                      {attempt.username}
                    </td>
                    <td className="py-3 px-4">
                      {attempt.status === 'success' ? (
                        <Badge variant="success">
                          <Check className="w-3 h-3 mr-1" />
                          Success
                        </Badge>
                      ) : (
                        <Badge variant="danger">
                          <X className="w-3 h-3 mr-1" />
                          Failed
                        </Badge>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}

function IPManagement() {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [newWhitelistIp, setNewWhitelistIp] = useState('');
  const [newBlacklistIp, setNewBlacklistIp] = useState('');

  const { data: settings } = useQuery({
    queryKey: ['security-settings'],
    queryFn: securityApi.getSettings,
  });

  const [whitelist, setWhitelist] = useState<string[]>([]);
  const [blacklist, setBlacklist] = useState<string[]>([]);

  // Update lists when settings load
  useState(() => {
    if (settings) {
      setWhitelist(settings.ip_whitelist || []);
      setBlacklist(settings.ip_blacklist || []);
    }
  });

  const saveMutation = useMutation({
    mutationFn: securityApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-settings'] });
      toast.success('IP lists saved');
    },
    onError: () => {
      toast.error('Failed to save IP lists');
    },
  });

  const addToWhitelist = () => {
    if (newWhitelistIp && !whitelist.includes(newWhitelistIp)) {
      setWhitelist([...whitelist, newWhitelistIp]);
      setNewWhitelistIp('');
    }
  };

  const addToBlacklist = () => {
    if (newBlacklistIp && !blacklist.includes(newBlacklistIp)) {
      setBlacklist([...blacklist, newBlacklistIp]);
      setNewBlacklistIp('');
    }
  };

  const handleSave = () => {
    saveMutation.mutate({
      ip_whitelist: whitelist,
      ip_blacklist: blacklist,
    });
  };

  return (
    <div className="space-y-6">
      {/* Whitelist */}
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-2">IP Whitelist</h3>
        <p className="text-sm text-slate-500 mb-4">
          These IPs will never be blocked, even after failed login attempts.
        </p>

        <div className="flex gap-2 mb-4">
          <Input
            placeholder="192.168.1.1 or 192.168.1.0/24"
            value={newWhitelistIp}
            onChange={(e) => setNewWhitelistIp(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && addToWhitelist()}
          />
          <Button icon={<Plus className="w-4 h-4" />} onClick={addToWhitelist}>
            Add
          </Button>
        </div>

        {whitelist.length > 0 ? (
          <div className="space-y-2">
            {whitelist.map((ip) => (
              <div
                key={ip}
                className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg"
              >
                <span className="font-mono text-sm text-green-800">{ip}</span>
                <button
                  onClick={() => setWhitelist(whitelist.filter((i) => i !== ip))}
                  className="p-1 text-green-600 hover:text-green-800"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-slate-400 italic">No IPs whitelisted</p>
        )}
      </Card>

      {/* Blacklist */}
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-2">IP Blacklist</h3>
        <p className="text-sm text-slate-500 mb-4">
          These IPs will be permanently blocked from the login page.
        </p>

        <div className="flex gap-2 mb-4">
          <Input
            placeholder="192.168.1.1 or 192.168.1.0/24"
            value={newBlacklistIp}
            onChange={(e) => setNewBlacklistIp(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && addToBlacklist()}
          />
          <Button icon={<Plus className="w-4 h-4" />} onClick={addToBlacklist}>
            Add
          </Button>
        </div>

        {blacklist.length > 0 ? (
          <div className="space-y-2">
            {blacklist.map((ip) => (
              <div
                key={ip}
                className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg"
              >
                <span className="font-mono text-sm text-red-800">{ip}</span>
                <button
                  onClick={() => setBlacklist(blacklist.filter((i) => i !== ip))}
                  className="p-1 text-red-600 hover:text-red-800"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-slate-400 italic">No IPs blacklisted</p>
        )}
      </Card>

      <div className="flex justify-end">
        <Button onClick={handleSave} loading={saveMutation.isPending}>
          Save IP Lists
        </Button>
      </div>
    </div>
  );
}

function TwoFactorAuth() {
  const queryClient = useQueryClient();
  const toast = useToast();

  const { data: settings, isLoading } = useQuery({
    queryKey: ['security-settings'],
    queryFn: securityApi.getSettings,
  });

  const [formData, setFormData] = useState({
    '2fa_enabled': false,
    '2fa_method': 'email' as 'email' | 'totp',
    '2fa_roles': [] as string[],
  });

  const roles = [
    { value: 'administrator', label: 'Administrator' },
    { value: 'editor', label: 'Editor' },
    { value: 'author', label: 'Author' },
    { value: 'contributor', label: 'Contributor' },
  ];

  // Update form when settings load
  useState(() => {
    if (settings) {
      setFormData({
        '2fa_enabled': settings['2fa_enabled'],
        '2fa_method': settings['2fa_method'],
        '2fa_roles': settings['2fa_roles'] || [],
      });
    }
  });

  const saveMutation = useMutation({
    mutationFn: securityApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-settings'] });
      toast.success('2FA settings saved');
    },
    onError: () => {
      toast.error('Failed to save settings');
    },
  });

  const toggleRole = (role: string) => {
    const roles = formData['2fa_roles'];
    if (roles.includes(role)) {
      setFormData({ ...formData, '2fa_roles': roles.filter((r) => r !== role) });
    } else {
      setFormData({ ...formData, '2fa_roles': [...roles, role] });
    }
  };

  if (isLoading) {
    return <Card><div className="animate-pulse h-64 bg-slate-100 rounded-lg" /></Card>;
  }

  return (
    <div className="space-y-6">
      <Card>
        <div className="flex items-center justify-between mb-6">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Two-Factor Authentication</h3>
            <p className="text-sm text-slate-500">Add an extra layer of security to user logins</p>
          </div>
          <Toggle
            enabled={formData['2fa_enabled']}
            onChange={(v) => setFormData({ ...formData, '2fa_enabled': v })}
          />
        </div>

        {formData['2fa_enabled'] && (
          <div className="space-y-6 pt-4 border-t border-slate-200">
            {/* Method Selection */}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-3">
                Authentication Method
              </label>
              <div className="grid grid-cols-2 gap-4">
                <button
                  onClick={() => setFormData({ ...formData, '2fa_method': 'email' })}
                  className={`p-4 border-2 rounded-lg text-left transition-colors ${
                    formData['2fa_method'] === 'email'
                      ? 'border-primary-500 bg-primary-50'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <p className="font-medium text-slate-900">Email Code</p>
                  <p className="text-sm text-slate-500">Send a code via email</p>
                </button>
                <button
                  onClick={() => setFormData({ ...formData, '2fa_method': 'totp' })}
                  className={`p-4 border-2 rounded-lg text-left transition-colors ${
                    formData['2fa_method'] === 'totp'
                      ? 'border-primary-500 bg-primary-50'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <p className="font-medium text-slate-900">Authenticator App</p>
                  <p className="text-sm text-slate-500">Use Google/Microsoft Authenticator</p>
                </button>
              </div>
            </div>

            {/* Role Selection */}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-3">
                Require 2FA for Roles
              </label>
              <div className="space-y-2">
                {roles.map((role) => (
                  <label
                    key={role.value}
                    className="flex items-center gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50"
                  >
                    <input
                      type="checkbox"
                      checked={formData['2fa_roles'].includes(role.value)}
                      onChange={() => toggleRole(role.value)}
                      className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span className="text-slate-900">{role.label}</span>
                  </label>
                ))}
              </div>
            </div>
          </div>
        )}
      </Card>

      <div className="flex justify-end">
        <Button onClick={() => saveMutation.mutate(formData)} loading={saveMutation.isPending}>
          Save 2FA Settings
        </Button>
      </div>
    </div>
  );
}

function NotificationSettings() {
  const queryClient = useQueryClient();
  const toast = useToast();

  const { data: settings, isLoading } = useQuery({
    queryKey: ['security-settings'],
    queryFn: securityApi.getSettings,
  });

  const [formData, setFormData] = useState({
    notify_login_success: false,
    notify_login_failed: true,
    notify_lockout: true,
    notify_email: '',
  });

  // Update form when settings load
  useState(() => {
    if (settings) {
      setFormData({
        notify_login_success: settings.notify_login_success,
        notify_login_failed: settings.notify_login_failed,
        notify_lockout: settings.notify_lockout,
        notify_email: settings.notify_email,
      });
    }
  });

  const saveMutation = useMutation({
    mutationFn: securityApi.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['security-settings'] });
      toast.success('Notification settings saved');
    },
    onError: () => {
      toast.error('Failed to save settings');
    },
  });

  if (isLoading) {
    return <Card><div className="animate-pulse h-64 bg-slate-100 rounded-lg" /></Card>;
  }

  return (
    <div className="space-y-6">
      <Card>
        <h3 className="text-lg font-semibold text-slate-900 mb-6">Security Notifications</h3>

        <div className="space-y-4">
          <Input
            type="email"
            label="Notification Email"
            value={formData.notify_email}
            onChange={(e) => setFormData({ ...formData, notify_email: e.target.value })}
            placeholder="admin@example.com"
            helper="Leave empty to use the admin email"
          />

          <div className="pt-4 border-t border-slate-200 space-y-4">
            <NotificationToggle
              label="Successful Logins"
              description="Get notified when someone logs in successfully"
              enabled={formData.notify_login_success}
              onChange={(v) => setFormData({ ...formData, notify_login_success: v })}
            />
            <NotificationToggle
              label="Failed Login Attempts"
              description="Get notified when a login attempt fails"
              enabled={formData.notify_login_failed}
              onChange={(v) => setFormData({ ...formData, notify_login_failed: v })}
            />
            <NotificationToggle
              label="IP Lockouts"
              description="Get notified when an IP address is locked out"
              enabled={formData.notify_lockout}
              onChange={(v) => setFormData({ ...formData, notify_lockout: v })}
            />
          </div>
        </div>
      </Card>

      <div className="flex justify-end">
        <Button onClick={() => saveMutation.mutate(formData)} loading={saveMutation.isPending}>
          Save Notification Settings
        </Button>
      </div>
    </div>
  );
}

// Reusable Toggle Component
function Toggle({ enabled, onChange }: { enabled: boolean; onChange: (value: boolean) => void }) {
  return (
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
  );
}

// Reusable Notification Toggle
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
      <Toggle enabled={enabled} onChange={onChange} />
    </div>
  );
}
