import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Trash2,
  Monitor,
  Smartphone,
  Tablet,
  Mail,
  Clock,
  Eye,
  MousePointer,
  Globe,
  Link as LinkIcon,
  ExternalLink,
  User,
  Loader2,
} from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Badge, ConfirmModal } from '../components/common';
import { visitorsApi } from '../api/endpoints';
import type { VisitorEvent } from '../types';
import { useState } from 'react';

const deviceIcons: Record<string, typeof Monitor> = {
  desktop: Monitor,
  mobile: Smartphone,
  tablet: Tablet,
};

const eventTypeColors: Record<string, 'default' | 'success' | 'warning' | 'danger'> = {
  pageview: 'default',
  form_submit: 'success',
  enrollment: 'success',
  click: 'warning',
  identify: 'success',
};

const eventTypeIcons: Record<string, typeof Eye> = {
  pageview: Eye,
  form_submit: Mail,
  enrollment: User,
  click: MousePointer,
  identify: User,
};

export default function VisitorDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [showDelete, setShowDelete] = useState(false);

  // Fetch visitor
  const { data: visitor, isLoading } = useQuery({
    queryKey: ['visitor', id],
    queryFn: () => visitorsApi.getById(Number(id)),
    enabled: !!id,
  });

  // Fetch events
  const { data: eventsData, isLoading: eventsLoading } = useQuery({
    queryKey: ['visitor-events', id],
    queryFn: () => visitorsApi.getEvents(Number(id), { limit: 100 }),
    enabled: !!id,
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: visitorsApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['visitors'] });
      navigate('/visitors');
    },
  });

  if (isLoading) {
    return (
      <Layout title="Visitor Details">
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-6 h-6 animate-spin text-slate-400" />
        </div>
      </Layout>
    );
  }

  if (!visitor) {
    return (
      <Layout title="Visitor Not Found">
        <Card className="p-8 text-center">
          <p className="text-slate-500">This visitor could not be found.</p>
          <Button variant="outline" className="mt-4" onClick={() => navigate('/visitors')}>
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Visitors
          </Button>
        </Card>
      </Layout>
    );
  }

  const DeviceIcon = deviceIcons[visitor.device_type || 'desktop'] || Monitor;

  return (
    <Layout
      title={visitor.email || `Visitor ${visitor.visitor_id.slice(0, 8)}...`}
      description="Visitor activity timeline"
    >
      {/* Action buttons */}
      <div className="flex items-center gap-2 mb-6">
        <Button variant="outline" onClick={() => navigate('/visitors')}>
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back to Visitors
        </Button>
        <Button variant="danger" onClick={() => setShowDelete(true)}>
          <Trash2 className="w-4 h-4 mr-2" />
          Delete Visitor
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Visitor Info */}
        <div className="lg:col-span-1">
          <Card className="p-6">
            <div className="flex items-center gap-4 mb-6">
              <div className="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center">
                <DeviceIcon className="w-8 h-8 text-slate-500" />
              </div>
              <div>
                {visitor.email ? (
                  <>
                    <div className="font-semibold text-lg">{visitor.email}</div>
                    <Badge variant="success">Identified</Badge>
                  </>
                ) : (
                  <>
                    <div className="font-mono text-sm text-slate-500">
                      {visitor.visitor_id.slice(0, 12)}...
                    </div>
                    <Badge variant="default">Anonymous</Badge>
                  </>
                )}
              </div>
            </div>

            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <div className="text-sm text-slate-500">Visits</div>
                  <div className="text-2xl font-bold">{visitor.total_visits}</div>
                </div>
                <div>
                  <div className="text-sm text-slate-500">Pageviews</div>
                  <div className="text-2xl font-bold">{visitor.total_pageviews}</div>
                </div>
              </div>

              <div className="pt-4 border-t border-slate-100 space-y-3">
                <div className="flex items-center gap-2 text-sm">
                  <Monitor className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-500">Device:</span>
                  <span className="capitalize">{visitor.device_type || 'Unknown'}</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <Globe className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-500">Browser:</span>
                  <span>{visitor.browser || 'Unknown'}</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <Monitor className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-500">OS:</span>
                  <span>{visitor.os || 'Unknown'}</span>
                </div>
                {visitor.country && (
                  <div className="flex items-center gap-2 text-sm">
                    <Globe className="w-4 h-4 text-slate-400" />
                    <span className="text-slate-500">Location:</span>
                    <span>
                      {[visitor.city, visitor.region, visitor.country]
                        .filter(Boolean)
                        .join(', ')}
                    </span>
                  </div>
                )}
              </div>

              <div className="pt-4 border-t border-slate-100 space-y-3">
                <div className="flex items-center gap-2 text-sm">
                  <Clock className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-500">First seen:</span>
                  <span>{new Date(visitor.first_seen).toLocaleString()}</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <Clock className="w-4 h-4 text-slate-400" />
                  <span className="text-slate-500">Last seen:</span>
                  <span>{new Date(visitor.last_seen).toLocaleString()}</span>
                </div>
              </div>

              {visitor.contact_id && (
                <div className="pt-4 border-t border-slate-100">
                  <Button
                    variant="outline"
                    size="sm"
                    className="w-full"
                    onClick={() => navigate(`/contacts/${visitor.contact_id}`)}
                  >
                    <User className="w-4 h-4 mr-2" />
                    View Contact
                  </Button>
                </div>
              )}
            </div>
          </Card>

          {/* Visitor ID */}
          <Card className="p-4 mt-4">
            <div className="text-sm text-slate-500 mb-1">Visitor ID</div>
            <div className="font-mono text-xs break-all text-slate-700">
              {visitor.visitor_id}
            </div>
          </Card>
        </div>

        {/* Activity Timeline */}
        <div className="lg:col-span-2">
          <Card className="p-6">
            <h3 className="text-lg font-semibold mb-4">Activity Timeline</h3>

            {eventsLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="w-6 h-6 animate-spin text-slate-400" />
              </div>
            ) : eventsData?.events && eventsData.events.length > 0 ? (
              <div className="relative">
                {/* Timeline line */}
                <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200" />

                <div className="space-y-4">
                  {eventsData.events.map((event: VisitorEvent) => {
                    const EventIcon = eventTypeIcons[event.event_type] || Eye;
                    const color = eventTypeColors[event.event_type] || 'default';

                    return (
                      <div key={event.id} className="relative flex gap-4">
                        {/* Timeline dot */}
                        <div className={`
                          w-8 h-8 rounded-full flex items-center justify-center z-10
                          ${color === 'success' ? 'bg-green-100' :
                            color === 'warning' ? 'bg-amber-100' :
                            color === 'danger' ? 'bg-red-100' : 'bg-slate-100'}
                        `}>
                          <EventIcon className={`w-4 h-4 ${
                            color === 'success' ? 'text-green-600' :
                            color === 'warning' ? 'text-amber-600' :
                            color === 'danger' ? 'text-red-600' : 'text-slate-500'
                          }`} />
                        </div>

                        {/* Event content */}
                        <div className="flex-1 pb-4">
                          <div className="flex items-center gap-2 mb-1">
                            <Badge variant={color}>
                              {event.event_type.replace(/_/g, ' ')}
                            </Badge>
                            <span className="text-xs text-slate-400">
                              {new Date(event.created_at).toLocaleString()}
                            </span>
                          </div>

                          {event.page_url && (
                            <div className="flex items-center gap-1 text-sm text-slate-600 mb-1">
                              <LinkIcon className="w-3 h-3" />
                              <a
                                href={event.page_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hover:text-primary-600 truncate max-w-md"
                              >
                                {event.page_title || event.page_url}
                              </a>
                              <ExternalLink className="w-3 h-3 text-slate-400" />
                            </div>
                          )}

                          {event.referrer && (
                            <div className="text-xs text-slate-400">
                              From: {event.referrer}
                            </div>
                          )}

                          {/* UTM params */}
                          {(event.utm_source || event.utm_campaign) && (
                            <div className="flex flex-wrap gap-1 mt-2">
                              {event.utm_source && (
                                <span className="text-xs bg-slate-100 px-2 py-0.5 rounded">
                                  source: {event.utm_source}
                                </span>
                              )}
                              {event.utm_medium && (
                                <span className="text-xs bg-slate-100 px-2 py-0.5 rounded">
                                  medium: {event.utm_medium}
                                </span>
                              )}
                              {event.utm_campaign && (
                                <span className="text-xs bg-slate-100 px-2 py-0.5 rounded">
                                  campaign: {event.utm_campaign}
                                </span>
                              )}
                            </div>
                          )}

                          {/* Custom data */}
                          {event.custom_data && Object.keys(event.custom_data).length > 0 && (
                            <pre className="mt-2 p-2 bg-slate-50 rounded text-xs overflow-auto max-h-32">
                              {JSON.stringify(event.custom_data, null, 2)}
                            </pre>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ) : (
              <div className="text-center py-8 text-slate-500">
                No events recorded yet
              </div>
            )}
          </Card>
        </div>
      </div>

      {/* Delete Confirmation */}
      <ConfirmModal
        isOpen={showDelete}
        onClose={() => setShowDelete(false)}
        onConfirm={() => deleteMutation.mutate(Number(id))}
        title="Delete Visitor"
        message="Are you sure you want to delete this visitor and all their events? This action cannot be undone."
        confirmText="Delete"
        loading={deleteMutation.isPending}
      />
    </Layout>
  );
}
