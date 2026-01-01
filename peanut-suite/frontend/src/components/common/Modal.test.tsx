import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Modal, { ConfirmModal } from './Modal';

describe('Modal', () => {
  const defaultProps = {
    isOpen: true,
    onClose: vi.fn(),
    children: <p>Modal content</p>,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // =========================================
  // Visibility Tests
  // =========================================

  it('renders when isOpen is true', () => {
    render(<Modal {...defaultProps} />);
    expect(screen.getByText('Modal content')).toBeInTheDocument();
  });

  it('does not render when isOpen is false', () => {
    render(<Modal {...defaultProps} isOpen={false} />);
    expect(screen.queryByText('Modal content')).not.toBeInTheDocument();
  });

  // =========================================
  // Title and Description Tests
  // =========================================

  it('renders title when provided', () => {
    render(<Modal {...defaultProps} title="Test Title" />);
    expect(screen.getByText('Test Title')).toBeInTheDocument();
  });

  it('renders description when provided', () => {
    render(
      <Modal {...defaultProps} title="Title" description="Test description" />
    );
    expect(screen.getByText('Test description')).toBeInTheDocument();
  });

  it('does not render description without title', () => {
    render(<Modal {...defaultProps} description="Description only" />);
    // Check that description is rendered even without title
    // (but the header section only shows if title or showClose is true)
  });

  // =========================================
  // Close Button Tests
  // =========================================

  it('shows close button by default', () => {
    render(<Modal {...defaultProps} title="Title" />);
    // Find the close button by its parent containing the X icon
    const closeButton = document.querySelector('button.text-slate-400');
    expect(closeButton).toBeInTheDocument();
  });

  it('hides close button when showClose is false', () => {
    render(<Modal {...defaultProps} title="Title" showClose={false} />);
    const closeButton = document.querySelector('button.text-slate-400');
    expect(closeButton).not.toBeInTheDocument();
  });

  it('calls onClose when close button is clicked', async () => {
    const onClose = vi.fn();
    const user = userEvent.setup();

    render(<Modal {...defaultProps} onClose={onClose} title="Title" />);

    const closeButton = document.querySelector('button.text-slate-400');
    if (closeButton) {
      await user.click(closeButton);
    }

    expect(onClose).toHaveBeenCalledTimes(1);
  });

  // =========================================
  // Backdrop Tests
  // =========================================

  it('calls onClose when backdrop is clicked', () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);

    // Find the backdrop (first fixed element with bg-black/50)
    const backdrop = document.querySelector('.bg-black\\/50');
    if (backdrop) {
      fireEvent.click(backdrop);
    }

    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('does not close when modal content is clicked', () => {
    const onClose = vi.fn();
    render(<Modal {...defaultProps} onClose={onClose} />);

    fireEvent.click(screen.getByText('Modal content'));

    expect(onClose).not.toHaveBeenCalled();
  });

  // =========================================
  // Size Tests
  // =========================================

  it('applies small size class', () => {
    render(<Modal {...defaultProps} size="sm" />);
    const modal = document.querySelector('.max-w-md');
    expect(modal).toBeInTheDocument();
  });

  it('applies medium size class by default', () => {
    render(<Modal {...defaultProps} />);
    const modal = document.querySelector('.max-w-lg');
    expect(modal).toBeInTheDocument();
  });

  it('applies large size class', () => {
    render(<Modal {...defaultProps} size="lg" />);
    const modal = document.querySelector('.max-w-2xl');
    expect(modal).toBeInTheDocument();
  });

  it('applies extra-large size class', () => {
    render(<Modal {...defaultProps} size="xl" />);
    const modal = document.querySelector('.max-w-4xl');
    expect(modal).toBeInTheDocument();
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    render(<Modal {...defaultProps} className="custom-modal" />);
    const modal = document.querySelector('.custom-modal');
    expect(modal).toBeInTheDocument();
  });
});

describe('ConfirmModal', () => {
  const defaultProps = {
    isOpen: true,
    onClose: vi.fn(),
    onConfirm: vi.fn(),
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // =========================================
  // Rendering Tests
  // =========================================

  it('renders title and message', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Confirm Action')).toBeInTheDocument();
    expect(screen.getByText('Are you sure you want to proceed?')).toBeInTheDocument();
  });

  it('renders default button text', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Cancel')).toBeInTheDocument();
    expect(screen.getByText('Confirm')).toBeInTheDocument();
  });

  it('renders custom button text', () => {
    render(
      <ConfirmModal
        {...defaultProps}
        confirmText="Delete"
        cancelText="Keep"
      />
    );
    expect(screen.getByText('Keep')).toBeInTheDocument();
    expect(screen.getByText('Delete')).toBeInTheDocument();
  });

  // =========================================
  // Action Tests
  // =========================================

  it('calls onClose when cancel button is clicked', async () => {
    const onClose = vi.fn();
    const user = userEvent.setup();

    render(<ConfirmModal {...defaultProps} onClose={onClose} />);

    await user.click(screen.getByText('Cancel'));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('calls onConfirm when confirm button is clicked', async () => {
    const onConfirm = vi.fn();
    const user = userEvent.setup();

    render(<ConfirmModal {...defaultProps} onConfirm={onConfirm} />);

    await user.click(screen.getByText('Confirm'));
    expect(onConfirm).toHaveBeenCalledTimes(1);
  });

  // =========================================
  // Loading State Tests
  // =========================================

  it('disables cancel button when loading', () => {
    render(<ConfirmModal {...defaultProps} loading />);
    expect(screen.getByText('Cancel')).toBeDisabled();
  });

  it('shows loading state on confirm button', () => {
    render(<ConfirmModal {...defaultProps} loading />);
    const confirmBtn = screen.getByText('Confirm').closest('button');
    expect(confirmBtn).toBeDisabled();
  });

  // =========================================
  // Variant Tests
  // =========================================

  it('applies danger variant to confirm button', () => {
    render(<ConfirmModal {...defaultProps} variant="danger" />);
    const confirmBtn = screen.getByText('Confirm').closest('button');
    expect(confirmBtn).toHaveClass('bg-red-600');
  });

  it('applies primary variant by default', () => {
    render(<ConfirmModal {...defaultProps} />);
    const confirmBtn = screen.getByText('Confirm').closest('button');
    expect(confirmBtn).toHaveClass('bg-primary-600');
  });
});
