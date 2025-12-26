import { useState } from 'react';
import { X, ChevronLeft, ChevronRight, Lightbulb, Sparkles, CheckCircle2, HelpCircle } from 'lucide-react';
import { clsx } from 'clsx';

export interface HelpContent {
  howTo: {
    title: string;
    steps: string[];
  };
  tips?: string[];
  useCases?: {
    title: string;
    examples: string[];
  };
}

interface HelpModalProps {
  isOpen: boolean;
  onClose: () => void;
  content: HelpContent;
}

export default function HelpModal({ isOpen, onClose, content }: HelpModalProps) {
  const [currentSlide, setCurrentSlide] = useState(0);

  if (!isOpen) return null;

  const { howTo, tips, useCases } = content;

  // Build slides array based on available content
  const slides: { type: 'howTo' | 'useCases' | 'tips'; title: string }[] = [
    { type: 'howTo', title: howTo.title },
  ];
  if (useCases && useCases.examples.length > 0) {
    slides.push({ type: 'useCases', title: useCases.title });
  }
  if (tips && tips.length > 0) {
    slides.push({ type: 'tips', title: 'Pro Tips' });
  }

  const goToSlide = (index: number) => {
    setCurrentSlide(index);
  };

  const nextSlide = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  const renderSlideContent = () => {
    const slide = slides[currentSlide];

    switch (slide.type) {
      case 'howTo':
        return (
          <div>
            <ol className="space-y-4">
              {howTo.steps.map((step, index) => (
                <li key={index} className="flex gap-3">
                  <span className="flex-shrink-0 w-7 h-7 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    {index + 1}
                  </span>
                  <span className="text-slate-700 pt-1">{step}</span>
                </li>
              ))}
            </ol>
          </div>
        );
      case 'useCases':
        return (
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Sparkles className="w-5 h-5 text-purple-500" />
              <span className="font-semibold text-slate-900">{useCases?.title}</span>
            </div>
            <ul className="space-y-3">
              {useCases?.examples.map((example, index) => (
                <li key={index} className="flex items-start gap-3 text-slate-600">
                  <span className="w-2 h-2 bg-purple-400 rounded-full flex-shrink-0 mt-2" />
                  {example}
                </li>
              ))}
            </ul>
          </div>
        );
      case 'tips':
        return (
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Lightbulb className="w-5 h-5 text-amber-500" />
              <span className="font-semibold text-slate-900">Pro Tips</span>
            </div>
            <ul className="space-y-3">
              {tips?.map((tip, index) => (
                <li key={index} className="flex items-start gap-3 text-slate-600">
                  <CheckCircle2 className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                  {tip}
                </li>
              ))}
            </ul>
          </div>
        );
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden animate-scale-in">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-white">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
              <HelpCircle className="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <h2 className="text-lg font-semibold text-slate-900">
                {slides[currentSlide].title}
              </h2>
              <p className="text-xs text-slate-500">
                {currentSlide + 1} of {slides.length}
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="px-6 py-5 min-h-[200px]">
          {renderSlideContent()}
        </div>

        {/* Footer Navigation */}
        {slides.length > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50">
            <button
              onClick={prevSlide}
              className="flex items-center gap-1 px-3 py-1.5 text-sm text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition-colors"
            >
              <ChevronLeft className="w-4 h-4" />
              Previous
            </button>

            {/* Dots */}
            <div className="flex items-center gap-2">
              {slides.map((slide, index) => (
                <button
                  key={index}
                  onClick={() => goToSlide(index)}
                  className={clsx(
                    'w-2.5 h-2.5 rounded-full transition-all',
                    currentSlide === index
                      ? 'bg-primary-600 w-5'
                      : 'bg-slate-300 hover:bg-slate-400'
                  )}
                  title={slide.title}
                />
              ))}
            </div>

            <button
              onClick={nextSlide}
              className="flex items-center gap-1 px-3 py-1.5 text-sm text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition-colors"
            >
              Next
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

// Small button component to trigger the modal
export function HelpButton({ onClick }: { onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className="flex items-center gap-1.5 px-2.5 py-1 text-sm text-slate-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
      title="How to use this page"
    >
      <HelpCircle className="w-4 h-4" />
      <span className="hidden sm:inline">How to</span>
    </button>
  );
}
