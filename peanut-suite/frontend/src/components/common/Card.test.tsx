import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import Card, { CardHeader, StatCard } from './Card';

describe('Card', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders children', () => {
    render(<Card>Card content</Card>);
    expect(screen.getByText('Card content')).toBeInTheDocument();
  });

  it('applies default styling', () => {
    const { container } = render(<Card>Content</Card>);
    const card = container.firstChild;
    expect(card).toHaveClass('bg-white', 'rounded-xl', 'border', 'shadow-sm');
  });

  // =========================================
  // Padding Tests
  // =========================================

  it('applies medium padding by default', () => {
    const { container } = render(<Card>Content</Card>);
    const card = container.firstChild;
    expect(card).toHaveClass('p-6');
  });

  it('applies small padding', () => {
    const { container } = render(<Card padding="sm">Content</Card>);
    const card = container.firstChild;
    expect(card).toHaveClass('p-4');
  });

  it('applies large padding', () => {
    const { container } = render(<Card padding="lg">Content</Card>);
    const card = container.firstChild;
    expect(card).toHaveClass('p-8');
  });

  it('applies no padding', () => {
    const { container } = render(<Card padding="none">Content</Card>);
    const card = container.firstChild;
    expect(card).not.toHaveClass('p-4');
    expect(card).not.toHaveClass('p-6');
    expect(card).not.toHaveClass('p-8');
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(<Card className="custom-class">Content</Card>);
    const card = container.firstChild;
    expect(card).toHaveClass('custom-class');
  });

  // =========================================
  // Prop Passthrough Tests
  // =========================================

  it('passes through additional props', () => {
    render(<Card data-testid="card">Content</Card>);
    expect(screen.getByTestId('card')).toBeInTheDocument();
  });
});

describe('CardHeader', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders title', () => {
    render(<CardHeader title="Card Title" />);
    expect(screen.getByText('Card Title')).toBeInTheDocument();
  });

  it('title is an h3 element', () => {
    render(<CardHeader title="Card Title" />);
    expect(screen.getByRole('heading', { level: 3 })).toHaveTextContent('Card Title');
  });

  // =========================================
  // Description Tests
  // =========================================

  it('renders description when provided', () => {
    render(<CardHeader title="Title" description="Card description" />);
    expect(screen.getByText('Card description')).toBeInTheDocument();
  });

  it('does not render description container when not provided', () => {
    render(<CardHeader title="Title Only" />);
    expect(screen.queryByText(/description/i)).not.toBeInTheDocument();
  });

  // =========================================
  // Action Tests
  // =========================================

  it('renders action when provided', () => {
    render(
      <CardHeader
        title="Title"
        action={<button>Action Button</button>}
      />
    );
    expect(screen.getByRole('button', { name: 'Action Button' })).toBeInTheDocument();
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(<CardHeader title="Title" className="custom-header" />);
    expect(container.querySelector('.custom-header')).toBeInTheDocument();
  });
});

describe('StatCard', () => {
  // =========================================
  // Basic Rendering Tests
  // =========================================

  it('renders title and value', () => {
    render(<StatCard title="Total Users" value={1234} />);
    expect(screen.getByText('Total Users')).toBeInTheDocument();
    expect(screen.getByText('1234')).toBeInTheDocument();
  });

  it('renders string value', () => {
    render(<StatCard title="Status" value="Active" />);
    expect(screen.getByText('Active')).toBeInTheDocument();
  });

  // =========================================
  // Change Indicator Tests
  // =========================================

  it('renders increase change', () => {
    render(
      <StatCard
        title="Revenue"
        value="$10,000"
        change={{ value: 15, type: 'increase' }}
      />
    );
    expect(screen.getByText('+15%')).toBeInTheDocument();
  });

  it('increase has green styling', () => {
    render(
      <StatCard
        title="Revenue"
        value="$10,000"
        change={{ value: 15, type: 'increase' }}
      />
    );
    expect(screen.getByText('+15%')).toHaveClass('text-green-600');
  });

  it('renders decrease change', () => {
    render(
      <StatCard
        title="Churn"
        value="5%"
        change={{ value: 10, type: 'decrease' }}
      />
    );
    expect(screen.getByText('-10%')).toBeInTheDocument();
  });

  it('decrease has red styling', () => {
    render(
      <StatCard
        title="Churn"
        value="5%"
        change={{ value: 10, type: 'decrease' }}
      />
    );
    expect(screen.getByText('-10%')).toHaveClass('text-red-600');
  });

  it('renders neutral change', () => {
    render(
      <StatCard
        title="Users"
        value={100}
        change={{ value: 0, type: 'neutral' }}
      />
    );
    expect(screen.getByText('0%')).toBeInTheDocument();
  });

  it('neutral has gray styling', () => {
    render(
      <StatCard
        title="Users"
        value={100}
        change={{ value: 0, type: 'neutral' }}
      />
    );
    expect(screen.getByText('0%')).toHaveClass('text-slate-500');
  });

  // =========================================
  // Icon Tests
  // =========================================

  it('renders icon when provided', () => {
    render(
      <StatCard
        title="Messages"
        value={42}
        icon={<span data-testid="stat-icon">@</span>}
      />
    );
    expect(screen.getByTestId('stat-icon')).toBeInTheDocument();
  });

  // =========================================
  // Custom ClassName Tests
  // =========================================

  it('applies custom className', () => {
    const { container } = render(
      <StatCard title="Title" value={0} className="custom-stat" />
    );
    expect(container.querySelector('.custom-stat')).toBeInTheDocument();
  });
});
