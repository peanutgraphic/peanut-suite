import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface TourStep {
  id: string;
  target: string; // CSS selector for the element to highlight
  title: string;
  content: string;
  placement?: 'top' | 'bottom' | 'left' | 'right';
  route?: string; // Navigate to this route before showing step
  action?: 'click' | 'hover'; // Optional action hint
}

export const tourSteps: TourStep[] = [
  // Welcome
  {
    id: 'welcome',
    target: '[data-tour="sidebar"]',
    title: 'Welcome to Peanut Suite! 🥜',
    content: 'Your all-in-one marketing toolkit. Let\'s take a quick tour of the key features.',
    placement: 'right',
    route: '/',
  },
  // Dashboard
  {
    id: 'dashboard-stats',
    target: '[data-tour="dashboard-stats"]',
    title: 'Dashboard Overview',
    content: 'See all your key metrics at a glance. Track UTM campaigns, short links, contacts, and popups from one place.',
    placement: 'bottom',
    route: '/',
  },
  {
    id: 'quick-actions',
    target: '[data-tour="quick-actions"]',
    title: 'Quick Actions',
    content: 'Jump straight into creating campaigns, links, or popups with these shortcuts.',
    placement: 'right',
    route: '/',
  },
  // UTM Builder
  {
    id: 'utm-nav',
    target: '[data-tour="nav-utm"]',
    title: 'UTM Campaign Tracking',
    content: 'Create UTM-tagged URLs to track exactly where your traffic comes from.',
    placement: 'right',
  },
  {
    id: 'utm-builder',
    target: '[data-tour="utm-form"]',
    title: 'UTM Builder',
    content: 'Fill in your campaign details here. We\'ll generate a trackable URL automatically. Source, Medium, and Campaign are the most important fields.',
    placement: 'bottom',
    route: '/utm',
  },
  // Short Links
  {
    id: 'links-nav',
    target: '[data-tour="nav-links"]',
    title: 'Short Links',
    content: 'Create branded short links that are easy to share and track. Perfect for social media and campaigns.',
    placement: 'right',
  },
  {
    id: 'links-create',
    target: '[data-tour="links-create"]',
    title: 'Create Short Links',
    content: 'Paste any long URL and get a short, trackable link. You can customize the slug and add titles for organization.',
    placement: 'bottom',
    route: '/links',
  },
  // Contacts
  {
    id: 'contacts-nav',
    target: '[data-tour="nav-contacts"]',
    title: 'Contact Management',
    content: 'All your leads and contacts in one place. Track their journey from first click to conversion.',
    placement: 'right',
  },
  // Visitors
  {
    id: 'visitors-nav',
    target: '[data-tour="nav-visitors"]',
    title: 'Visitor Tracking',
    content: 'See everyone who visits your site. Identify anonymous visitors when they fill out forms or make purchases.',
    placement: 'right',
  },
  // Attribution
  {
    id: 'attribution-nav',
    target: '[data-tour="nav-attribution"]',
    title: 'Attribution Reports',
    content: 'Understand the complete customer journey. See which touchpoints lead to conversions.',
    placement: 'right',
  },
  // Popups
  {
    id: 'popups-nav',
    target: '[data-tour="nav-popups"]',
    title: 'Popup Builder',
    content: 'Create engaging popups to capture leads, announce sales, or guide visitors. Customize triggers and targeting.',
    placement: 'right',
  },
  // Analytics
  {
    id: 'analytics-nav',
    target: '[data-tour="nav-analytics"]',
    title: 'Analytics',
    content: 'Deep dive into your marketing performance. See trends, compare campaigns, and identify what\'s working.',
    placement: 'right',
  },
  // Settings
  {
    id: 'settings-nav',
    target: '[data-tour="nav-settings"]',
    title: 'Settings',
    content: 'Configure your preferences, integrations, and advanced options here.',
    placement: 'right',
  },
  // Finish
  {
    id: 'tour-complete',
    target: '[data-tour="sidebar"]',
    title: 'You\'re All Set! 🎉',
    content: 'That\'s the basics! Explore each section to discover more features. You can restart this tour anytime from Settings.',
    placement: 'right',
    route: '/',
  },
];

interface TourState {
  isActive: boolean;
  currentStep: number;
  hasCompletedTour: boolean;
  hasSeenWelcome: boolean;

  // Actions
  startTour: () => void;
  endTour: () => void;
  nextStep: () => void;
  prevStep: () => void;
  goToStep: (step: number) => void;
  skipTour: () => void;
  resetTour: () => void;
  setHasSeenWelcome: (seen: boolean) => void;
}

export const useTourStore = create<TourState>()(
  persist(
    (set, get) => ({
      isActive: false,
      currentStep: 0,
      hasCompletedTour: false,
      hasSeenWelcome: false,

      startTour: () => set({ isActive: true, currentStep: 0 }),

      endTour: () => set({
        isActive: false,
        hasCompletedTour: true,
      }),

      nextStep: () => {
        const { currentStep } = get();
        if (currentStep < tourSteps.length - 1) {
          set({ currentStep: currentStep + 1 });
        } else {
          get().endTour();
        }
      },

      prevStep: () => {
        const { currentStep } = get();
        if (currentStep > 0) {
          set({ currentStep: currentStep - 1 });
        }
      },

      goToStep: (step: number) => {
        if (step >= 0 && step < tourSteps.length) {
          set({ currentStep: step });
        }
      },

      skipTour: () => set({
        isActive: false,
        hasCompletedTour: true,
        hasSeenWelcome: true,
      }),

      resetTour: () => set({
        isActive: false,
        currentStep: 0,
        hasCompletedTour: false,
        hasSeenWelcome: false,
      }),

      setHasSeenWelcome: (seen: boolean) => set({ hasSeenWelcome: seen }),
    }),
    {
      name: 'peanut-tour-storage',
    }
  )
);
