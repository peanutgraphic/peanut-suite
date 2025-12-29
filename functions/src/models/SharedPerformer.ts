import { Timestamp } from "firebase-admin/firestore";

/**
 * Shared Performer Profile - public-facing performer profile
 * Owned by Booker but readable by all apps
 * Firestore collection: /performers/{performer_id}
 */
export interface SharedPerformerProfile {
  performer_id: string;
  peanut_id: string; // Links to Peanut Account

  // Basic Info
  stage_name: string;
  legal_name?: string; // Private, only for bookings
  tagline?: string;
  bio: string;

  // Media
  profile_photo_url?: string;
  gallery_urls: string[]; // Up to 5 for Pro
  video_urls: string[]; // Up to 3 for Pro

  // Professional
  experience_years?: number;
  act_types: string[]; // "Stand-up", "Improv", "Sketch", etc.
  clean_rating: CleanRating;

  // Contact (public)
  website_url?: string;
  instagram?: string;
  twitter?: string;
  tiktok?: string;
  youtube?: string;

  // Contact (private - for confirmed bookings only)
  email?: string;
  phone?: string;

  // Booking
  hourly_rate?: number;
  minimum_fee?: number;
  travel_willing: boolean;
  travel_radius_miles?: number;
  location_city: string;
  location_state: string;

  // Status
  is_verified: boolean;
  is_featured: boolean;
  is_active: boolean;

  // Computed (updated by triggers)
  completed_bookings: number;
  average_rating: number;
  total_reviews: number;
  achievement_level: AchievementLevel;

  // Notebook contribution (opt-in)
  notebook_linked: boolean;
  total_shows_logged?: number; // From Notebook
  years_performing?: number; // From Notebook
  home_venues?: string[]; // From Notebook

  // Timestamps
  created_at: Timestamp;
  updated_at: Timestamp;
}

export type CleanRating = "clean" | "pg13" | "adult";
export type AchievementLevel = "bronze" | "silver" | "gold" | "platinum";

/**
 * Request to create a performer profile
 */
export interface CreatePerformerRequest {
  stage_name: string;
  bio: string;
  act_types: string[];
  clean_rating: CleanRating;
  location_city: string;
  location_state: string;
  tagline?: string;
  website_url?: string;
  instagram?: string;
  travel_willing?: boolean;
  travel_radius_miles?: number;
  hourly_rate?: number;
  minimum_fee?: number;
}

/**
 * Request to update a performer profile
 */
export interface UpdatePerformerRequest {
  stage_name?: string;
  bio?: string;
  tagline?: string;
  act_types?: string[];
  clean_rating?: CleanRating;
  profile_photo_url?: string;
  gallery_urls?: string[];
  video_urls?: string[];
  website_url?: string;
  instagram?: string;
  twitter?: string;
  tiktok?: string;
  youtube?: string;
  hourly_rate?: number;
  minimum_fee?: number;
  travel_willing?: boolean;
  travel_radius_miles?: number;
  location_city?: string;
  location_state?: string;
  is_active?: boolean;
}

/**
 * Notebook stats contribution
 */
export interface NotebookStatsRequest {
  total_shows: number;
  years_performing: number;
  home_venues: string[];
}

/**
 * Query parameters for performer search
 */
export interface PerformerSearchQuery {
  city?: string;
  state?: string;
  act_type?: string;
  rating_min?: number;
  verified?: boolean;
  clean_rating?: CleanRating;
}

/**
 * API response for performer operations
 */
export interface PerformerResponse {
  success: boolean;
  performer_id?: string;
  performer?: SharedPerformerProfile;
  performers?: SharedPerformerProfile[];
  error?: string;
  error_code?: string;
}
