import { Beaker, X } from 'lucide-react';
import { useState } from 'react';

interface SampleDataBannerProps {
  onDismiss?: () => void;
  dismissable?: boolean;
}

export default function SampleDataBanner({
  onDismiss,
  dismissable = true,
}: SampleDataBannerProps) {
  const [dismissed, setDismissed] = useState(false);

  if (dismissed) return null;

  const handleDismiss = () => {
    setDismissed(true);
    onDismiss?.();
  };

  return (
    <div className="mb-4 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2">
      <Beaker className="w-4 h-4 text-amber-600 flex-shrink-0" />
      <span className="text-sm font-medium text-amber-800 flex-1">
        Sample Data Preview
      </span>
      {dismissable && (
        <button
          onClick={handleDismiss}
          className="flex-shrink-0 p-1 hover:bg-amber-100 rounded-full transition-colors"
          title="Dismiss"
        >
          <X className="w-4 h-4 text-amber-500" />
        </button>
      )}
    </div>
  );
}
