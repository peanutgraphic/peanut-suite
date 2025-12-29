import { Timestamp } from "firebase-admin/firestore";
import { AppSource } from "./PeanutAccount";

/**
 * Shared Venue - synchronized across all Peanut apps
 * Firestore collection: /venues/{venue_id}
 */
export interface SharedVenue {
  venue_id: string;

  // Basic Info
  name: string;
  venue_type: VenueType;

  // Location
  address: string;
  city: string;
  state: string;
  zip: string;
  latitude?: number;
  longitude?: number;

  // Contact
  phone?: string;
  email?: string;
  website?: string;

  // Booker Contact (from Notebook)
  booker_name?: string;
  booker_email?: string;
  booker_phone?: string;
  preferred_contact_method?: ContactMethod;

  // Capacity & Tech (from Festival)
  capacity?: number;
  stage_type?: StageType;
  has_green_room: boolean;
  has_sound_system: boolean;
  has_lighting: boolean;
  has_mic_stand: boolean;

  // Business Terms (from Notebook/Booker)
  typical_pay_range_low?: number;
  typical_pay_range_high?: number;
  pay_type?: PayType;
  provides_food: boolean;
  provides_drinks: boolean;
  provides_lodging: boolean;
  provides_travel: boolean;

  // Ratings (aggregated)
  average_rating?: number;
  total_reviews?: number;

  // Source tracking
  created_by_app: AppSource;
  created_by_peanut_id: string;

  // Status
  is_verified: boolean;
  is_active: boolean;

  // Timestamps
  created_at: Timestamp;
  updated_at: Timestamp;
}

export type VenueType =
  | "club"
  | "bar"
  | "theater"
  | "restaurant"
  | "outdoor"
  | "corporate"
  | "other";

export type ContactMethod = "email" | "phone" | "text" | "dm";

export type StageType = "dedicated" | "corner" | "floor" | "raised";

export type PayType = "flat" | "door" | "percentage" | "tips" | "none";

/**
 * Request body for creating a venue
 */
export interface CreateVenueRequest {
  name: string;
  venue_type: VenueType;
  address: string;
  city: string;
  state: string;
  zip?: string;
  latitude?: number;
  longitude?: number;
  phone?: string;
  email?: string;
  website?: string;
}

/**
 * Request body for updating a venue
 */
export interface UpdateVenueRequest {
  name?: string;
  venue_type?: VenueType;
  address?: string;
  city?: string;
  state?: string;
  zip?: string;
  latitude?: number;
  longitude?: number;
  phone?: string;
  email?: string;
  website?: string;
  booker_name?: string;
  booker_email?: string;
  booker_phone?: string;
  preferred_contact_method?: ContactMethod;
  capacity?: number;
  stage_type?: StageType;
  has_green_room?: boolean;
  has_sound_system?: boolean;
  has_lighting?: boolean;
  has_mic_stand?: boolean;
  typical_pay_range_low?: number;
  typical_pay_range_high?: number;
  pay_type?: PayType;
  provides_food?: boolean;
  provides_drinks?: boolean;
  provides_lodging?: boolean;
  provides_travel?: boolean;
}

/**
 * Query parameters for venue search
 */
export interface VenueSearchQuery {
  q?: string;
  city?: string;
  state?: string;
  type?: VenueType;
  verified?: boolean;
  lat?: number;
  lng?: number;
  radius_miles?: number;
}

/**
 * API response for venue operations
 */
export interface VenueResponse {
  success: boolean;
  venue_id?: string;
  venue?: SharedVenue;
  venues?: SharedVenue[];
  error?: string;
  error_code?: string;
}
