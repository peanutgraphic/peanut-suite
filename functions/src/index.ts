import * as admin from "firebase-admin";
import * as functions from "firebase-functions";
import express from "express";
import cors from "cors";

// Initialize Firebase Admin
admin.initializeApp();

// Import API routers
import accountRouter from "./api/account";
import performerRouter from "./api/performer";
import venueRouter from "./api/venue";
import bookingRouter from "./api/booking";

// Create Express app
const app = express();

// Middleware
app.use(cors({ origin: true }));
app.use(express.json());

// Health check
app.get("/api/v1/health", (req, res) => {
  res.json({
    status: "ok",
    service: "peanut-suite-api",
    version: "1.0.0",
    timestamp: new Date().toISOString(),
  });
});

// Mount API routes
app.use("/api/v1/account", accountRouter);
app.use("/api/v1/performer", performerRouter);
app.use("/api/v1/venue", venueRouter);
app.use("/api/v1/booking", bookingRouter);

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: "Endpoint not found",
    error_code: "API_404",
  });
});

// Error handler
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error("Unhandled error:", err);
  res.status(500).json({
    success: false,
    error: "Internal server error",
    error_code: "API_500",
  });
});

// Export the Express app as a Cloud Function
export const api = functions.https.onRequest(app);

// ============================================
// FIRESTORE TRIGGERS
// ============================================

/**
 * Trigger: When a new account is created, send welcome notification
 */
export const onAccountCreated = functions.firestore
  .document("accounts/{peanutId}")
  .onCreate(async (snapshot, context) => {
    const _account = snapshot.data();
    const peanutId = context.params.peanutId;

    console.log(`New Peanut Account created: ${peanutId}`);

    // TODO: Send welcome email
    // TODO: Initialize default preferences
    // TODO: Create app-specific subcollections if needed

    return null;
  });

/**
 * Trigger: When account is updated, sync to linked profiles
 */
export const onAccountUpdated = functions.firestore
  .document("accounts/{peanutId}")
  .onUpdate(async (change, context) => {
    const before = change.before.data();
    const after = change.after.data();
    const peanutId = context.params.peanutId;

    // Check if display_name or avatar changed
    if (
      before.display_name !== after.display_name ||
      before.avatar_url !== after.avatar_url
    ) {
      console.log(`Account ${peanutId} profile updated, syncing...`);

      // TODO: Sync to Booker performer profile if linked
      // TODO: Sync to Festival organizer profile if linked
    }

    return null;
  });

/**
 * Trigger: When a booking is created, notify the performer
 */
export const onBookingCreated = functions.firestore
  .document("bookings/{bookingId}")
  .onCreate(async (snapshot, context) => {
    const _booking = snapshot.data();
    const bookingId = context.params.bookingId;

    console.log(`New booking created: ${bookingId}`);

    // TODO: Send push notification to performer's Notebook app
    // TODO: Send email notification

    return null;
  });

/**
 * Trigger: When a booking status changes
 */
export const onBookingUpdated = functions.firestore
  .document("bookings/{bookingId}")
  .onUpdate(async (change, context) => {
    const before = change.before.data();
    const after = change.after.data();
    const bookingId = context.params.bookingId;

    // Check if status changed
    if (before.booking_status !== after.booking_status) {
      console.log(
        `Booking ${bookingId} status changed: ${before.booking_status} -> ${after.booking_status}`
      );

      // TODO: Notify performer of status change
      // TODO: If completed, prompt for review
    }

    return null;
  });

// ============================================
// SCHEDULED FUNCTIONS
// ============================================

/**
 * Scheduled: Daily cleanup of stale data
 * Runs every day at 3 AM
 */
export const dailyCleanup = functions.pubsub
  .schedule("0 3 * * *")
  .timeZone("America/New_York")
  .onRun(async (context) => {
    console.log("Running daily cleanup...");

    // TODO: Clean up expired sessions
    // TODO: Archive old bookings
    // TODO: Update aggregated stats

    return null;
  });
