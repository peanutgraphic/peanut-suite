import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  RefreshCw,
  ExternalLink,
  Server,
  Cpu,
  HardDrive,
  MemoryStick,
  Globe,
  Shield,
  CheckCircle,
  XCircle,
  Clock,
  Activity,
  Settings,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Badge, Input } from '../components/common';
import { serversApi } from '../api/endpoints';
import type { HealthGrade, ServerHealthHistory, PleskDomain, PleskService } from '../types';

// Grade badge configuration
const GRADE_CONFIG: Record<HealthGrade, { color: string; bgColor: string }> = {
  A: { color: 'text-green-600', bgColor: 'bg-green-100' },
  B: { color: 'text-blue-600', bgColor: 'bg-blue-100' },
  C: { color: 'text-yellow-600', bgColor: 'bg-yellow-100' },
  D: { color: 'text-orange-600', bgColor: 'bg-orange-100' },
  F: { color: 'text-red-600', bgColor: 'bg-red-100' },
};

function GradeCircle({ grade, score }: { grade: HealthGrade; score: number }) {
  const config = GRADE_CONFIG[grade] || GRADE_CONFIG.F;
  return (
    <div className="flex flex-col items-center">
      <div className={`w-24 h-24 rounded-full ${config.bgColor} flex items-center justify-center`}>
        <span className={`text-4xl font-bold ${config.color}`}>{grade}</span>
      </div>
      <span className="mt-2 text-lg font-medium text-slate-700">{score}/100</span>
    </div>
  );
}

function MetricCard({
  icon: Icon,
  label,
  value,
  subValue,
  status,
}: {
  icon: typeof Cpu;
  label: string;
  value: string | number;
  subValue?: string;
  status?: 'ok' | 'warning' | 'critical';
}) {
  const statusColors = {
    ok: 'text-green-600 bg-green-100',
    warning: 'text-amber-600 bg-amber-100',
    critical: 'text-red-600 bg-red-100',
  };

  return (
    <Card className="p-4">
      <div className="flex items-start justify-between">
        <div className={`p-2 rounded-lg ${status ? statusColors[status] : 'bg-slate-100 text-slate-600'}`}>
          <Icon className="w-5 h-5" />
        </div>
        {status && (
          <Badge variant={status === 'ok' ? 'success' : status === 'warning' ? 'warning' : 'danger'}>
            {status === 'ok' ? 'Good' : status === 'warning' ? 'Warning' : 'Critical'}
          </Badge>
        )}
      </div>
      <p className="mt-3 text-2xl font-bold text-slate-900">{value}</p>
      <p className="text-sm text-slate-500">{label}</p>
      {subValue && <p className="text-xs text-slate-400 mt-1">{subValue}</p>}
    </Card>
  );
}

type TabType = 'overview' | 'domains' | 'services' | 'health' | 'settings';

