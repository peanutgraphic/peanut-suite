import { useState, useRef, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Building2, ChevronDown, Search, Plus, X } from 'lucide-react';
import { clientsApi } from '../../api/endpoints';
import type { Client } from '../../types';

interface ClientSelectorProps {
  value: number | null;
  onChange: (clientId: number | null) => void;
  label?: string;
  required?: boolean;
  disabled?: boolean;
  className?: string;
  placeholder?: string;
  error?: string;
  onCreateNew?: () => void;
  showSearch?: boolean;
}

export default function ClientSelector({
  value,
  onChange,
  label = 'Client',
  required = false,
  disabled = false,
  className = '',
  placeholder = 'Select a client',
  error,
  onCreateNew,
  showSearch = true,
}: ClientSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const dropdownRef = useRef<HTMLDivElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  // Fetch clients
  const { data: clients = [], isLoading } = useQuery({
    queryKey: ['clients', { status: 'active' }],
    queryFn: () => clientsApi.getAll({ status: 'active' }),
  });

  // Find selected client
  const selectedClient = clients.find((c: Client) => c.id === value);

  // Filter clients by search
  const filteredClients = clients.filter((c: Client) =>
    c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    (c.billing_email && c.billing_email.toLowerCase().includes(searchQuery.toLowerCase()))
  );

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
        setSearchQuery('');
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Focus search input when dropdown opens
  useEffect(() => {
    if (isOpen && showSearch && searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, [isOpen, showSearch]);

  const handleSelect = (clientId: number | null) => {
    onChange(clientId);
    setIsOpen(false);
    setSearchQuery('');
  };

  return (
    <div className={`relative ${className}`} ref={dropdownRef}>
      {label && (
        <label className="block text-sm font-medium text-slate-700 mb-1.5">
          {label}
          {required && <span className="text-red-500 ml-0.5">*</span>}
        </label>
      )}

      {/* Trigger Button */}
      <button
        type="button"
        onClick={() => !disabled && setIsOpen(!isOpen)}
        disabled={disabled || isLoading}
        className={`w-full flex items-center gap-3 px-3 py-2 border rounded-lg text-sm text-left focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white ${
          error
            ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
            : 'border-slate-300'
        } ${disabled ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : 'hover:border-slate-400'}`}
      >
        <div className="w-6 h-6 rounded bg-primary-100 flex items-center justify-center flex-shrink-0">
          <Building2 className="w-3.5 h-3.5 text-primary-600" />
        </div>
        <div className="flex-1 min-w-0">
          {selectedClient ? (
            <div>
              <p className="font-medium text-slate-900 truncate">{selectedClient.name}</p>
              {selectedClient.primary_contact && (
                <p className="text-xs text-slate-500 truncate">
                  {selectedClient.primary_contact.contact_name || selectedClient.primary_contact.contact_email}
                </p>
              )}
            </div>
          ) : (
            <span className="text-slate-400">{placeholder}</span>
          )}
        </div>
        {value && !required && !disabled && (
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              handleSelect(null);
            }}
            className="p-1 hover:bg-slate-100 rounded"
          >
            <X className="w-4 h-4 text-slate-400" />
          </button>
        )}
        <ChevronDown className={`w-4 h-4 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-hidden">
          {/* Search Input */}
          {showSearch && (
            <div className="p-2 border-b border-slate-100">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input
                  ref={searchInputRef}
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search clients..."
                  className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                />
              </div>
            </div>
          )}

          {/* Client List */}
          <div className="max-h-52 overflow-y-auto">
            {!required && (
              <button
                type="button"
                onClick={() => handleSelect(null)}
                className={`w-full px-3 py-2 text-left text-sm hover:bg-slate-50 flex items-center gap-3 ${
                  value === null ? 'bg-primary-50' : ''
                }`}
              >
                <div className="w-6 h-6 rounded bg-slate-100 flex items-center justify-center">
                  <Building2 className="w-3.5 h-3.5 text-slate-400" />
                </div>
                <span className="text-slate-500 italic">No client</span>
              </button>
            )}

            {filteredClients.length === 0 ? (
              <div className="px-3 py-4 text-center text-sm text-slate-500">
                {searchQuery ? 'No clients found' : 'No clients available'}
              </div>
            ) : (
              filteredClients.map((client: Client) => (
                <button
                  key={client.id}
                  type="button"
                  onClick={() => handleSelect(client.id)}
                  className={`w-full px-3 py-2 text-left hover:bg-slate-50 flex items-center gap-3 ${
                    value === client.id ? 'bg-primary-50' : ''
                  }`}
                >
                  <div className="w-6 h-6 rounded bg-primary-100 flex items-center justify-center flex-shrink-0">
                    <Building2 className="w-3.5 h-3.5 text-primary-600" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-slate-900 truncate">{client.name}</p>
                    {client.primary_contact ? (
                      <p className="text-xs text-slate-500 truncate">
                        {client.primary_contact.contact_name || client.primary_contact.contact_email}
                      </p>
                    ) : client.billing_email ? (
                      <p className="text-xs text-slate-500 truncate">{client.billing_email}</p>
                    ) : null}
                  </div>
                  {value === client.id && (
                    <div className="w-5 h-5 rounded-full bg-primary-600 flex items-center justify-center">
                      <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                      </svg>
                    </div>
                  )}
                </button>
              ))
            )}
          </div>

          {/* Create New Option */}
          {onCreateNew && (
            <div className="border-t border-slate-100 p-2">
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  onCreateNew();
                }}
                className="w-full flex items-center gap-2 px-3 py-2 text-sm text-primary-600 hover:bg-primary-50 rounded-md"
              >
                <Plus className="w-4 h-4" />
                Add new client
              </button>
            </div>
          )}
        </div>
      )}

      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}

      {isLoading && (
        <p className="mt-1 text-xs text-slate-500">Loading clients...</p>
      )}
    </div>
  );
}
