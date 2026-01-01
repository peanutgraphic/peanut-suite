import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Select, { type SelectOption } from './Select';

const testOptions: SelectOption[] = [
  { value: 'option1', label: 'Option 1' },
  { value: 'option2', label: 'Option 2' },
  { value: 'option3', label: 'Option 3' },
];

describe('Select', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders select element', () => {
    render(<Select options={testOptions} data-testid="select" />);
    expect(screen.getByTestId('select')).toBeInTheDocument();
  });

  it('renders all options', () => {
    render(<Select options={testOptions} />);
    expect(screen.getByRole('option', { name: 'Option 1' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Option 2' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Option 3' })).toBeInTheDocument();
  });

  it('renders with label', () => {
    render(<Select options={testOptions} label="Country" name="country" />);
    expect(screen.getByText('Country')).toBeInTheDocument();
    expect(screen.getByLabelText('Country')).toBeInTheDocument();
  });

  it('associates label with select via id', () => {
    render(<Select options={testOptions} label="Status" id="status-id" />);
    const select = screen.getByLabelText('Status');
    expect(select).toHaveAttribute('id', 'status-id');
  });

  it('uses name as id fallback', () => {
    render(<Select options={testOptions} label="Category" name="category" />);
    const select = screen.getByLabelText('Category');
    expect(select).toHaveAttribute('id', 'category');
  });

  // =========================================
  // Placeholder Tests
  // =========================================

  it('renders placeholder option when provided', () => {
    render(<Select options={testOptions} placeholder="Select an option" />);
    expect(screen.getByRole('option', { name: 'Select an option' })).toBeInTheDocument();
  });

  it('placeholder option is disabled', () => {
    render(<Select options={testOptions} placeholder="Choose..." />);
    const placeholder = screen.getByRole('option', { name: 'Choose...' });
    expect(placeholder).toBeDisabled();
  });

  it('placeholder option has empty value', () => {
    render(<Select options={testOptions} placeholder="Select..." />);
    const placeholder = screen.getByRole('option', { name: 'Select...' });
    expect(placeholder).toHaveValue('');
  });

  // =========================================
  // Required Field Tests
  // =========================================

  it('shows required indicator', () => {
    render(<Select options={testOptions} label="Required Field" required />);
    expect(screen.getByText('*')).toBeInTheDocument();
  });

  it('required indicator has red color', () => {
    render(<Select options={testOptions} label="Required" required />);
    const asterisk = screen.getByText('*');
    expect(asterisk).toHaveClass('text-red-500');
  });

  // =========================================
  // Error State Tests
  // =========================================

  it('shows error message', () => {
    render(<Select options={testOptions} error="Please select an option" />);
    expect(screen.getByText('Please select an option')).toBeInTheDocument();
  });

  it('error message has red styling', () => {
    render(<Select options={testOptions} error="Invalid selection" />);
    const errorMessage = screen.getByText('Invalid selection');
    expect(errorMessage).toHaveClass('text-red-600');
  });

  it('applies error border class', () => {
    render(<Select options={testOptions} error="Error" data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toHaveClass('border-red-300');
  });

  // =========================================
  // Hint Text Tests
  // =========================================

  it('shows hint text', () => {
    render(<Select options={testOptions} hint="Choose your preferred option" />);
    expect(screen.getByText('Choose your preferred option')).toBeInTheDocument();
  });

  it('hint is hidden when error is present', () => {
    render(
      <Select options={testOptions} hint="Helpful hint" error="Error message" />
    );
    expect(screen.queryByText('Helpful hint')).not.toBeInTheDocument();
    expect(screen.getByText('Error message')).toBeInTheDocument();
  });

  // =========================================
  // Width Tests
  // =========================================

  it('applies full width by default', () => {
    render(<Select options={testOptions} data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toHaveClass('w-full');
  });

  it('can disable full width', () => {
    render(<Select options={testOptions} fullWidth={false} data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).not.toHaveClass('w-full');
  });

  // =========================================
  // Disabled Option Tests
  // =========================================

  it('renders disabled options', () => {
    const optionsWithDisabled: SelectOption[] = [
      { value: 'enabled', label: 'Enabled Option' },
      { value: 'disabled', label: 'Disabled Option', disabled: true },
    ];

    render(<Select options={optionsWithDisabled} />);

    const enabledOption = screen.getByRole('option', { name: 'Enabled Option' });
    const disabledOption = screen.getByRole('option', { name: 'Disabled Option' });

    expect(enabledOption).not.toBeDisabled();
    expect(disabledOption).toBeDisabled();
  });

  // =========================================
  // Disabled Select Tests
  // =========================================

  it('supports disabled state', () => {
    render(<Select options={testOptions} disabled data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toBeDisabled();
  });

  it('applies disabled styling', () => {
    render(<Select options={testOptions} disabled data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toHaveClass('disabled:bg-slate-50');
  });

  // =========================================
  // Value and Change Tests
  // =========================================

  it('handles value changes', async () => {
    const onChange = vi.fn();
    const user = userEvent.setup();

    render(<Select options={testOptions} onChange={onChange} data-testid="select" />);

    await user.selectOptions(screen.getByTestId('select'), 'option2');
    expect(onChange).toHaveBeenCalled();
  });

  it('supports controlled value', () => {
    render(<Select options={testOptions} value="option2" readOnly data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toHaveValue('option2');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    render(<Select options={testOptions} className="custom-select" data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toHaveClass('custom-select');
  });

  // =========================================
  // Chevron Icon Tests
  // =========================================

  it('renders chevron icon', () => {
    const { container } = render(<Select options={testOptions} />);
    const svg = container.querySelector('svg');
    expect(svg).toBeInTheDocument();
  });

  // =========================================
  // Empty Options Tests
  // =========================================

  it('handles empty options array', () => {
    render(<Select options={[]} data-testid="select" />);
    const select = screen.getByTestId('select');
    expect(select).toBeInTheDocument();
  });

  it('shows only placeholder when no options', () => {
    render(<Select options={[]} placeholder="No options available" />);
    const options = screen.getAllByRole('option');
    expect(options).toHaveLength(1);
    expect(options[0]).toHaveTextContent('No options available');
  });
});
