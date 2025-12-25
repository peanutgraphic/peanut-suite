import { type ReactNode, useState, useEffect } from 'react';
import { clsx } from 'clsx';
import Header from './Header';
import Sidebar from './Sidebar';
import { useEscapeKey } from '@/hooks/useKeyboardShortcuts';

interface LayoutProps {
  children: ReactNode;
  title: string;
  description?: string;
  showSidebar?: boolean;
}

export default function Layout({ children, title, description, showSidebar = true }: LayoutProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Close mobile menu on escape
  useEscapeKey(() => setMobileMenuOpen(false), mobileMenuOpen);

  // Close mobile menu when window is resized to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 768 && mobileMenuOpen) {
        setMobileMenuOpen(false);
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [mobileMenuOpen]);

  // Prevent body scroll when mobile menu is open
  useEffect(() => {
    if (mobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }

    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileMenuOpen]);

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
      {/* Skip to main content link for accessibility */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-primary-600 focus:text-white focus:rounded-lg focus:shadow-lg"
      >
        Skip to main content
      </a>

      {/* Sidebar for desktop and mobile */}
      {showSidebar && (
        <>
          {/* Desktop sidebar */}
          <div className="hidden md:block">
            <Sidebar collapsed={sidebarCollapsed} onToggle={() => setSidebarCollapsed(!sidebarCollapsed)} />
          </div>

          {/* Mobile menu overlay */}
          {mobileMenuOpen && (
            <div
              className="fixed inset-0 bg-black/50 z-40 md:hidden"
              onClick={() => setMobileMenuOpen(false)}
              aria-hidden="true"
            />
          )}

          {/* Mobile sidebar */}
          <div
            className={clsx(
              'md:hidden fixed top-0 left-0 h-screen z-50 transition-transform duration-300',
              mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
            )}
          >
            <Sidebar collapsed={false} onToggle={() => setMobileMenuOpen(false)} />
          </div>
        </>
      )}

      {/* Main content area */}
      <div
        className={clsx(
          'transition-all duration-300',
          showSidebar && 'md:ml-56',
          showSidebar && sidebarCollapsed && 'md:ml-16'
        )}
      >
        <Header
          title={title}
          description={description}
          onMenuToggle={showSidebar ? () => setMobileMenuOpen(!mobileMenuOpen) : undefined}
        />
        <main
          id="main-content"
          className="p-4 md:p-6"
          role="main"
          aria-label="Main content"
        >
          {children}
        </main>
      </div>
    </div>
  );
}
