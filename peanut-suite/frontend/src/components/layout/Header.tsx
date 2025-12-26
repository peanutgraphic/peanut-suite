import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Bell, Search, User, Command, Plus, Link2, Target, Users, ChevronDown } from 'lucide-react';

interface HeaderProps {
  title: string;
  description?: string;
  onSearchClick?: () => void;
}

const quickCreateItems = [
  { label: 'New Link', icon: Link2, path: '/links?create=true', description: 'Create short URL' },
  { label: 'New UTM', icon: Target, path: '/utm', description: 'Build UTM parameters' },
  { label: 'New Contact', icon: Users, path: '/contacts?create=true', description: 'Add lead' },
];

export default function Header({ title, description, onSearchClick }: HeaderProps) {
  const navigate = useNavigate();
  const [quickCreateOpen, setQuickCreateOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setQuickCreateOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleQuickCreate = (path: string) => {
    navigate(path);
    setQuickCreateOpen(false);
  };

  return (
    <>
      {/* Top bar - aligns with sidebar logo */}
      <header className="h-14 bg-white border-b border-slate-200 flex items-center justify-end px-6">
        <div className="flex items-center gap-3">
          {/* Quick Create */}
          <div className="relative" ref={dropdownRef}>
            <button
              onClick={() => setQuickCreateOpen(!quickCreateOpen)}
              className="flex items-center gap-1.5 px-3 py-1.5 text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium text-sm"
            >
              <Plus className="w-4 h-4" />
              <span className="hidden sm:inline">Create</span>
              <ChevronDown className="w-3.5 h-3.5" />
            </button>

            {quickCreateOpen && (
              <div className="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                {quickCreateItems.map((item) => (
                  <button
                    key={item.path}
                    onClick={() => handleQuickCreate(item.path)}
                    className="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 transition-colors"
                  >
                    <div className="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                      <item.icon className="w-4 h-4 text-primary-600" />
                    </div>
                    <div>
                      <p className="font-medium text-slate-900 text-sm">{item.label}</p>
                      <p className="text-xs text-slate-500">{item.description}</p>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Search */}
          <button
            onClick={onSearchClick}
            className="flex items-center gap-2 px-3 py-1.5 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors"
            title="Search (⌘K)"
          >
            <Search className="w-4 h-4" />
            <span className="text-sm hidden sm:inline">Search</span>
            <kbd className="hidden sm:flex items-center gap-0.5 px-1.5 py-0.5 text-xs bg-white border border-slate-200 rounded text-slate-400">
              <Command className="w-3 h-3" />K
            </kbd>
          </button>

          {/* Notifications */}
          <button className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors relative">
            <Bell className="w-5 h-5" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
          </button>

          {/* User menu */}
          <button className="flex items-center gap-2 p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
            <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
              <User className="w-4 h-4 text-primary-600" />
            </div>
          </button>
        </div>
      </header>

      {/* Page title - aligns with sidebar nav items */}
      <div className="px-6 pt-3 pb-2 bg-slate-50">
        <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
        {description && (
          <p className="text-sm text-slate-500 mt-0.5">{description}</p>
        )}
      </div>
    </>
  );
}
