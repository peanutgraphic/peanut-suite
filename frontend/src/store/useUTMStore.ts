import { create } from 'zustand';
import type { UTM } from '../types';

interface UTMFormData {
  base_url: string;
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  utm_term: string;
  utm_content: string;
  program: string;
  tags: string[];
  notes: string;
}

interface UTMState {
  // Form state
  formData: UTMFormData;
  setFormField: <K extends keyof UTMFormData>(field: K, value: UTMFormData[K]) => void;
  resetForm: () => void;
  loadUTM: (utm: UTM) => void;

  // Saved presets
  presets: Record<string, Partial<UTMFormData>>;
  savePreset: (name: string, data: Partial<UTMFormData>) => void;
  deletePreset: (name: string) => void;
  loadPreset: (name: string) => void;

  // Recent values for autocomplete
  recentSources: string[];
  recentMediums: string[];
  recentCampaigns: string[];
  recentPrograms: string[];
  addRecentValue: (field: 'source' | 'medium' | 'campaign' | 'program', value: string) => void;
}

const initialFormData: UTMFormData = {
  base_url: '',
  utm_source: '',
  utm_medium: '',
  utm_campaign: '',
  utm_term: '',
  utm_content: '',
  program: '',
  tags: [],
  notes: '',
};

const addToRecent = (arr: string[], value: string, max = 10): string[] => {
  if (!value) return arr;
  const filtered = arr.filter((v) => v !== value);
  return [value, ...filtered].slice(0, max);
};

export const useUTMStore = create<UTMState>()((set, get) => ({
  formData: initialFormData,

  setFormField: (field, value) =>
    set((state) => ({
      formData: { ...state.formData, [field]: value },
    })),

  resetForm: () => set({ formData: initialFormData }),

  loadUTM: (utm) =>
    set({
      formData: {
        base_url: utm.base_url,
        utm_source: utm.utm_source,
        utm_medium: utm.utm_medium,
        utm_campaign: utm.utm_campaign,
        utm_term: utm.utm_term || '',
        utm_content: utm.utm_content || '',
        program: utm.program || '',
        tags: utm.tags || [],
        notes: utm.notes || '',
      },
    }),

  presets: {},

  savePreset: (name, data) =>
    set((state) => ({
      presets: { ...state.presets, [name]: data },
    })),

  deletePreset: (name) =>
    set((state) => {
      const { [name]: _, ...rest } = state.presets;
      return { presets: rest };
    }),

  loadPreset: (name) => {
    const preset = get().presets[name];
    if (preset) {
      set((state) => ({
        formData: { ...state.formData, ...preset },
      }));
    }
  },

  recentSources: [],
  recentMediums: [],
  recentCampaigns: [],
  recentPrograms: [],

  addRecentValue: (field, value) =>
    set((state) => {
      switch (field) {
        case 'source':
          return { recentSources: addToRecent(state.recentSources, value) };
        case 'medium':
          return { recentMediums: addToRecent(state.recentMediums, value) };
        case 'campaign':
          return { recentCampaigns: addToRecent(state.recentCampaigns, value) };
        case 'program':
          return { recentPrograms: addToRecent(state.recentPrograms, value) };
        default:
          return {};
      }
    }),
}));
