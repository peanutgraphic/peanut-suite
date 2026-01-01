import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Input, { Textarea } from './Input';

describe('Input', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders input element', () => {
    render(<Input placeholder="Enter text" />);
    expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
  });

  it('renders with label', () => {
    render(<Input label="Email" name="email" />);
    expect(screen.getByText('Email')).toBeInTheDocument();
    expect(screen.getByLabelText('Email')).toBeInTheDocument();
  });

  it('associates label with input via id', () => {
    render(<Input label="Username" id="custom-id" />);
    const input = screen.getByLabelText('Username');
    expect(input).toHaveAttribute('id', 'custom-id');
  });

  it('uses name as id fallback', () => {
    render(<Input label="Password" name="password" />);
    const input = screen.getByLabelText('Password');
    expect(input).toHaveAttribute('id', 'password');
  });

  // =========================================
  // Required Field Tests
  // =========================================

  it('shows required indicator', () => {
    render(<Input label="Required Field" required />);
    expect(screen.getByText('*')).toBeInTheDocument();
  });

  it('required indicator has red color', () => {
    render(<Input label="Required Field" required />);
    const asterisk = screen.getByText('*');
    expect(asterisk).toHaveClass('text-red-500');
  });

  // =========================================
  // Error State Tests
  // =========================================

  it('shows error message', () => {
    render(<Input error="This field is required" />);
    expect(screen.getByText('This field is required')).toBeInTheDocument();
  });

  it('error message has red styling', () => {
    render(<Input error="Invalid email" />);
    const errorMessage = screen.getByText('Invalid email');
    expect(errorMessage).toHaveClass('text-red-600');
  });

  it('applies error border class', () => {
    render(<Input error="Error" data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveClass('border-red-300');
  });

  // =========================================
  // Hint/Helper Text Tests
  // =========================================

  it('shows hint text', () => {
    render(<Input hint="Enter your email address" />);
    expect(screen.getByText('Enter your email address')).toBeInTheDocument();
  });

  it('shows helper text (alias for hint)', () => {
    render(<Input helper="Password must be 8+ characters" />);
    expect(screen.getByText('Password must be 8+ characters')).toBeInTheDocument();
  });

  it('hint is hidden when error is present', () => {
    render(<Input hint="Helpful hint" error="Error message" />);
    expect(screen.queryByText('Helpful hint')).not.toBeInTheDocument();
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  // =========================================
  // Icon Tests
  // =========================================

  it('renders left icon', () => {
    render(<Input leftIcon={<span data-testid="left-icon">@</span>} />);
    expect(screen.getByTestId('left-icon')).toBeInTheDocument();
  });

  it('renders right icon', () => {
    render(<Input rightIcon={<span data-testid="right-icon">X</span>} />);
    expect(screen.getByTestId('right-icon')).toBeInTheDocument();
  });

  it('applies padding for left icon', () => {
    render(<Input leftIcon={<span>@</span>} data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveStyle({ paddingLeft: '44px' });
  });

  // =========================================
  // Width Tests
  // =========================================

  it('applies full width by default', () => {
    render(<Input data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveClass('w-full');
  });

  it('can disable full width', () => {
    render(<Input fullWidth={false} data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).not.toHaveClass('w-full');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    render(<Input className="custom-class" data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveClass('custom-class');
  });

  // =========================================
  // Input Type Tests
  // =========================================

  it('supports different input types', () => {
    render(<Input type="email" data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveAttribute('type', 'email');
  });

  it('supports password type', () => {
    render(<Input type="password" data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveAttribute('type', 'password');
  });

  // =========================================
  // Disabled State Tests
  // =========================================

  it('supports disabled state', () => {
    render(<Input disabled data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toBeDisabled();
  });

  it('applies disabled styling', () => {
    render(<Input disabled data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveClass('disabled:bg-slate-50');
  });

  // =========================================
  // Value and Change Tests
  // =========================================

  it('handles value changes', async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();

    render(<Input onChange={onChange} data-testid="input" />);

    await user.type(screen.getByTestId('input'), 'hello');
    expect(onChange).toHaveBeenCalled();
  });

  it('supports controlled value', () => {
    render(<Input value="test value" readOnly data-testid="input" />);
    const input = screen.getByTestId('input');
    expect(input).toHaveValue('test value');
  });
});

describe('Textarea', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders textarea element', () => {
    render(<Textarea placeholder="Enter description" />);
    expect(screen.getByPlaceholderText('Enter description')).toBeInTheDocument();
  });

  it('renders with label', () => {
    render(<Textarea label="Description" name="description" />);
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByLabelText('Description')).toBeInTheDocument();
  });

  // =========================================
  // Required Field Tests
  // =========================================

  it('shows required indicator', () => {
    render(<Textarea label="Bio" required />);
    expect(screen.getByText('*')).toBeInTheDocument();
  });

  // =========================================
  // Error State Tests
  // =========================================

  it('shows error message', () => {
    render(<Textarea error="This field is required" />);
    expect(screen.getByText('This field is required')).toBeInTheDocument();
  });

  it('error message has red styling', () => {
    render(<Textarea error="Too short" />);
    const errorMessage = screen.getByText('Too short');
    expect(errorMessage).toHaveClass('text-red-600');
  });

  // =========================================
  // Hint Text Tests
  // =========================================

  it('shows hint text', () => {
    render(<Textarea hint="Max 500 characters" />);
    expect(screen.getByText('Max 500 characters')).toBeInTheDocument();
  });

  it('hint is hidden when error is present', () => {
    render(<Textarea hint="Helpful hint" error="Error message" />);
    expect(screen.queryByText('Helpful hint')).not.toBeInTheDocument();
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  // =========================================
  // Width Tests
  // =========================================

  it('applies full width by default', () => {
    render(<Textarea data-testid="textarea" />);
    const textarea = screen.getByTestId('textarea');
    expect(textarea).toHaveClass('w-full');
  });

  // =========================================
  // Value and Change Tests
  // =========================================

  it('handles value changes', async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();

    render(<Textarea onChange={onChange} data-testid="textarea" />);

    await user.type(screen.getByTestId('textarea'), 'hello');
    expect(onChange).toHaveBeenCalled();
  });
});
