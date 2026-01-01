import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import EmptyState, { emptyStates, Skeleton, TableSkeleton } from './EmptyState';

describe('EmptyState', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders title', () => {
    render(<EmptyState title="No data available" />);
    expect(screen.getByText('No data available')).toBeInTheDocument();
  });

  it('title is an h3 element', () => {
    render(<EmptyState title="No items" />);
    expect(screen.getByRole('heading', { level: 3 })).toHaveTextContent('No items');
  });

  // =========================================
  // Description Tests
  // =========================================

  it('renders description when provided', () => {
    render(
      <EmptyState
        title="No data"
        description="Try adjusting your filters"
      />
    );
    expect(screen.getByText('Try adjusting your filters')).toBeInTheDocument();
  });

  it('does not render description when not provided', () => {
    render(<EmptyState title="Title Only" />);
    expect(screen.queryByText(/description/i)).not.toBeInTheDocument();
  });

  // =========================================
  // Action Tests
  // =========================================

  it('renders action when provided', () => {
    render(
      <EmptyState
        title="No links"
        action={<button>Create Link</button>}
      />
    );
    expect(screen.getByRole('button', { name: 'Create Link' })).toBeInTheDocument();
  });

  // =========================================
  // Custom Icon Tests
  // =========================================

  it('renders custom icon when provided', () => {
    render(
      <EmptyState
        title="Custom"
        icon={<span data-testid="custom-icon">*</span>}
      />
    );
    expect(screen.getByTestId('custom-icon')).toBeInTheDocument();
  });

  // =========================================
  // Compact Mode Tests
  // =========================================

  it('applies compact styling when compact is true', () => {
    const { container } = render(<EmptyState title="Compact" compact />);
    const wrapper = container.firstChild;
    expect(wrapper).toHaveClass('py-8', 'px-4');
  });

  it('applies full styling when compact is false', () => {
    const { container } = render(<EmptyState title="Full" compact={false} />);
    const wrapper = container.firstChild;
    expect(wrapper).toHaveClass('py-16', 'px-6');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(<EmptyState title="Test" className="custom-empty" />);
    expect(container.querySelector('.custom-empty')).toBeInTheDocument();
  });

  // =========================================
  // Type Variations Tests
  // =========================================

  it('renders with links type', () => {
    render(<EmptyState type="links" title="No links" />);
    expect(screen.getByText('No links')).toBeInTheDocument();
  });

  it('renders with contacts type', () => {
    render(<EmptyState type="contacts" title="No contacts" />);
    expect(screen.getByText('No contacts')).toBeInTheDocument();
  });

  it('renders with utm type', () => {
    render(<EmptyState type="utm" title="No campaigns" />);
    expect(screen.getByText('No campaigns')).toBeInTheDocument();
  });

  it('renders with visitors type', () => {
    render(<EmptyState type="visitors" title="No visitors" />);
    expect(screen.getByText('No visitors')).toBeInTheDocument();
  });

  it('renders with analytics type', () => {
    render(<EmptyState type="analytics" title="No data" />);
    expect(screen.getByText('No data')).toBeInTheDocument();
  });

  it('renders with monitor type', () => {
    render(<EmptyState type="monitor" title="No sites" />);
    expect(screen.getByText('No sites')).toBeInTheDocument();
  });

  it('renders with webhooks type', () => {
    render(<EmptyState type="webhooks" title="No webhooks" />);
    expect(screen.getByText('No webhooks')).toBeInTheDocument();
  });

  it('renders with popups type', () => {
    render(<EmptyState type="popups" title="No popups" />);
    expect(screen.getByText('No popups')).toBeInTheDocument();
  });

  it('renders with search type', () => {
    render(<EmptyState type="search" title="No results" />);
    expect(screen.getByText('No results')).toBeInTheDocument();
  });

  it('renders with attribution type', () => {
    render(<EmptyState type="attribution" title="No attribution" />);
    expect(screen.getByText('No attribution')).toBeInTheDocument();
  });
});

describe('emptyStates configurations', () => {
  // =========================================
  // Pre-configured Empty States
  // =========================================

  it('has links configuration', () => {
    expect(emptyStates.links).toBeDefined();
    expect(emptyStates.links.type).toBe('links');
    expect(emptyStates.links.title).toBe('No links yet');
  });

  it('has contacts configuration', () => {
    expect(emptyStates.contacts).toBeDefined();
    expect(emptyStates.contacts.type).toBe('contacts');
    expect(emptyStates.contacts.title).toBe('No contacts yet');
  });

  it('has utm configuration', () => {
    expect(emptyStates.utm).toBeDefined();
    expect(emptyStates.utm.type).toBe('utm');
    expect(emptyStates.utm.title).toBe('No UTM campaigns yet');
  });

  it('has webhooks configuration', () => {
    expect(emptyStates.webhooks).toBeDefined();
    expect(emptyStates.webhooks.type).toBe('webhooks');
  });

  it('has monitor configuration', () => {
    expect(emptyStates.monitor).toBeDefined();
    expect(emptyStates.monitor.type).toBe('monitor');
  });

  it('has analytics configuration', () => {
    expect(emptyStates.analytics).toBeDefined();
    expect(emptyStates.analytics.type).toBe('analytics');
  });

  it('has popups configuration', () => {
    expect(emptyStates.popups).toBeDefined();
    expect(emptyStates.popups.type).toBe('popups');
  });

  it('has visitors configuration', () => {
    expect(emptyStates.visitors).toBeDefined();
    expect(emptyStates.visitors.type).toBe('visitors');
  });

  it('has attribution configuration', () => {
    expect(emptyStates.attribution).toBeDefined();
    expect(emptyStates.attribution.type).toBe('attribution');
  });

  it('has search configuration', () => {
    expect(emptyStates.search).toBeDefined();
    expect(emptyStates.search.type).toBe('search');
    expect(emptyStates.search.title).toBe('No results found');
  });

  it('all configurations have title and description', () => {
    Object.values(emptyStates).forEach((config) => {
      expect(config.title).toBeDefined();
      expect(config.description).toBeDefined();
    });
  });
});

describe('Skeleton', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders skeleton element', () => {
    const { container } = render(<Skeleton />);
    expect(container.firstChild).toBeInTheDocument();
  });

  it('applies animate-pulse class', () => {
    const { container } = render(<Skeleton />);
    expect(container.firstChild).toHaveClass('animate-pulse');
  });

  it('applies background color', () => {
    const { container } = render(<Skeleton />);
    expect(container.firstChild).toHaveClass('bg-slate-200');
  });

  it('applies rounded corners', () => {
    const { container } = render(<Skeleton />);
    expect(container.firstChild).toHaveClass('rounded');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(<Skeleton className="h-4 w-full" />);
    expect(container.firstChild).toHaveClass('h-4', 'w-full');
  });
});

describe('TableSkeleton', () => {
  // =========================================
  // Default Rendering Tests
  // =========================================

  it('renders default rows and columns', () => {
    render(<TableSkeleton />);
    // Default is 5 rows with 4 columns each = 20 skeleton elements
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBe(20);
  });

  // =========================================
  // Custom Rows and Columns Tests
  // =========================================

  it('renders custom number of rows', () => {
    render(<TableSkeleton rows={3} columns={4} />);
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBe(12);
  });

  it('renders custom number of columns', () => {
    render(<TableSkeleton rows={5} columns={2} />);
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBe(10);
  });

  it('renders single row', () => {
    render(<TableSkeleton rows={1} columns={3} />);
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toBe(3);
  });
});
