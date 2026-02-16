declare module "*.css";

declare namespace JSX {
  interface IntrinsicElements {
    "s-app-nav": Record<string, unknown>;
    "s-banner": Record<string, unknown>;
    "s-button": Record<string, unknown>;
    "s-card": Record<string, unknown>;
    "s-link": Record<string, unknown>;
    "s-modal": Record<string, unknown>;
    "s-page": Record<string, unknown>;
    "s-paragraph": Record<string, unknown>;
    "s-section": Record<string, unknown>;
    "s-stack": Record<string, unknown>;
    "s-text-field": Record<string, unknown>;
  }
}