export default function ServerDetail() {
  const { id } = useParams<{ id: string }>();
  const serverId = parseInt(id || '0');
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [serverName, setServerName] = useState('');

  const { data: server, isLoading } = useQuery({
    queryKey: ['server', serverId],
    queryFn: () => serversApi.getServer(serverId),
    enabled: serverId > 0,
  });

  const { data: healthHistory } = useQuery({
    queryKey: ['server-health', serverId],
    queryFn: () => serversApi.getHealthHistory(serverId, 30),
    enabled: serverId > 0 && activeTab === 'health',
  });

  const { data: domainsData } = useQuery({
    queryKey: ['server-domains', serverId],
    queryFn: () => serversApi.getDomains(serverId),
    enabled: serverId > 0 && activeTab === 'domains',
  });

  const { data: servicesData } = useQuery({
    queryKey: ['server-services', serverId],
    queryFn: () => serversApi.getServices(serverId),
    enabled: serverId > 0 && activeTab === 'services',
  });

  const refreshMutation = useMutation({
    mutationFn: () => serversApi.checkHealth(serverId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['server', serverId] });
      queryClient.invalidateQueries({ queryKey: ['server-health', serverId] });
    },
  });

  const updateMutation = useMutation({
    mutationFn: (data: { server_name: string }) => serversApi.updateServer(serverId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['server', serverId] });
    },
  });

  if (isLoading) {
    return (
      <Layout title="Server Details">
        <div className="flex items-center justify-center py-12">
          <RefreshCw className="w-8 h-8 text-primary-500 animate-spin" />
        </div>
      </Layout>
    );
  }

  if (!server) {
    return (
      <Layout title="Server Not Found">
        <Card className="p-8 text-center">
          <Server className="w-12 h-12 text-slate-300 mx-auto mb-4" />
          <p className="text-slate-500">Server not found</p>
          <Link to="/servers" className="text-primary-600 hover:underline mt-2 inline-block">
            Back to Servers
          </Link>
        </Card>
      </Layout>
    );
  }

  const health = server.last_health;
  const checks = health?.checks || {};

  const tabs: { id: TabType; label: string; icon: typeof Activity }[] = [
    { id: 'overview', label: 'Overview', icon: Activity },
    { id: 'domains', label: 'Domains', icon: Globe },
    { id: 'services', label: 'Services', icon: Server },
    { id: 'health', label: 'Health History', icon: Clock },
    { id: 'settings', label: 'Settings', icon: Settings },
  ];

  return (
    <Layout title={server.server_name || server.server_host}>
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div className="flex items-center gap-4">
          <Link
            to="/servers"
            className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
          >
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-slate-900">
              {server.server_name || server.server_host}
            </h1>
            <p className="text-sm text-slate-500">{server.server_host}:{server.server_port}</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button
            variant="secondary"
            icon={<RefreshCw className={`w-4 h-4 ${refreshMutation.isPending ? 'animate-spin' : ''}`} />}
            onClick={() => refreshMutation.mutate()}
            disabled={refreshMutation.isPending}
          >
            Refresh
          </Button>
          <Button
            variant="secondary"
            icon={<ExternalLink className="w-4 h-4" />}
            onClick={() => window.open(`https://${server.server_host}:${server.server_port}`, '_blank')}
          >
            Open Plesk
          </Button>
        </div>
      </div>

      {/* Health Score Card */}
      {health && (
        <Card className="p-6 mb-6">
          <div className="flex flex-col md:flex-row md:items-center gap-6">
            <GradeCircle grade={health.grade} score={health.score} />
            <div className="flex-1">
              <h3 className="text-lg font-semibold text-slate-900 mb-2">Server Health</h3>
              <p className="text-slate-600 mb-3">
                {health.status === 'healthy' && 'Your server is running smoothly with no issues detected.'}
                {health.status === 'warning' && 'Some metrics need attention but the server is operational.'}
                {health.status === 'critical' && 'Critical issues detected. Immediate attention required.'}
              </p>
              <div className="flex flex-wrap gap-2">
                <Badge variant={server.status === 'active' ? 'success' : 'danger'}>
                  {server.status}
                </Badge>
                {server.plesk_version && (
                  <Badge variant="info">Plesk {server.plesk_version}</Badge>
                )}
                {server.os_info && (
                  <Badge variant="info">{server.os_info}</Badge>
                )}
              </div>
            </div>
          </div>
        </Card>
      )}

      {/* Tabs */}
      <div className="border-b border-slate-200 mb-6">
        <div className="flex gap-1 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                activeTab === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
              }`}
            >
              <tab.icon className="w-4 h-4" />
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <MetricCard
            icon={Cpu}
            label="CPU Usage"
            value={`${checks.cpu_usage?.value || 0}%`}
            status={checks.cpu_usage?.status}
          />
          <MetricCard
            icon={MemoryStick}
            label="RAM Usage"
            value={`${checks.ram_usage?.value || 0}%`}
            subValue={checks.ram_usage?.total ? `${Math.round((checks.ram_usage.used || 0) / 1024 / 1024 / 1024)}GB / ${Math.round(checks.ram_usage.total / 1024 / 1024 / 1024)}GB` : undefined}
            status={checks.ram_usage?.status}
          />
          <MetricCard
            icon={HardDrive}
            label="Disk Usage"
            value={`${checks.disk_usage?.value || 0}%`}
            subValue={checks.disk_usage?.total ? `${Math.round((checks.disk_usage.used || 0) / 1024 / 1024 / 1024)}GB / ${Math.round(checks.disk_usage.total / 1024 / 1024 / 1024)}GB` : undefined}
            status={checks.disk_usage?.status}
          />
          <MetricCard
            icon={Activity}
            label="Load Average"
            value={checks.load_average?.value?.toFixed(2) || '0.00'}
            status={checks.load_average?.status}
          />
          <MetricCard
            icon={Server}
            label="Services"
            value={checks.services?.stopped_count === 0 ? 'All Running' : `${checks.services?.stopped_count} Stopped`}
            subValue={checks.services?.stopped?.join(', ')}
            status={checks.services?.status}
          />
          <MetricCard
            icon={Shield}
            label="SSL Certificates"
            value={checks.ssl_certs?.issue_count === 0 ? 'All Valid' : `${checks.ssl_certs?.issue_count} Issues`}
            subValue={checks.ssl_certs?.issues?.slice(0, 2).join(', ')}
            status={checks.ssl_certs?.status}
          />
        </div>
      )}

      {activeTab === 'domains' && (
        <Card>
          <div className="p-4 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900">Domains ({domainsData?.domains?.length || 0})</h3>
          </div>
          <div className="divide-y divide-slate-200">
            {domainsData?.domains?.map((domain: PleskDomain) => (
              <div key={domain.name} className="p-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Globe className="w-5 h-5 text-slate-400" />
                  <div>
                    <p className="font-medium text-slate-900">{domain.name}</p>
                    <p className="text-sm text-slate-500">{domain.hosting || 'Unknown hosting'}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  {domain.ssl ? (
                    <Badge variant="success">
                      <Shield className="w-3 h-3 mr-1" />
                      SSL
                    </Badge>
                  ) : (
                    <Badge variant="warning">No SSL</Badge>
                  )}
                  <Badge variant={domain.status === 'active' ? 'success' : 'default'}>
                    {domain.status}
                  </Badge>
                </div>
              </div>
            )) || (
              <div className="p-8 text-center text-slate-500">
                No domains found or unable to fetch domains
              </div>
            )}
          </div>
        </Card>
      )}

      {activeTab === 'services' && (
        <Card>
          <div className="p-4 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900">Services ({servicesData?.services?.length || 0})</h3>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            {servicesData?.services?.map((service: PleskService) => (
              <div
                key={service.id}
                className={`p-4 rounded-lg border ${
                  service.running
                    ? 'border-green-200 bg-green-50'
                    : 'border-red-200 bg-red-50'
                }`}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    {service.running ? (
                      <CheckCircle className="w-5 h-5 text-green-600" />
                    ) : (
                      <XCircle className="w-5 h-5 text-red-600" />
                    )}
                    <span className="font-medium text-slate-900">{service.name}</span>
                  </div>
                  <Badge variant={service.running ? 'success' : 'danger'}>
                    {service.running ? 'Running' : 'Stopped'}
                  </Badge>
                </div>
              </div>
            )) || (
              <div className="col-span-full p-8 text-center text-slate-500">
                No services found or unable to fetch services
              </div>
            )}
          </div>
        </Card>
      )}

      {activeTab === 'health' && (
        <Card>
          <div className="p-4 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900">Health History (Last 30 Days)</h3>
          </div>
          <div className="divide-y divide-slate-200">
            {healthHistory?.history?.map((entry: ServerHealthHistory) => {
              const config = GRADE_CONFIG[entry.grade] || GRADE_CONFIG.F;
              return (
                <div key={entry.id} className="p-4 flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <span className={`inline-flex items-center justify-center w-10 h-10 rounded-full font-bold ${config.bgColor} ${config.color}`}>
                      {entry.grade}
                    </span>
                    <div>
                      <p className="font-medium text-slate-900">Score: {entry.score}/100</p>
                      <p className="text-sm text-slate-500">
                        {new Date(entry.checked_at).toLocaleString()}
                      </p>
                    </div>
                  </div>
                  <Badge
                    variant={
                      entry.status === 'healthy'
                        ? 'success'
                        : entry.status === 'warning'
                        ? 'warning'
                        : 'danger'
                    }
                  >
                    {entry.status}
                  </Badge>
                </div>
              );
            }) || (
              <div className="p-8 text-center text-slate-500">
                No health history available
              </div>
            )}
          </div>
        </Card>
      )}

      {activeTab === 'settings' && (
        <Card className="p-6">
          <h3 className="font-semibold text-slate-900 mb-4">Server Settings</h3>
          <div className="space-y-4 max-w-md">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Display Name
              </label>
              <Input
                type="text"
                placeholder={server.server_host}
                value={serverName || server.server_name || ''}
                onChange={(e) => setServerName(e.target.value)}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Server Host
              </label>
              <Input type="text" value={server.server_host} disabled className="bg-slate-50" />
              <p className="text-xs text-slate-500 mt-1">Server host cannot be changed</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Port
              </label>
              <Input type="text" value={server.server_port} disabled className="bg-slate-50" />
            </div>
            <div className="pt-4">
              <Button
                onClick={() => updateMutation.mutate({ server_name: serverName })}
                disabled={updateMutation.isPending || !serverName}
              >
                {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
              </Button>
            </div>
          </div>
        </Card>
      )}
    </Layout>
  );
}
