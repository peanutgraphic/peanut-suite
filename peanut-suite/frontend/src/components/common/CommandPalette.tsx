import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  Search,
  Link2,
  Target,
  Users,
  BarChart3,
  Settings,
  Plus,
  ArrowRight,
  Command,
  Hash,
  X
} from 'lucide-react';
import { clsx } from 'clsx';
import { linksApi, utmApi, contactsApi } from '../../api/endpoints';
import type { Link, UTM, Contact } from '../../types';

interface CommandPaletteProps {
  isOpen: boolean;
  onClose: () => void;
}

interface SearchResult {
  id: string;
  type: 'link' | 'utm' | 'contact' | 'page' | 'action';
  title: string;
  subtitle?: string;
  icon: React.ReactNode;
  url?: string;
  action?: () => void;
}

const pages: SearchResult[] = [
  { id: 'page-dashboard', type: 'page', title: 'Dashboard', subtitle: 'Overview & analytics', icon: <BarChart3 className="w-4 h-4" />, url: '/' },
  { id: 'page-links', type: 'page', title: 'Links', subtitle: 'Manage short links', icon: <Link2 className="w-4 h-4" />, url: '/links' },
  { id: 'page-utm', type: 'page', title: 'UTM Builder', subtitle: 'Create UTM campaigns', icon: <Target className="w-4 h-4" />, url: '/utm' },
  { id: 'page-utm-library', type: 'page', title: 'UTM Library', subtitle: 'Saved UTM templates', icon: <Hash className="w-4 h-4" />, url: '/utm/library' },
  { id: 'page-contacts', type: 'page', title: 'Contacts', subtitle: 'Lead management', icon: <Users className="w-4 h-4" />, url: '/contacts' },
  { id: 'page-attribution', type: 'page', title: 'Attribution', subtitle: 'Channel performance', icon: <Target className="w-4 h-4" />, url: '/attribution' },
  { id: 'page-analytics', type: 'page', title: 'Analytics', subtitle: 'Traffic insights', icon: <BarChart3 className="w-4 h-4" />, url: '/analytics' },
  { id: 'page-settings', type: 'page', title: 'Settings', subtitle: 'Configure plugin', icon: <Settings className="w-4 h-4" />, url: '/settings' },
];

const quickActions: SearchResult[] = [
  { id: 'action-new-link', type: 'action', title: 'Create New Link', subtitle: 'Shortcut to create', icon: <Plus className="w-4 h-4" />, url: '/links?create=true' },
  { id: 'action-new-utm', type: 'action', title: 'Create New UTM', subtitle: 'Build UTM parameters', icon: <Plus className="w-4 h-4" />, url: '/utm' },
  { id: 'action-new-contact', type: 'action', title: 'Add Contact', subtitle: 'Add new lead', icon: <Plus className="w-4 h-4" />, url: '/contacts?create=true' },
];

