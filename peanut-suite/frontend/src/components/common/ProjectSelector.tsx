import { useQuery } from '@tanstack/react-query';
import { FolderKanban, ChevronDown } from 'lucide-react';
import { projectsApi } from '../../api/endpoints';
import { useProjectStore, flattenHierarchy } from '../../store/useProjectStore';
import type { ProjectHierarchy } from '../../types';

interface ProjectSelectorProps {
  value: number | null;
  onChange: (projectId: number | null) => void;
  label?: string;
  required?: boolean;
  disabled?: boolean;
  className?: string;
  placeholder?: string;
  error?: string;
}

export default function ProjectSelector({
  value,
  onChange,
  label = 'Project',
  required = true,
  disabled = false,
  className = '',
  placeholder = 'Select a project',
  error,
}: ProjectSelectorProps) {
  const { currentProject } = useProjectStore();

  // Fetch accessible projects
  const { data: projects = [], isLoading } = useQuery({
    queryKey: ['projects', 'accessible'],
    queryFn: projectsApi.getAccessible,
  });

  // Fetch hierarchy for display
  const { data: hierarchy = [] } = useQuery({
    queryKey: ['projects', 'hierarchy'],
    queryFn: projectsApi.getHierarchy,
  });

  // Flatten hierarchy for dropdown display
  const flatProjects = flattenHierarchy(hierarchy as ProjectHierarchy[]);

  // If value is null and we have a current project, auto-select it
  const effectiveValue = value ?? currentProject?.id ?? null;

  // Find selected project for display
  const selectedProject = projects.find((p) => p.id === effectiveValue);

  return (
    <div className={className}>
      {label && (
        <label className="block text-sm font-medium text-slate-700 mb-1.5">
          {label}
          {required && <span className="text-red-500 ml-0.5">*</span>}
        </label>
      )}
      <div className="relative">
        <select
          value={effectiveValue ?? ''}
          onChange={(e) => {
            const newValue = e.target.value ? Number(e.target.value) : null;
            onChange(newValue);
          }}
          disabled={disabled || isLoading}
          className={`w-full pl-12 pr-10 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-white ${
            error
              ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
              : 'border-slate-300'
          } ${disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''}`}
        >
          {!required && <option value="">{placeholder}</option>}
          {required && !effectiveValue && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {flatProjects.map((project) => (
            <option key={project.id} value={project.id}>
              {'  '.repeat(project.depth)}
              {project.name}
            </option>
          ))}
        </select>

        {/* Left icon */}
        <div className="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
          <div
            className="w-5 h-5 rounded flex items-center justify-center"
            style={{ backgroundColor: selectedProject?.color || '#6366f1' }}
          >
            <FolderKanban className="w-3 h-3 text-white" />
          </div>
        </div>

        {/* Right chevron */}
        <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
          <ChevronDown className="w-4 h-4 text-slate-400" />
        </div>
      </div>

      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}

      {isLoading && (
        <p className="mt-1 text-xs text-slate-500">Loading projects...</p>
      )}
    </div>
  );
}
