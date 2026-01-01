import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Pagination from './Pagination';

describe('Pagination', () => {
  const defaultProps = {
    page: 1,
    totalPages: 5,
    total: 50,
    perPage: 10,
    onPageChange: vi.fn(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  // =========================================
  // Visibility Tests
  // =========================================

  it('renders when totalPages > 1', () => {
    render(<Pagination {...defaultProps} />);
    expect(screen.getByText('Previous')).toBeInTheDocument();
    expect(screen.getByText('Next')).toBeInTheDocument();
  });

  it('does not render when totalPages is 1', () => {
    const { container } = render(
      <Pagination {...defaultProps} totalPages={1} />
    );
    expect(container.firstChild).toBeNull();
  });

  it('does not render when totalPages is 0', () => {
    const { container } = render(
      <Pagination {...defaultProps} totalPages={0} total={0} />
    );
    expect(container.firstChild).toBeNull();
  });

  // =========================================
  // Results Text Tests
  // =========================================

  it('shows correct results text on first page', () => {
    render(<Pagination {...defaultProps} />);
    // Check for the results text container
    const resultsText = screen.getByText(/Showing/);
    expect(resultsText).toHaveTextContent('Showing 1 to 10 of 50 results');
  });

  it('shows correct results text on middle page', () => {
    render(<Pagination {...defaultProps} page={3} />);
    expect(screen.getByText('21')).toBeInTheDocument();
    expect(screen.getByText('30')).toBeInTheDocument();
  });

  it('shows correct results text on last page with partial results', () => {
    render(
      <Pagination
        {...defaultProps}
        page={6}
        totalPages={6}
        total={53}
        perPage={10}
      />
    );
    const resultsText = screen.getByText(/Showing/);
    expect(resultsText).toHaveTextContent('Showing 51 to 53 of 53 results');
  });

  // =========================================
  // Navigation Button Tests
  // =========================================

  it('disables Previous button on first page', () => {
    render(<Pagination {...defaultProps} page={1} />);
    expect(screen.getByRole('button', { name: /previous/i })).toBeDisabled();
  });

  it('enables Previous button on pages after first', () => {
    render(<Pagination {...defaultProps} page={2} />);
    expect(screen.getByRole('button', { name: /previous/i })).not.toBeDisabled();
  });

  it('disables Next button on last page', () => {
    render(<Pagination {...defaultProps} page={5} />);
    expect(screen.getByRole('button', { name: /next/i })).toBeDisabled();
  });

  it('enables Next button on pages before last', () => {
    render(<Pagination {...defaultProps} page={4} />);
    expect(screen.getByRole('button', { name: /next/i })).not.toBeDisabled();
  });

  // =========================================
  // Page Change Tests
  // =========================================

  it('calls onPageChange with previous page', async () => {
    const onPageChange = vi.fn();
    const user = userEvent.setup();

    render(<Pagination {...defaultProps} page={3} onPageChange={onPageChange} />);

    await user.click(screen.getByRole('button', { name: /previous/i }));
    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  it('calls onPageChange with next page', async () => {
    const onPageChange = vi.fn();
    const user = userEvent.setup();

    render(<Pagination {...defaultProps} page={3} onPageChange={onPageChange} />);

    await user.click(screen.getByRole('button', { name: /next/i }));
    expect(onPageChange).toHaveBeenCalledWith(4);
  });

  it('calls onPageChange when clicking page number', async () => {
    const onPageChange = vi.fn();
    const user = userEvent.setup();

    render(<Pagination {...defaultProps} onPageChange={onPageChange} />);

    // Click on page 3
    await user.click(screen.getByRole('button', { name: '3' }));
    expect(onPageChange).toHaveBeenCalledWith(3);
  });

  // =========================================
  // Page Number Display Tests
  // =========================================

  it('shows all pages when totalPages <= 5', () => {
    render(<Pagination {...defaultProps} totalPages={4} />);

    expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '2' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '3' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '4' })).toBeInTheDocument();
  });

  it('shows ellipsis for many pages', () => {
    render(<Pagination {...defaultProps} totalPages={10} page={5} />);

    // Should show first page, ellipsis, current area, ellipsis, last page
    expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '10' })).toBeInTheDocument();
    // Ellipsis indicators
    expect(screen.getAllByText('...').length).toBeGreaterThan(0);
  });

  it('shows ellipsis only after first page when near start', () => {
    render(<Pagination {...defaultProps} totalPages={10} page={2} />);

    // At page 2, should not show ellipsis before current pages
    expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '2' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '3' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '10' })).toBeInTheDocument();
  });

  it('shows ellipsis only before last page when near end', () => {
    render(<Pagination {...defaultProps} totalPages={10} page={9} />);

    expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '8' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '9' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '10' })).toBeInTheDocument();
  });

  // =========================================
  // Active Page Styling Tests
  // =========================================

  it('applies active styling to current page', () => {
    render(<Pagination {...defaultProps} page={3} />);

    const activePage = screen.getByRole('button', { name: '3' });
    expect(activePage).toHaveClass('bg-primary-600', 'text-white');
  });

  it('applies inactive styling to non-current pages', () => {
    render(<Pagination {...defaultProps} page={1} />);

    const inactivePage = screen.getByRole('button', { name: '3' });
    expect(inactivePage).toHaveClass('text-slate-600');
    expect(inactivePage).not.toHaveClass('bg-primary-600');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(
      <Pagination {...defaultProps} className="custom-pagination" />
    );
    expect(container.querySelector('.custom-pagination')).toBeInTheDocument();
  });

  // =========================================
  // Edge Cases
  // =========================================

  it('handles page 1 of 2 correctly', () => {
    render(<Pagination {...defaultProps} totalPages={2} />);

    expect(screen.getByRole('button', { name: '1' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: '2' })).toBeInTheDocument();
    expect(screen.queryByText('...')).not.toBeInTheDocument();
  });

  it('handles being on the last page', () => {
    render(<Pagination {...defaultProps} page={5} totalPages={5} />);

    expect(screen.getByRole('button', { name: /previous/i })).not.toBeDisabled();
    expect(screen.getByRole('button', { name: /next/i })).toBeDisabled();
  });

  it('handles large total values', () => {
    render(
      <Pagination
        {...defaultProps}
        page={50}
        totalPages={100}
        total={1000}
        perPage={10}
      />
    );

    expect(screen.getByText('491')).toBeInTheDocument();
    expect(screen.getByText('500')).toBeInTheDocument();
    expect(screen.getByText('1000')).toBeInTheDocument();
  });
});
