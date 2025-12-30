import { useState, useMemo } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Copy, Check, ExternalLink, Save, RotateCcw, Library, Zap } from 'lucide-react';
import { Layout } from '../components/layout';
import { Card, Button, Input, Select, SampleDataBanner, useToast } from '../components/common';
import { useUTMStore } from '../store';
import { utmApi } from '../api/endpoints';
import { helpContent, pageDescriptions } from '../constants';

// Sample UTM form data for preview
const sampleUTMForm = {
  base_url: 'https://example.com/landing-page',
  utm_source: 'google',
  utm_medium: 'cpc',
  utm_campaign: 'summer_sale_2024',
  utm_term: 'marketing software',
  utm_content: 'ad_variation_a',
  program: 'q4_initiative',
  tags: [] as string[],
  notes: '',
};

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
  const toast = useToast();
  const [copied, setCopied] = useState(false);
  const [showSampleData, setShowSampleData] = useState(true);
  const {
    formData,
    setFormField,
    resetForm,
    saveDefaults,
    lastSource,
    lastMedium,
    lastProgram,
    applyDefaults,
  } = useUTMStore();

  // Check if form is empty to show sample data
  const isFormEmpty = !formData.base_url && !formData.utm_source && !formData.utm_medium && !formData.utm_campaign;
  const displaySampleData = isFormEmpty && showSampleData;

  // Use sample or real form data for display
  const displayFormData = displaySampleData ? sampleUTMForm : formData;

  const createMutation = useMutation({
    mutationFn: utmApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['utms'] });
      saveDefaults(); // Remember these values for next time
      resetForm();
      toast.success('UTM saved to library');
    },
    onError: () => {
      toast.error('Failed to save UTM');
    },
  });

  // Check if we have smart defaults available
  const hasSmartDefaults = !!(lastSource || lastMedium || lastProgram);
  const canApplyDefaults = hasSmartDefaults && isFormEmpty;

  // Generate preview URL based on display form data (sample or real)
  const fullUrl = useMemo(() => {
    if (!displayFormData.base_url) return '';

    try {
      const url = new URL(displayFormData.base_url);
      if (displayFormData.utm_source) url.searchParams.set('utm_source', displayFormData.utm_source);
      if (displayFormData.utm_medium) url.searchParams.set('utm_medium', displayFormData.utm_medium);
      if (displayFormData.utm_campaign) url.searchParams.set('utm_campaign', displayFormData.utm_campaign);
      if (displayFormData.utm_term) url.searchParams.set('utm_term', displayFormData.utm_term);
      if (displayFormData.utm_content) url.searchParams.set('utm_content', displayFormData.utm_content);
      return url.toString();
    } catch {
      return '';
    }
  }, [displayFormData]);

  const isValid = formData.base_url && formData.utm_source && formData.utm_medium && formData.utm_campaign;

  // Calculate form progress
  const requiredFields = ['base_url', 'utm_source', 'utm_medium', 'utm_campaign'] as const;
  const optionalFields = ['utm_term', 'utm_content', 'program'] as const;
  const filledRequired = requiredFields.filter(f => formData[f]).length;
  const filledOptional = optionalFields.filter(f => formData[f]).length;
  const totalProgress = Math.round(((filledRequired / requiredFields.length) * 80) + ((filledOptional / optionalFields.length) * 20));

  const handleCopy = async () => {
    if (fullUrl) {
      await navigator.clipboard.writeText(fullUrl);
      setCopied(true);
      toast.success('URL copied to clipboard');
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

  const pageInfo = pageDescriptions.utm;
  const pageHelpContent = { howTo: pageInfo.howTo, tips: pageInfo.tips, useCases: pageInfo.useCases };

  return (
    <Layout title={pageInfo.title} description={pageInfo.description} helpContent={pageHelpContent} pageGuideId="utm">
      {/* Sample Data Banner */}
      {displaySampleData && (
        <SampleDataBanner onDismiss={() => setShowSampleData(false)} />
      )}

      {/* Smart Defaults Banner */}
      {canApplyDefaults && !displaySampleData && (
        <div className="mb-4 px-4 py-3 bg-indigo-50 border border-indigo-200 rounded-lg flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Zap className="w-4 h-4 text-indigo-600" />
            <span className="text-sm text-indigo-800">
              <span className="font-medium">Smart defaults available:</span>{' '}
              {lastSource && <span className="bg-indigo-100 px-1.5 py-0.5 rounded text-xs">{lastSource}</span>}
              {lastMedium && <span className="bg-indigo-100 px-1.5 py-0.5 rounded text-xs ml-1">{lastMedium}</span>}
              {lastProgram && <span className="bg-indigo-100 px-1.5 py-0.5 rounded text-xs ml-1">{lastProgram}</span>}
            </span>
          </div>
          <button
            onClick={applyDefaults}
            className="text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
          >
            Apply <Zap className="w-3 h-3" />
          </button>
        </div>
      )}

      <div className="flex justify-end mb-4">
        <Link to="/utm/library">
          <Button variant="outline" icon={<Library className="w-4 h-4" />}>
            View Library
          </Button>
        </Link>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Form */}
        <Card data-tour="utm-form">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-slate-900">UTM Parameters</h3>
            <span className="text-sm text-slate-500">{totalProgress}% complete</span>
          </div>

          {/* Progress Bar */}
          <div className="h-1.5 bg-slate-100 rounded-full mb-6 overflow-hidden">
            <div
              className={`h-full rounded-full transition-all duration-300 ${
                totalProgress === 100 ? 'bg-green-500' : 'bg-primary-500'
              }`}
              style={{ width: `${totalProgress}%` }}
            />
          </div>

          <div className="space-y-4">
            <Input
              label="Website URL"
              placeholder="https://example.com/page"
              value={formData.base_url}
              onChange={(e) => setFormField('base_url', e.target.value)}
              tooltip={helpContent.utm.baseUrl}
              required
            />

            <Select
              label="Campaign Source"
              options={sourceOptions}
              value={formData.utm_source}
              onChange={(e) => setFormField('utm_source', e.target.value)}
              placeholder="Select or type source"
              tooltip={helpContent.utm.source}
              required
            />

            <Select
              label="Campaign Medium"
              options={mediumOptions}
              value={formData.utm_medium}
              onChange={(e) => setFormField('utm_medium', e.target.value)}
              placeholder="Select or type medium"
              tooltip={helpContent.utm.medium}
              required
            />

            <Input
              label="Campaign Name"
              placeholder="summer_sale_2024"
              value={formData.utm_campaign}
              onChange={(e) => setFormField('utm_campaign', e.target.value)}
              helper="Use lowercase with underscores"
              tooltip={helpContent.utm.campaign}
              required
            />

            <Input
              label="Campaign Term"
              placeholder="running+shoes"
              value={formData.utm_term}
              onChange={(e) => setFormField('utm_term', e.target.value)}
              helper="Optional: Identify paid keywords"
              tooltip={helpContent.utm.term}
            />

            <Input
              label="Campaign Content"
              placeholder="banner_ad_1"
              value={formData.utm_content}
              onChange={(e) => setFormField('utm_content', e.target.value)}
              helper="Optional: Differentiate ads/links"
              tooltip={helpContent.utm.content}
            />

            <Input
              label="Program/Initiative"
              placeholder="q4_initiative"
              value={formData.program}
              onChange={(e) => setFormField('program', e.target.value)}
              helper="Optional: Internal tracking"
              tooltip={helpContent.utm.program}
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
                <ParamRow label="Source" value={displayFormData.utm_source} param="utm_source" />
                <ParamRow label="Medium" value={displayFormData.utm_medium} param="utm_medium" />
                <ParamRow label="Campaign" value={displayFormData.utm_campaign} param="utm_campaign" />
                {displayFormData.utm_term && (
                  <ParamRow label="Term" value={displayFormData.utm_term} param="utm_term" />
                )}
                {displayFormData.utm_content && (
                  <ParamRow label="Content" value={displayFormData.utm_content} param="utm_content" />
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
