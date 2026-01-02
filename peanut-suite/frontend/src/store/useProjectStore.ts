import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Project, ProjectHierarchy, ProjectLimits } from '../types';

interface ProjectState {
  // Data
  projects: Project[];
  currentProject: Project | null;
  hierarchy: ProjectHierarchy[];
  limits: ProjectLimits | null;

  // Loading states
  isLoading: boolean;
  isInitialized: boolean;
  error: string | null;

  // Actions
  setProjects: (projects: Project[]) => void;
  setCurrentProject: (project: Project | null) => void;
  setHierarchy: (hierarchy: ProjectHierarchy[]) => void;
  setLimits: (limits: ProjectLimits | null) => void;
  setLoading: (isLoading: boolean) => void;
  setError: (error: string | null) => void;
  markInitialized: () => void;
  clearProject: () => void;
  reset: () => void;

  // Selectors
  getProjectById: (id: number) => Project | undefined;
  getProjectsByParent: (parentId: number | null) => Project[];
  getCurrentProjectId: () => number | null;
  hasProjects: () => boolean;
  canCreateProject: () => boolean;
}

const STORAGE_KEY = 'peanut-current-project';

export const useProjectStore = create<ProjectState>()(
  persist(
    (set, get) => ({
      // Initial state
      projects: [],
      currentProject: null,
      hierarchy: [],
      limits: null,
      isLoading: false,
      isInitialized: false,
      error: null,

      // Actions
      setProjects: (projects) => {
        set({ projects });

        // If we have a current project selected, verify it still exists
        const { currentProject } = get();
        if (currentProject) {
          const stillExists = projects.find((p) => p.id === currentProject.id);
          if (!stillExists) {
            // Current project was deleted, clear selection
            set({ currentProject: null });
          } else {
            // Update with latest data
            set({ currentProject: stillExists });
          }
        }

        // If no current project but we have projects, select the default or first one
        if (!get().currentProject && projects.length > 0) {
          const defaultProject = projects.find((p) => p.settings?.is_default);
          set({ currentProject: defaultProject || projects[0] });
        }
      },

      setCurrentProject: (project) => {
        set({ currentProject: project });
      },

      setHierarchy: (hierarchy) => {
        set({ hierarchy });
      },

      setLimits: (limits) => {
        set({ limits });
      },

      setLoading: (isLoading) => {
        set({ isLoading });
      },

      setError: (error) => {
        set({ error });
      },

      markInitialized: () => {
        set({ isInitialized: true });
      },

      clearProject: () => {
        set({ currentProject: null });
      },

      reset: () => {
        set({
          projects: [],
          currentProject: null,
          hierarchy: [],
          limits: null,
          isLoading: false,
          isInitialized: false,
          error: null,
        });
      },

      // Selectors
      getProjectById: (id) => {
        const { projects } = get();
        return projects.find((p) => p.id === id);
      },

      getProjectsByParent: (parentId) => {
        const { projects } = get();
        return projects.filter((p) => p.parent_id === parentId);
      },

      getCurrentProjectId: () => {
        const { currentProject } = get();
        return currentProject?.id ?? null;
      },

      hasProjects: () => {
        const { projects } = get();
        return projects.length > 0;
      },

      canCreateProject: () => {
        const { limits } = get();
        if (!limits) return true; // Default to allowing if limits not loaded
        return limits.can_create;
      },
    }),
    {
      name: STORAGE_KEY,
      // Only persist the current project ID, not all data
      partialize: (state) => ({
        currentProjectId: state.currentProject?.id,
      }),
      // On rehydration, we only have the ID - we'll resolve it when projects load
      onRehydrateStorage: () => () => {
        // The actual project will be resolved when setProjects is called
      },
    }
  )
);

// Helper hook to get flattened projects with depth info for display
export function flattenHierarchy(
  hierarchy: ProjectHierarchy[],
  depth = 0
): Array<Project & { depth: number }> {
  const result: Array<Project & { depth: number }> = [];

  for (const project of hierarchy) {
    result.push({ ...project, depth });
    if (project.children && project.children.length > 0) {
      result.push(...flattenHierarchy(project.children, depth + 1));
    }
  }

  return result;
}

// Get breadcrumb path for a project
export function getProjectBreadcrumb(
  projectId: number,
  projects: Project[]
): Project[] {
  const path: Project[] = [];
  let current = projects.find((p) => p.id === projectId);

  while (current) {
    path.unshift(current);
    current = current.parent_id
      ? projects.find((p) => p.id === current!.parent_id)
      : undefined;
  }

  return path;
}
