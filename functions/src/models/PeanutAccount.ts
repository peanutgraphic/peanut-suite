import { Timestamp } from "firebase-admin/firestore";

/**
 * Core Peanut Account - shared identity across all Peanut apps
 * Firestore collection: /accounts/{peanut_id}
 */
export interface PeanutAccount {
  peanut_id: string;
  email: string;
  display_name: string;
  avatar_url?: string;

  // Auth
  auth_providers: AuthProvider[];
  firebase_uid: string;

  // Linked profiles
  notebook_enabled: boolean;
  booker_performer_id?: string;
  booker_customer_id?: string;
  festival_organizer_id?: string;

  // Preferences
  email_notifications: boolean;
  marketing_opt_in: boolean;

  // Metadata
  created_at: Timestamp;
  updated_at: Timestamp;
  last_login_at: Timestamp;
  last_app_used: AppSource;
}

export type AuthProvider = "apple" | "google" | "email";
export type AppSource = "notebook" | "booker" | "festival";

/**
 * Lookup document for Firebase UID -> Peanut ID mapping
 * Firestore collection: /accounts_by_uid/{firebase_uid}
 */
export interface AccountByUid {
  peanut_id: string;
  created_at: Timestamp;
}

/**
 * Request body for creating a new Peanut Account
 */
export interface CreateAccountRequest {
  email: string;
  display_name: string;
  auth_provider: AuthProvider;
  avatar_url?: string;
  app_source: AppSource;
}

/**
 * Request body for updating a Peanut Account
 */
export interface UpdateAccountRequest {
  display_name?: string;
  avatar_url?: string;
  email_notifications?: boolean;
  marketing_opt_in?: boolean;
}

/**
 * Request body for linking to Booker profile
 */
export interface LinkBookerRequest {
  performer_id?: string;
  customer_id?: string;
}

/**
 * Request body for linking to Festival
 */
export interface LinkFestivalRequest {
  festival_id: string;
  role: "organizer" | "volunteer" | "performer";
}

/**
 * API response for account operations
 */
export interface AccountResponse {
  success: boolean;
  peanut_id?: string;
  account?: Partial<PeanutAccount>;
  error?: string;
  error_code?: string;
}
