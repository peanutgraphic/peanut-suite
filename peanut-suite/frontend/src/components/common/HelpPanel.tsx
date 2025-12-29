import { useState } from 'react';
import { ChevronDown, ChevronUp, ChevronLeft, ChevronRight, Lightbulb, HelpCircle, CheckCircle2, Sparkles } from 'lucide-react';
import { clsx } from 'clsx';

interface HelpPanelProps {
  howTo: {
    title: string;
    steps: string[];
  };
  tips?: string[];
  useCases?: {
    title: string;
    examples: string[];
  };
  defaultOpen?: boolean;
}

export default function HelpPanel({ howTo, tips, useCases, defaultOpen = false }: HelpPanelProps) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  const [currentSlide, setCurrentSlide] = useState(0);

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
            <ol className="space-y-3">
              {howTo.steps.map((step, index) => (
                <li key={index} className="flex gap-3">
                  <span className="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    {index + 1}
                  </span>
                  <span className="text-slate-700 pt-0.5">{step}</span>
                </li>
              ))}
            </ol>
          </div>
        );
      case 'useCases':
        return (
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Sparkles className="w-4 h-4 text-purple-500" />
              <span className="font-medium text-slate-900">{useCases?.title}</span>
            </div>
            <ul className="space-y-2">
              {useCases?.examples.map((example, index) => (
                <li key={index} className="flex items-start gap-2 text-sm text-slate-600">
                  <span className="w-1.5 h-1.5 bg-purple-400 rounded-full flex-shrink-0 mt-2" />
                  {example}
                </li>
              ))}
            </ul>
          </div>
        );
      case 'tips':
        return (
          <div>
            <div className="flex items-center gap-2 mb-3">
              <Lightbulb className="w-4 h-4 text-amber-500" />
              <span className="font-medium text-slate-900">Pro Tips</span>
            </div>
            <ul className="space-y-2">
              {tips?.map((tip, index) => (
                <li key={index} className="flex items-start gap-2 text-sm text-slate-600">
                  <CheckCircle2 className="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                  {tip}
                </li>
              ))}
            </ul>
          </div>
        );
    }
  };

  return (
    <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
      {/* Toggle Header */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-full flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors"
      >
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
            <HelpCircle className="w-4 h-4 text-primary-600" />
          </div>
          <div className="text-left">
            <p className="font-medium text-slate-900">{howTo.title}</p>
            <p className="text-xs text-slate-500">
              {isOpen ? 'Click to collapse' : 'Click to expand'}
            </p>
          </div>
        </div>
        {isOpen ? (
          <ChevronUp className="w-5 h-5 text-slate-400" />
        ) : (
          <ChevronDown className="w-5 h-5 text-slate-400" />
        )}
      </button>

      {/* Expandable Content */}
      <div
        className={clsx(
          'transition-all duration-300 ease-in-out overflow-hidden',
          isOpen ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'
        )}
      >
        <div className="px-5 pb-4 border-t border-slate-100">
          {/* Slide Content */}
          <div className="mt-4 min-h-[140px]">
            {renderSlideContent()}
          </div>

          {/* Navigation */}
          {slides.length > 1 && (
            <div className="flex items-center justify-center gap-4 mt-4 pt-3 border-t border-slate-100">
              <button
                onClick={prevSlide}
                className="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors"
                title="Previous"
              >
                <ChevronLeft className="w-4 h-4" />
              </button>

              {/* Dots */}
              <div className="flex items-center gap-2">
                {slides.map((slide, index) => (
                  <button
                    key={index}
                    onClick={() => goToSlide(index)}
                    className={clsx(
                      'w-2 h-2 rounded-full transition-all',
                      currentSlide === index
                        ? 'bg-primary-600 w-4'
                        : 'bg-slate-300 hover:bg-slate-400'
                    )}
                    title={slide.title}
                  />
                ))}
              </div>

              <button
                onClick={nextSlide}
                className="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded transition-colors"
                title="Next"
              >
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
