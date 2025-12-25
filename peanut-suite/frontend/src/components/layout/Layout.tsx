import { type ReactNode } from 'react';
import Header from './Header';

interface LayoutProps {
  children: ReactNode;
  title: string;
  description?: string;
}

export default function Layout({ children, title, description }: LayoutProps) {
  return (
    <div className="min-h-screen bg-slate-50">
      <Header title={title} description={description} />
      <main className="p-6">
        {children}
      </main>
    </div>
  );
}
