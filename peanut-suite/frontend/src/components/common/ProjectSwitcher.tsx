import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { FolderKanban, ChevronDown, Check, Plus, Settings, FolderTree } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { projectsApi } from '../../api/endpoints';
import { useProjectStore, flattenHierarchy } from '../../store/useProjectStore';
import { useAccountStore } from '../../store';
import type { Project, ProjectHierarchy } from '../../types';

interface ProjectSwitcherProps {
  className?: string;
  compact?: boolean;
}

export default function ProjectSwitcher({ className = '', compact = false }: ProjectSwitcherProps) {
  const navigate = useNavigate();
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const { isOwnerOrAdmin } = useAccountStore();
  const {
    currentProject,
    setCurrentProject,
    setProjects,
    setHierarchy,
    setLimits,
    setLoading,
    markInitialized,
  } = useProjectStore();

  // Fetch projects
  const { data: projects = [], isLoading: projectsLoading } = useQuery({
    queryKey: ['projects'],
    queryFn: async () => {
      const data = await projectsApi.getAccessible();
      setProjects(data);
      return data;
    },
  });

  // Fetch hierarchy for display
  const { data: hierarchy = [] } = useQuery({
    queryKey: ['projects', 'hierarchy'],
    queryFn: async () => {
      const data = await projectsApi.getHierarchy();
      setHierarchy(data);
      return data;
    },
  });

  // Fetch limits
  useQuery({
    queryKey: ['projects', 'limits'],
    queryFn: async () => {
      const data = await projectsApi.getLimits();
      setLimits(data);
      return data;
    },
  });

  // Mark as initialized when projects load
  useEffect(() => {
    if (!projectsLoading && projects.length >= 0) {
      markInitialized();
    }
  }, [projectsLoading, projects, markInitialized]);

  // Update loading state
  useEffect(() => {
    setLoading(projectsLoading);
  }, [projectsLoading, setLoading]);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelectProject = (project: Project | null) => {
    setCurrentProject(project);
    setIsOpen(false);
  };

  const handleManageProjects = () => {
    navigate('/projects');
    setIsOpen(false);
  };

  const handleCreateProject = () => {
    navigate('/projects?create=true');
    setIsOpen(false);
  };

  // Flatten hierarchy for dropdown display
  const flatProjects = flattenHierarchy(hierarchy as ProjectHierarchy[]);

  // Don't render if no projects
  if (projects.length === 0 && !projectsLoading) {
    return null;
  }

  return (
    <div className={`relative ${className}`} ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className={`flex items-center gap-2 px-3 py-1.5 text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors ${
          compact ? 'max-w-[120px]' : 'max-w-[200px]'
        }`}
        disabled={projectsLoading}
      >
        <div
          className="w-5 h-5 rounded flex items-center justify-center flex-shrink-0"
          style={{ backgroundColor: currentProject?.color || '#6366f1' }}
        >
          <FolderKanban className="w-3 h-3 text-white" />
        </div>
        <span className="text-sm font-medium truncate">
          {projectsLoading
            ? 'Loading...'
            : currentProject
              ? currentProject.name
              : 'All Projects'}
        </span>
        <ChevronDown className="w-4 h-4 flex-shrink-0 text-slate-400" />
      </button>

      {isOpen && (
        <div className="absolute left-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
          {/* All Projects option for admins */}
          {isOwnerOrAdmin() && (
            <>
              <button
                onClick={() => handleSelectProject(null)}
                className="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 transition-colors"
              >
                <div className="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                  <FolderTree className="w-4 h-4 text-slate-500" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-slate-900 text-sm">All Projects</p>
                  <p className="text-xs text-slate-500">View across all projects</p>
                </div>
                {!currentProject && (
                  <Check className="w-4 h-4 text-primary-600 flex-shrink-0" />
                )}
              </button>
              <div className="h-px bg-slate-100 my-1" />
            </>
          )}

          {/* Project list */}
          <div className="max-h-64 overflow-y-auto">
            {flatProjects.map((project) => (
              <button
                key={project.id}
                onClick={() => handleSelectProject(project)}
                className="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 transition-colors"
                style={{ paddingLeft: `${16 + project.depth * 16}px` }}
              >
                <div
                  className="w-6 h-6 rounded flex items-center justify-center flex-shrink-0"
                  style={{ backgroundColor: project.color || '#6366f1' }}
                >
                  <FolderKanban className="w-3 h-3 text-white" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-slate-900 text-sm truncate">
                    {project.name}
                  </p>
                  {project.description && (
                    <p className="text-xs text-slate-500 truncate">
                      {project.description}
                    </p>
                  )}
                </div>
                {currentProject?.id === project.id && (
                  <Check className="w-4 h-4 text-primary-600 flex-shrink-0" />
                )}
              </button>
            ))}
          </div>

          {/* Actions for admins */}
          {isOwnerOrAdmin() && (
            <>
              <div className="h-px bg-slate-100 my-1" />
              <button
                onClick={handleCreateProject}
                className="w-full flex items-center gap-3 px-4 py-2.5 text-left text-primary-600 hover:bg-primary-50 transition-colors"
              >
                <div className="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                  <Plus className="w-4 h-4 text-primary-600" />
                </div>
                <span className="font-medium text-sm">New Project</span>
              </button>
              <button
                onClick={handleManageProjects}
                className="w-full flex items-center gap-3 px-4 py-2.5 text-left text-slate-600 hover:bg-slate-50 transition-colors"
              >
                <div className="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                  <Settings className="w-4 h-4 text-slate-500" />
                </div>
                <span className="font-medium text-sm">Manage Projects</span>
              </button>
            </>
          )}
        </div>
      )}
    </div>
  );
}
