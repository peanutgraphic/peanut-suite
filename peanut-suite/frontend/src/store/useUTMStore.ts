import { create } from 'zustand';
import { persist } from 'zustand/middleware';
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
  resetFormKeepDefaults: () => void; // Resets but keeps source/medium
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

  // Smart defaults - last used values
  lastSource: string;
  lastMedium: string;
  lastProgram: string;
  saveDefaults: () => void;
  applyDefaults: () => void;
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

export const useUTMStore = create<UTMState>()(
  persist(
    (set, get) => ({
      formData: initialFormData,

      setFormField: (field, value) =>
        set((state) => ({
          formData: { ...state.formData, [field]: value },
        })),

      resetForm: () => set({ formData: initialFormData }),

      resetFormKeepDefaults: () => {
        const { lastSource, lastMedium, lastProgram } = get();
        set({
          formData: {
            ...initialFormData,
            utm_source: lastSource,
            utm_medium: lastMedium,
            program: lastProgram,
          },
        });
      },

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

      // Smart defaults
      lastSource: '',
      lastMedium: '',
      lastProgram: '',

      saveDefaults: () => {
        const { formData } = get();
        set({
          lastSource: formData.utm_source || get().lastSource,
          lastMedium: formData.utm_medium || get().lastMedium,
          lastProgram: formData.program || get().lastProgram,
        });
        // Also add to recent values
        if (formData.utm_source) get().addRecentValue('source', formData.utm_source);
        if (formData.utm_medium) get().addRecentValue('medium', formData.utm_medium);
        if (formData.utm_campaign) get().addRecentValue('campaign', formData.utm_campaign);
        if (formData.program) get().addRecentValue('program', formData.program);
      },

      applyDefaults: () => {
        const { lastSource, lastMedium, lastProgram, formData } = get();
        set({
          formData: {
            ...formData,
            utm_source: formData.utm_source || lastSource,
            utm_medium: formData.utm_medium || lastMedium,
            program: formData.program || lastProgram,
          },
        });
      },
    }),
    {
      name: 'peanut-utm-store',
      partialize: (state) => ({
        // Only persist these fields
        recentSources: state.recentSources,
        recentMediums: state.recentMediums,
        recentCampaigns: state.recentCampaigns,
        recentPrograms: state.recentPrograms,
        lastSource: state.lastSource,
        lastMedium: state.lastMedium,
        lastProgram: state.lastProgram,
        presets: state.presets,
      }),
    }
  )
);
