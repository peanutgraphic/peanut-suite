import { Timestamp } from "firebase-admin/firestore";

/**
 * Shared Booking - cross-app booking sync
 * Firestore collection: /bookings/{booking_id}
 */
export interface SharedBooking {
  booking_id: string;
  booking_number: string; // Human-readable: PB-2024-0001 or PF-2024-0001

  // Source
  source: BookingSource;
  source_event_id?: string; // Festival show ID if applicable

  // Parties
  performer_peanut_id: string;
  customer_peanut_id?: string; // Booker customer
  festival_id?: string; // If from Festival

  // Event Details
  event_title: string;
  event_date: Timestamp;
  event_start_time: string; // "20:00"
  event_end_time?: string;
  set_length_minutes?: number;

  // Location
  venue_id?: string; // Link to shared venue
  venue_name: string;
  venue_address?: string;
  venue_city: string;
  venue_state: string;

  // Show Details
  show_type: ShowType;
  slot_position?: number; // Position in lineup
  slot_type?: SlotType;

  // Financial
  payment_amount?: number;
  payment_status: PaymentStatus;

  // Status
  booking_status: BookingStatus;
  performer_confirmed: boolean;

  // Notebook Sync
  notebook_imported: boolean;
  notebook_show_id?: string; // Local Notebook Show ID

  // Timestamps
  created_at: Timestamp;
  updated_at: Timestamp;
}

export type BookingSource = "booker" | "festival";

export type ShowType =
  | "open_mic"
  | "bringer"
  | "booked"
  | "feature"
  | "headliner"
  | "festival"
  | "corporate"
  | "private";

export type SlotType = "host" | "opener" | "middle" | "feature" | "headliner";

export type PaymentStatus =
  | "pending"
  | "deposit_paid"
  | "paid"
  | "cancelled";

export type BookingStatus =
  | "pending"
  | "confirmed"
  | "completed"
  | "cancelled"
  | "no_show";

/**
 * Request body for creating a booking (from Booker or Festival)
 */
export interface CreateBookingRequest {
  booking_number: string;
  source: BookingSource;
  source_event_id?: string;
  performer_peanut_id: string;
  customer_peanut_id?: string;
  festival_id?: string;
  event_title: string;
  event_date: string; // ISO date string
  event_start_time: string;
  event_end_time?: string;
  set_length_minutes?: number;
  venue_id?: string;
  venue_name: string;
  venue_address?: string;
  venue_city: string;
  venue_state: string;
  show_type: ShowType;
  slot_position?: number;
  slot_type?: SlotType;
  payment_amount?: number;
}

/**
 * Request body for updating booking status
 */
export interface UpdateBookingRequest {
  booking_status?: BookingStatus;
  payment_status?: PaymentStatus;
  performer_confirmed?: boolean;
  set_length_minutes?: number;
  slot_position?: number;
  slot_type?: SlotType;
}

/**
 * Request body for linking booking to Notebook
 */
export interface LinkNotebookRequest {
  notebook_show_id: string;
}

/**
 * Query parameters for booking search
 */
export interface BookingSearchQuery {
  status?: BookingStatus;
  from_date?: string;
  to_date?: string;
  source?: BookingSource;
  notebook_imported?: boolean;
}

/**
 * API response for booking operations
 */
export interface BookingResponse {
  success: boolean;
  booking_id?: string;
  booking?: SharedBooking;
  bookings?: SharedBooking[];
  error?: string;
  error_code?: string;
}
