import { useState, useMemo } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Copy, Check, ExternalLink, Save, RotateCcw, Library } from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Select } from '../components/common';
import { useUTMStore } from '../store';
import { utmApi } from '../api/endpoints';

const sourceOptions = [
  { value: 'google', label: 'Google' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'twitter', label: 'Twitter/X' },
  { value: 'linkedin', label: 'LinkedIn' },
  { value: 'email', label: 'Email' },
  { value: 'newsletter', label: 'Newsletter' },
  { value: 'direct', label: 'Direct' },
];

const mediumOptions = [
  { value: 'cpc', label: 'CPC (Cost Per Click)' },
  { value: 'organic', label: 'Organic' },
  { value: 'social', label: 'Social' },
  { value: 'email', label: 'Email' },
  { value: 'referral', label: 'Referral' },
  { value: 'display', label: 'Display' },
  { value: 'affiliate', label: 'Affiliate' },
  { value: 'video', label: 'Video' },
];

export default function UTMBuilder() {
  const queryClient = useQueryClient();
  const [copied, setCopied] = useState(false);
  const { formData, setFormField, resetForm } = useUTMStore();

  const createMutation = useMutation({
    mutationFn: utmApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['utms'] });
      resetForm();
    },
  });

  const fullUrl = useMemo(() => {
    if (!formData.base_url) return '';

    try {
      const url = new URL(formData.base_url);
      if (formData.utm_source) url.searchParams.set('utm_source', formData.utm_source);
      if (formData.utm_medium) url.searchParams.set('utm_medium', formData.utm_medium);
      if (formData.utm_campaign) url.searchParams.set('utm_campaign', formData.utm_campaign);
      if (formData.utm_term) url.searchParams.set('utm_term', formData.utm_term);
      if (formData.utm_content) url.searchParams.set('utm_content', formData.utm_content);
      return url.toString();
    } catch {
      return '';
    }
  }, [formData]);

  const isValid = formData.base_url && formData.utm_source && formData.utm_medium && formData.utm_campaign;

  const handleCopy = async () => {
    if (fullUrl) {
      await navigator.clipboard.writeText(fullUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const handleSave = () => {
    if (!isValid) return;

    createMutation.mutate({
      base_url: formData.base_url,
      utm_source: formData.utm_source,
      utm_medium: formData.utm_medium,
      utm_campaign: formData.utm_campaign,
      utm_term: formData.utm_term || undefined,
      utm_content: formData.utm_content || undefined,
      program: formData.program || undefined,
      tags: formData.tags.length > 0 ? formData.tags : undefined,
      notes: formData.notes || undefined,
    });
  };

  return (
    <Layout title="UTM Builder" description="Create tracked URLs for your campaigns">
      <div className="flex justify-end mb-4">
        <Link to="/utm/library">
          <Button variant="outline" icon={<Library className="w-4 h-4" />}>
            View Library
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Form */}
        <Card>
          <h3 className="text-lg font-semibold text-slate-900 mb-6">UTM Parameters</h3>

          <div className="space-y-4">
            <Input
              label="Website URL"
              placeholder="https://example.com/page"
              value={formData.base_url}
              onChange={(e) => setFormField('base_url', e.target.value)}
              required
            />

            <Select
              label="Campaign Source"
              options={sourceOptions}
              value={formData.utm_source}
              onChange={(e) => setFormField('utm_source', e.target.value)}
              placeholder="Select or type source"
              required
            />

            <Select
              label="Campaign Medium"
              options={mediumOptions}
              value={formData.utm_medium}
              onChange={(e) => setFormField('utm_medium', e.target.value)}
              placeholder="Select or type medium"
              required
            />

            <Input
              label="Campaign Name"
              placeholder="summer_sale_2024"
              value={formData.utm_campaign}
              onChange={(e) => setFormField('utm_campaign', e.target.value)}
              helper="Use lowercase with underscores"
              required
            />

            <Input
              label="Campaign Term"
              placeholder="running+shoes"
              value={formData.utm_term}
              onChange={(e) => setFormField('utm_term', e.target.value)}
              helper="Optional: Identify paid keywords"
            />

            <Input
              label="Campaign Content"
              placeholder="banner_ad_1"
              value={formData.utm_content}
              onChange={(e) => setFormField('utm_content', e.target.value)}
              helper="Optional: Differentiate ads/links"
            />

            <Input
              label="Program/Initiative"
              placeholder="q4_initiative"
              value={formData.program}
              onChange={(e) => setFormField('program', e.target.value)}
              helper="Optional: Internal tracking"
            />
          </div>

          <div className="flex gap-3 mt-6 pt-6 border-t border-slate-200">
            <Button
              variant="primary"
              onClick={handleSave}
              loading={createMutation.isPending}
              disabled={!isValid}
              icon={<Save className="w-4 h-4" />}
            >
              Save UTM
            </Button>
            <Button
              variant="outline"
              onClick={resetForm}
              icon={<RotateCcw className="w-4 h-4" />}
            >
              Reset
            </Button>
          </div>
        </Card>

        {/* Preview */}
        <div className="space-y-6">
          <Card>
            <h3 className="text-lg font-semibold text-slate-900 mb-4">Generated URL</h3>

            {fullUrl ? (
              <>
                <div className="p-4 bg-slate-50 rounded-lg border border-slate-200 break-all text-sm font-mono text-slate-700">
                  {fullUrl}
                </div>
                <div className="flex gap-3 mt-4">
                  <Button
                    variant={copied ? 'primary' : 'outline'}
                    onClick={handleCopy}
                    icon={copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
                  >
                    {copied ? 'Copied!' : 'Copy URL'}
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => window.open(fullUrl, '_blank')}
                    icon={<ExternalLink className="w-4 h-4" />}
                  >
                    Test URL
                  </Button>
                </div>
              </>
            ) : (
              <div className="p-8 text-center text-slate-400">
                Enter a URL and UTM parameters to generate your tracked link
              </div>
            )}
          </Card>

          {/* UTM Breakdown */}
          {fullUrl && (
            <Card>
              <h3 className="text-lg font-semibold text-slate-900 mb-4">Parameter Breakdown</h3>
              <div className="space-y-3">
                <ParamRow label="Source" value={formData.utm_source} param="utm_source" />
                <ParamRow label="Medium" value={formData.utm_medium} param="utm_medium" />
                <ParamRow label="Campaign" value={formData.utm_campaign} param="utm_campaign" />
                {formData.utm_term && (
                  <ParamRow label="Term" value={formData.utm_term} param="utm_term" />
                )}
                {formData.utm_content && (
                  <ParamRow label="Content" value={formData.utm_content} param="utm_content" />
                )}
              </div>
            </Card>
          )}
        </div>
      </div>
    </Layout>
  );
}

function ParamRow({ label, value, param }: { label: string; value: string; param: string }) {
  return (
    <div className="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
      <span className="text-sm text-slate-500">{label}</span>
      <code className="text-sm bg-slate-100 px-2 py-1 rounded text-slate-700">
        {param}={value}
      </code>
    </div>
  );
}