export default function CommandPalette({ isOpen, onClose }: CommandPaletteProps) {
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [selectedIndex, setSelectedIndex] = useState(0);
  const inputRef = useRef<HTMLInputElement>(null);
  const listRef = useRef<HTMLDivElement>(null);

  // Fetch data for search
  const { data: linksData } = useQuery({
    queryKey: ['links'],
    queryFn: () => linksApi.getAll({ per_page: 100 }),
    enabled: isOpen,
  });

  const { data: utmsData } = useQuery({
    queryKey: ['utms'],
    queryFn: () => utmApi.getAll({ per_page: 100 }),
    enabled: isOpen,
  });

  const { data: contactsData } = useQuery({
    queryKey: ['contacts'],
    queryFn: () => contactsApi.getAll({ per_page: 100 }),
    enabled: isOpen,
  });

  // Build search results
  const results = useMemo(() => {
    const searchResults: SearchResult[] = [];
    const lowerQuery = query.toLowerCase().trim();

    if (!lowerQuery) {
      // Show quick actions and pages when no query
      return [...quickActions, ...pages];
    }

    // Search links
    const links: Link[] = linksData?.data || [];
    links.forEach((link: Link) => {
      const matches =
        link.slug?.toLowerCase().includes(lowerQuery) ||
        link.original_url?.toLowerCase().includes(lowerQuery) ||
        link.title?.toLowerCase().includes(lowerQuery);

      if (matches) {
        searchResults.push({
          id: `link-${link.id}`,
          type: 'link',
          title: link.title || link.slug || 'Untitled Link',
          subtitle: link.original_url,
          icon: <Link2 className="w-4 h-4" />,
          url: `/links?edit=${link.id}`,
        });
      }
    });

    // Search UTMs
    const utms: UTM[] = utmsData?.data || [];
    utms.forEach((utm: UTM) => {
      const matches =
        utm.utm_campaign?.toLowerCase().includes(lowerQuery) ||
        utm.utm_source?.toLowerCase().includes(lowerQuery) ||
        utm.utm_medium?.toLowerCase().includes(lowerQuery) ||
        utm.base_url?.toLowerCase().includes(lowerQuery);

      if (matches) {
        searchResults.push({
          id: `utm-${utm.id}`,
          type: 'utm',
          title: utm.utm_campaign || 'Untitled Campaign',
          subtitle: `${utm.utm_source} / ${utm.utm_medium}`,
          icon: <Target className="w-4 h-4" />,
          url: `/utm/library?edit=${utm.id}`,
        });
      }
    });

    // Search contacts
    const contacts: Contact[] = contactsData?.data || [];
    contacts.forEach((contact: Contact) => {
      const matches =
        contact.email?.toLowerCase().includes(lowerQuery) ||
        contact.first_name?.toLowerCase().includes(lowerQuery) ||
        contact.last_name?.toLowerCase().includes(lowerQuery) ||
        contact.company?.toLowerCase().includes(lowerQuery);

      if (matches) {
        searchResults.push({
          id: `contact-${contact.id}`,
          type: 'contact',
          title: contact.first_name && contact.last_name
            ? `${contact.first_name} ${contact.last_name}`
            : contact.email || 'Unknown',
          subtitle: contact.company || contact.email,
          icon: <Users className="w-4 h-4" />,
          url: `/contacts?edit=${contact.id}`,
        });
      }
    });

    // Search pages
    pages.forEach((page) => {
      if (page.title.toLowerCase().includes(lowerQuery) || page.subtitle?.toLowerCase().includes(lowerQuery)) {
        searchResults.push(page);
      }
    });

    // Search quick actions
    quickActions.forEach((action) => {
      if (action.title.toLowerCase().includes(lowerQuery)) {
        searchResults.push(action);
      }
    });

    return searchResults;
  }, [query, linksData, utmsData, contactsData]);

  // Reset selection when results change
  useEffect(() => {
    setSelectedIndex(0);
  }, [results]);

  // Focus input when opened
  useEffect(() => {
    if (isOpen) {
      setQuery('');
      setSelectedIndex(0);
      setTimeout(() => inputRef.current?.focus(), 0);
    }
  }, [isOpen]);

  // Handle keyboard navigation
  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedIndex((i) => Math.min(i + 1, results.length - 1));
        break;
      case 'ArrowUp':
        e.preventDefault();
        setSelectedIndex((i) => Math.max(i - 1, 0));
        break;
      case 'Enter':
        e.preventDefault();
        if (results[selectedIndex]) {
          handleSelect(results[selectedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        onClose();
        break;
    }
  }, [results, selectedIndex, onClose]);

  // Scroll selected item into view
  useEffect(() => {
    const list = listRef.current;
    if (!list) return;

    const selected = list.querySelector(`[data-index="${selectedIndex}"]`);
    if (selected) {
      selected.scrollIntoView({ block: 'nearest' });
    }
  }, [selectedIndex]);

  const handleSelect = (result: SearchResult) => {
    if (result.action) {
      result.action();
    } else if (result.url) {
      navigate(result.url);
    }
    onClose();
  };

  const getTypeLabel = (type: SearchResult['type']) => {
    switch (type) {
      case 'link': return 'Link';
      case 'utm': return 'UTM';
      case 'contact': return 'Contact';
      case 'page': return 'Page';
      case 'action': return 'Action';
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="absolute left-1/2 top-[20%] -translate-x-1/2 w-full max-w-xl">
        <div className="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
          {/* Search Input */}
          <div className="flex items-center gap-3 px-4 py-3 border-b border-slate-200">
            <Search className="w-5 h-5 text-slate-400 flex-shrink-0" />
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Search links, UTMs, contacts, pages..."
              className="flex-1 text-base outline-none placeholder:text-slate-400"
            />
            <div className="flex items-center gap-1 text-xs text-slate-400">
              <kbd className="px-1.5 py-0.5 bg-slate-100 rounded text-slate-500 font-mono">esc</kbd>
              <span>to close</span>
            </div>
            <button
              onClick={onClose}
              className="p-1 hover:bg-slate-100 rounded transition-colors"
            >
              <X className="w-4 h-4 text-slate-400" />
            </button>
          </div>

          {/* Results */}
          <div ref={listRef} className="max-h-80 overflow-y-auto">
            {results.length === 0 ? (
              <div className="px-4 py-8 text-center text-slate-500">
                No results found for "{query}"
              </div>
            ) : (
              <div className="py-2">
                {results.map((result, index) => (
                  <button
                    key={result.id}
                    data-index={index}
                    onClick={() => handleSelect(result)}
                    onMouseEnter={() => setSelectedIndex(index)}
                    className={clsx(
                      'w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors',
                      selectedIndex === index ? 'bg-primary-50' : 'hover:bg-slate-50'
                    )}
                  >
                    <div className={clsx(
                      'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                      selectedIndex === index ? 'bg-primary-100 text-primary-600' : 'bg-slate-100 text-slate-500'
                    )}>
                      {result.icon}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-slate-900 truncate">{result.title}</div>
                      {result.subtitle && (
                        <div className="text-sm text-slate-500 truncate">{result.subtitle}</div>
                      )}
                    </div>
                    <div className="flex items-center gap-2 flex-shrink-0">
                      <span className="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                        {getTypeLabel(result.type)}
                      </span>
                      {selectedIndex === index && (
                        <ArrowRight className="w-4 h-4 text-primary-500" />
                      )}
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="px-4 py-2 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <div className="flex items-center gap-3">
              <span className="flex items-center gap-1">
                <kbd className="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono">↑</kbd>
                <kbd className="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono">↓</kbd>
                <span>to navigate</span>
              </span>
              <span className="flex items-center gap-1">
                <kbd className="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono">↵</kbd>
                <span>to select</span>
              </span>
            </div>
            <div className="flex items-center gap-1">
              <Command className="w-3 h-3" />
              <span>K to open</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
