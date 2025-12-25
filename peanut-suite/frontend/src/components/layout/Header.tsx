import { Bell, Search, User } from 'lucide-react';

interface HeaderProps {
  title: string;
  description?: string;
}

export default function Header({ title, description }: HeaderProps) {
  return (
    <header className="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6">
      <div>
        <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
        {description && (
          <p className="text-sm text-slate-500">{description}</p>
        )}
      </div>

      <div className="flex items-center gap-3">
        {/* Search */}
        <button className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
          <Search className="w-5 h-5" />
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
  );
}
