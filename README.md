# Fibermade

**The commerce platform for the fiber community**, starting with hand dyers.

---

## Repo structure

This repository contains two applications:

| Directory      | Purpose |
|----------------|---------|
| **`platform/`** | Main Fibermade app: Laravel API, creator dashboard, and store dashboard (Inertia + Vue). |
| **`shopify/`**  | Shopify app: installable app that links a merchant’s store to their Fibermade account and syncs catalog, inventory, and (later) orders. |

Each app has its own dependencies and runs independently: Composer + npm in `platform/`, npm in `shopify/`.

---

## Tech stack

- **Platform:** PHP (Laravel 12), Vue 3, Inertia v2, Tailwind v4, Vite. API auth via Sanctum.
- **Shopify app:** Node, React Router, Prisma, Shopify CLI and App Bridge.
- **Planning / docs:** `.ai/` holds epics, lore, and strategy.

---

## Getting started

- **Platform** — See [platform/README.md](platform/README.md) for setup (Herd, DBngin, migrations, npm).
- **Shopify app** — See [shopify/README.md](shopify/README.md) for Shopify CLI, env, Prisma, and local dev.

Run each app from its own directory. There is no single root-level install or dev command.

---

## Development workflow

- Platform and Shopify app are developed and run separately.
- CI (`.github/workflows/`) runs lint and tests per app.

---

## Further reading

- **Product and strategy:** [.ai/lore/](.ai/lore/) (Strategy, Personas, etc.)
- **Implementation roadmap:** [.ai/epics.md](.ai/epics.md) and [.ai/epics/](.ai/epics/)
- **Platform conventions:** [platform/AGENTS.md](platform/AGENTS.md), [platform/CLAUDE.md](platform/CLAUDE.md)
