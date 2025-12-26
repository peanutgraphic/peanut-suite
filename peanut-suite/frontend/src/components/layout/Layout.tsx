import { type ReactNode, useState, useEffect, useCallback } from 'react';
import Header from './Header';
import Sidebar from './Sidebar';
import { CommandPalette, HelpModal } from '../common';
import type { HelpContent } from '../common';
import { clsx } from 'clsx';

interface LayoutProps {
  children: ReactNode;
  title: string;
  description?: string;
  helpContent?: HelpContent;
}

export default function Layout({ children, title, description, helpContent }: LayoutProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [commandPaletteOpen, setCommandPaletteOpen] = useState(false);
  const [helpModalOpen, setHelpModalOpen] = useState(false);

  // Global keyboard shortcut for command palette (cmd+k or ctrl+k)
  const handleKeyDown = useCallback((e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      setCommandPaletteOpen((open) => !open);
    }
  }, []);

  useEffect(() => {
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [handleKeyDown]);

  return (
    <div className="min-h-screen bg-slate-50">
      <Sidebar
        collapsed={sidebarCollapsed}
        onToggle={() => setSidebarCollapsed(!sidebarCollapsed)}
      />
      <div className={clsx(
        'transition-all duration-300',
        sidebarCollapsed ? 'ml-16' : 'ml-56'
      )}>
        <Header
          title={title}
          description={description}
          onSearchClick={() => setCommandPaletteOpen(true)}
          helpContent={helpContent}
          onHelpClick={helpContent ? () => setHelpModalOpen(true) : undefined}
        />
        <main className="p-6 overflow-x-hidden">
          {children}
        </main>
      </div>

      {/* Global Command Palette */}
      <CommandPalette
        isOpen={commandPaletteOpen}
        onClose={() => setCommandPaletteOpen(false)}
      />

      {/* Help Modal */}
      {helpContent && (
        <HelpModal
          isOpen={helpModalOpen}
          onClose={() => setHelpModalOpen(false)}
          content={helpContent}
        />
      )}
    </div>
  );
}
