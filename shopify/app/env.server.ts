/**
 * Startup assertion: FIBERMADE_API_URL must be set in production so misconfigured
 * deploys fail fast instead of silently no-oping. Only enforced in production.
 */
if (process.env.NODE_ENV === "production") {
  const url = process.env.FIBERMADE_API_URL;
  if (!url || url.trim() === "") {
    throw new Error(
      "FIBERMADE_API_URL is required in production. Set it in your environment (e.g. .env or deployment config) to the Fibermade platform API base URL."
    );
  }
}
