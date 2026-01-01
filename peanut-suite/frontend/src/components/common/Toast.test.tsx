import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ToastProvider, useToast } from './Toast';

// Test component to access toast context
function TestToastTrigger() {
  const toast = useToast();

  return (
    <div>
      <button onClick={() => toast.success('Success message')}>
        Show Success
      </button>
      <button onClick={() => toast.error('Error message')}>
        Show Error
      </button>
      <button onClick={() => toast.warning('Warning message')}>
        Show Warning
      </button>
      <button onClick={() => toast.info('Info message')}>
        Show Info
      </button>
      <button onClick={() => toast.success('Custom duration', 0)}>
        No Auto-dismiss
      </button>
    </div>
  );
}

describe('ToastProvider', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders children', () => {
    render(
      <ToastProvider>
        <div>Child content</div>
      </ToastProvider>
    );
    expect(screen.getByText('Child content')).toBeInTheDocument();
  });

  it('does not render toast container initially', () => {
    render(
      <ToastProvider>
        <div>Content</div>
      </ToastProvider>
    );
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  // =========================================
  // Toast Type Tests
  // =========================================

  it('shows success toast', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));

    expect(screen.getByRole('alert')).toBeInTheDocument();
    expect(screen.getByText('Success message')).toBeInTheDocument();
  });

  it('success toast has green styling', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));

    const alert = screen.getByRole('alert');
    expect(alert).toHaveClass('bg-green-50', 'border-green-200', 'text-green-800');
  });

  it('shows error toast', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Error'));

    expect(screen.getByRole('alert')).toBeInTheDocument();
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  it('error toast has red styling', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Error'));

    const alert = screen.getByRole('alert');
    expect(alert).toHaveClass('bg-red-50', 'border-red-200', 'text-red-800');
  });

  it('shows warning toast', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Warning'));

    expect(screen.getByRole('alert')).toBeInTheDocument();
    expect(screen.getByText('Warning message')).toBeInTheDocument();
  });

  it('warning toast has amber styling', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Warning'));

    const alert = screen.getByRole('alert');
    expect(alert).toHaveClass('bg-amber-50', 'border-amber-200', 'text-amber-800');
  });

  it('shows info toast', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Info'));

    expect(screen.getByRole('alert')).toBeInTheDocument();
    expect(screen.getByText('Info message')).toBeInTheDocument();
  });

  it('info toast has blue styling', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Info'));

    const alert = screen.getByRole('alert');
    expect(alert).toHaveClass('bg-blue-50', 'border-blue-200', 'text-blue-800');
  });

  // =========================================
  // Auto-dismiss Tests
  // =========================================

  it('auto-dismisses toast after duration', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));
    expect(screen.getByText('Success message')).toBeInTheDocument();

    // Fast-forward time by default duration (4000ms)
    act(() => {
      vi.advanceTimersByTime(4000);
    });

    expect(screen.queryByText('Success message')).not.toBeInTheDocument();
  });

  it('does not auto-dismiss when duration is 0', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('No Auto-dismiss'));
    expect(screen.getByText('Custom duration')).toBeInTheDocument();

    // Fast-forward a long time
    act(() => {
      vi.advanceTimersByTime(10000);
    });

    // Toast should still be visible
    expect(screen.getByText('Custom duration')).toBeInTheDocument();
  });

  // =========================================
  // Manual Dismiss Tests
  // =========================================

  it('dismisses toast when close button is clicked', async () => {
    const user = userEvent.setup({ advanceTimers: vi.advanceTimersByTime });

    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));
    expect(screen.getByText('Success message')).toBeInTheDocument();

    // Find and click the close button (X icon)
    const closeButton = screen.getByRole('alert').querySelector('button');
    if (closeButton) {
      fireEvent.click(closeButton);
    }

    expect(screen.queryByText('Success message')).not.toBeInTheDocument();
  });

  // =========================================
  // Multiple Toasts Tests
  // =========================================

  it('shows multiple toasts', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));
    fireEvent.click(screen.getByText('Show Error'));

    expect(screen.getByText('Success message')).toBeInTheDocument();
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  it('stacks multiple toasts', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));
    fireEvent.click(screen.getByText('Show Warning'));
    fireEvent.click(screen.getByText('Show Info'));

    const alerts = screen.getAllByRole('alert');
    expect(alerts).toHaveLength(3);
  });

  // =========================================
  // Accessibility Tests
  // =========================================

  it('toast has role="alert"', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));

    expect(screen.getByRole('alert')).toBeInTheDocument();
  });

  it('close button is accessible', async () => {
    render(
      <ToastProvider>
        <TestToastTrigger />
      </ToastProvider>
    );

    fireEvent.click(screen.getByText('Show Success'));

    const closeButton = screen.getByRole('alert').querySelector('button');
    expect(closeButton).toBeInTheDocument();
  });
});

describe('useToast', () => {
  // =========================================
  // Context Error Tests
  // =========================================

  it('throws error when used outside provider', () => {
    // Suppress console.error for this test
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    function BadComponent() {
      useToast();
      return null;
    }

    expect(() => render(<BadComponent />)).toThrow(
      'useToast must be used within a ToastProvider'
    );

    consoleSpy.mockRestore();
  });

  // =========================================
  // Hook Interface Tests
  // =========================================

  it('returns toast methods', () => {
    let toastMethods: ReturnType<typeof useToast> | null = null;

    function TestComponent() {
      toastMethods = useToast();
      return null;
    }

    render(
      <ToastProvider>
        <TestComponent />
      </ToastProvider>
    );

    expect(toastMethods).not.toBeNull();
    expect(toastMethods!.success).toBeInstanceOf(Function);
    expect(toastMethods!.error).toBeInstanceOf(Function);
    expect(toastMethods!.warning).toBeInstanceOf(Function);
    expect(toastMethods!.info).toBeInstanceOf(Function);
  });
});
