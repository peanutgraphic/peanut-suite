import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface PageGuideState {
  // State
  dismissedGuides: string[];
  shownThisSession: string[];
  isGuideOpen: boolean;
  currentPageId: string | null;
  currentStep: number;

  // Actions
  openGuide: (pageId: string) => void;
  closeGuide: () => void;
  dismissGuide: (pageId: string) => void;
  nextStep: (totalSteps: number) => void;
  prevStep: () => void;
  goToStep: (step: number) => void;
  resetAllGuides: () => void;
  markShownThisSession: (pageId: string) => void;
  shouldAutoShow: (pageId: string) => boolean;
}

export const usePageGuideStore = create<PageGuideState>()(
  persist(
    (set, get) => ({
      dismissedGuides: [],
      shownThisSession: [],
      isGuideOpen: false,
      currentPageId: null,
      currentStep: 0,

      openGuide: (pageId: string) => {
        set({
          isGuideOpen: true,
          currentPageId: pageId,
          currentStep: 0,
        });
        get().markShownThisSession(pageId);
      },

      closeGuide: () => {
        set({
          isGuideOpen: false,
          currentPageId: null,
          currentStep: 0,
        });
      },

      dismissGuide: (pageId: string) => {
        const { dismissedGuides } = get();
        if (!dismissedGuides.includes(pageId)) {
          set({
            dismissedGuides: [...dismissedGuides, pageId],
            isGuideOpen: false,
            currentPageId: null,
            currentStep: 0,
          });
        }
      },

      nextStep: (totalSteps: number) => {
        const { currentStep, currentPageId } = get();
        if (currentStep < totalSteps - 1) {
          set({ currentStep: currentStep + 1 });
        } else {
          // Last step - close and mark as dismissed
          if (currentPageId) {
            get().dismissGuide(currentPageId);
          }
        }
      },

      prevStep: () => {
        const { currentStep } = get();
        if (currentStep > 0) {
          set({ currentStep: currentStep - 1 });
        }
      },

      goToStep: (step: number) => {
        set({ currentStep: step });
      },

      resetAllGuides: () => {
        set({
          dismissedGuides: [],
          shownThisSession: [],
          isGuideOpen: false,
          currentPageId: null,
          currentStep: 0,
        });
      },

      markShownThisSession: (pageId: string) => {
        const { shownThisSession } = get();
        if (!shownThisSession.includes(pageId)) {
          set({ shownThisSession: [...shownThisSession, pageId] });
        }
      },

      shouldAutoShow: (pageId: string) => {
        const { dismissedGuides, shownThisSession } = get();
        return !dismissedGuides.includes(pageId) && !shownThisSession.includes(pageId);
      },
    }),
    {
      name: 'peanut-page-guides',
      partialize: (state) => ({
        // Only persist dismissed guides, not session state
        dismissedGuides: state.dismissedGuides,
      }),
    }
  )
);
