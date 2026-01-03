// CSV Export Utility
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function exportToCSV(
  data: any[],
  columns: { key: string; header: string }[],
  filename: string
): void {
  if (data.length === 0) {
    return;
  }

  // Create CSV header row
  const headers = columns.map((col) => `"${col.header}"`).join(',');

  // Create CSV data rows
  const rows = data.map((item) =>
    columns
      .map((col) => {
        const value = item[col.key];
        // Handle different value types
        if (value === null || value === undefined) return '""';
        if (typeof value === 'string') return `"${value.replace(/"/g, '""')}"`;
        if (Array.isArray(value)) return `"${value.join(', ')}"`;
        if (typeof value === 'object') return `"${JSON.stringify(value).replace(/"/g, '""')}"`;
        return `"${value}"`;
      })
      .join(',')
  );

  // Combine and create blob
  const csv = [headers, ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });

  // Download
  downloadBlob(blob, `${filename}.csv`);
}

// JSON Export Utility
export function exportToJSON<T>(data: T[], filename: string): void {
  const json = JSON.stringify(data, null, 2);
  const blob = new Blob([json], { type: 'application/json' });
  downloadBlob(blob, `${filename}.json`);
}

// PDF Export Utility (lazy-loaded to reduce bundle size)
export async function exportToPDF(
  elementId: string,
  filename: string,
  options?: {
    orientation?: 'portrait' | 'landscape';
    title?: string;
  }
): Promise<void> {
  const element = document.getElementById(elementId);
  if (!element) {
    throw new Error(`Element with id "${elementId}" not found`);
  }

  try {
    // Dynamically import heavy PDF libraries only when needed
    const [{ default: jsPDF }, { default: html2canvas }] = await Promise.all([
      import('jspdf'),
      import('html2canvas'),
    ]);

    const canvas = await html2canvas(element, {
      scale: 2,
      useCORS: true,
      logging: false,
    });

    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF({
      orientation: options?.orientation || 'portrait',
      unit: 'mm',
      format: 'a4',
    });

    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();

    // Add title if provided
    if (options?.title) {
      pdf.setFontSize(18);
      pdf.text(options.title, pageWidth / 2, 15, { align: 'center' });
    }

    // Calculate dimensions maintaining aspect ratio
    const imgWidth = pageWidth - 20;
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
    const startY = options?.title ? 25 : 10;

    // Add image (may need to split across pages for long content)
    if (imgHeight <= pageHeight - startY - 10) {
      pdf.addImage(imgData, 'PNG', 10, startY, imgWidth, imgHeight);
    } else {
      // Handle multi-page content
      let remainingHeight = imgHeight;
      let currentY = startY;
      let sourceY = 0;

      while (remainingHeight > 0) {
        const chunkHeight = Math.min(remainingHeight, pageHeight - currentY - 10);
        const sourceHeight = (chunkHeight / imgHeight) * canvas.height;

        // Create a temp canvas for this chunk
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = sourceHeight;
        const tempCtx = tempCanvas.getContext('2d');
        if (tempCtx) {
          tempCtx.drawImage(
            canvas,
            0, sourceY,
            canvas.width, sourceHeight,
            0, 0,
            canvas.width, sourceHeight
          );
          pdf.addImage(tempCanvas.toDataURL('image/png'), 'PNG', 10, currentY, imgWidth, chunkHeight);
        }

        remainingHeight -= chunkHeight;
        sourceY += sourceHeight;

        if (remainingHeight > 0) {
          pdf.addPage();
          currentY = 10;
        }
      }
    }

    pdf.save(`${filename}.pdf`);
  } catch (error) {
    throw error instanceof Error ? error : new Error('Error generating PDF');
  }
}

// Helper function to download a blob
function downloadBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

// UTM Export columns
export const utmExportColumns = [
  { key: 'utm_campaign', header: 'Campaign' },
  { key: 'utm_source', header: 'Source' },
  { key: 'utm_medium', header: 'Medium' },
  { key: 'utm_term', header: 'Term' },
  { key: 'utm_content', header: 'Content' },
  { key: 'base_url', header: 'Base URL' },
  { key: 'full_url', header: 'Full URL' },
  { key: 'click_count', header: 'Clicks' },
  { key: 'created_at', header: 'Created' },
];

// Links Export columns
export const linksExportColumns = [
  { key: 'title', header: 'Title' },
  { key: 'destination_url', header: 'Destination URL' },
  { key: 'short_url', header: 'Short URL' },
  { key: 'slug', header: 'Slug' },
  { key: 'click_count', header: 'Clicks' },
  { key: 'unique_clicks', header: 'Unique Clicks' },
  { key: 'status', header: 'Status' },
  { key: 'created_at', header: 'Created' },
];

// Contacts Export columns
export const contactsExportColumns = [
  { key: 'email', header: 'Email' },
  { key: 'first_name', header: 'First Name' },
  { key: 'last_name', header: 'Last Name' },
  { key: 'phone', header: 'Phone' },
  { key: 'company', header: 'Company' },
  { key: 'status', header: 'Status' },
  { key: 'source', header: 'Source' },
  { key: 'tags', header: 'Tags' },
  { key: 'score', header: 'Score' },
  { key: 'created_at', header: 'Created' },
];
