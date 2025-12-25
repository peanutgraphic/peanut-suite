import { create } from 'zustand';

interface FilterState {
  // UTM filters
  utmFilters: {
    search: string;
    source: string;
    medium: string;
    campaign: string;
    program: string;
    dateFrom: string;
    dateTo: string;
  };
  setUTMFilter: <K extends keyof FilterState['utmFilters']>(
    key: K,
    value: FilterState['utmFilters'][K]
  ) => void;
  resetUTMFilters: () => void;

  // Link filters
  linkFilters: {
    search: string;
    status: string;
    domain: string;
    dateFrom: string;
    dateTo: string;
  };
  setLinkFilter: <K extends keyof FilterState['linkFilters']>(
    key: K,
    value: FilterState['linkFilters'][K]
  ) => void;
  resetLinkFilters: () => void;

  // Contact filters
  contactFilters: {
    search: string;
    status: string;
    source: string;
    tag: string;
    dateFrom: string;
    dateTo: string;
  };
  setContactFilter: <K extends keyof FilterState['contactFilters']>(
    key: K,
    value: FilterState['contactFilters'][K]
  ) => void;
  resetContactFilters: () => void;

  // Popup filters
  popupFilters: {
    search: string;
    status: string;
    type: string;
  };
  setPopupFilter: <K extends keyof FilterState['popupFilters']>(
    key: K,
    value: FilterState['popupFilters'][K]
  ) => void;
  resetPopupFilters: () => void;
}

const initialUTMFilters = {
  search: '',
  source: '',
  medium: '',
  campaign: '',
  program: '',
  dateFrom: '',
  dateTo: '',
};

const initialLinkFilters = {
  search: '',
  status: '',
  domain: '',
  dateFrom: '',
  dateTo: '',
};

const initialContactFilters = {
  search: '',
  status: '',
  source: '',
  tag: '',
  dateFrom: '',
  dateTo: '',
};

const initialPopupFilters = {
  search: '',
  status: '',
  type: '',
};

export const useFilterStore = create<FilterState>()((set) => ({
  utmFilters: initialUTMFilters,
  setUTMFilter: (key, value) =>
    set((state) => ({
      utmFilters: { ...state.utmFilters, [key]: value },
    })),
  resetUTMFilters: () => set({ utmFilters: initialUTMFilters }),

  linkFilters: initialLinkFilters,
  setLinkFilter: (key, value) =>
    set((state) => ({
      linkFilters: { ...state.linkFilters, [key]: value },
    })),
  resetLinkFilters: () => set({ linkFilters: initialLinkFilters }),

  contactFilters: initialContactFilters,
  setContactFilter: (key, value) =>
    set((state) => ({
      contactFilters: { ...state.contactFilters, [key]: value },
    })),
  resetContactFilters: () => set({ contactFilters: initialContactFilters }),

  popupFilters: initialPopupFilters,
  setPopupFilter: (key, value) =>
    set((state) => ({
      popupFilters: { ...state.popupFilters, [key]: value },
    })),
  resetPopupFilters: () => set({ popupFilters: initialPopupFilters }),
}));
