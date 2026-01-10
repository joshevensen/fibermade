# Stage 3 — Constrained Card-Present Payments

## Purpose
Stage 3 exists to reduce friction for in-person sales **without turning Fibermade into a POS company**.

Card-present payments are treated as:
- a capture mechanism
- not a system of record
- not the center of the product

---

## Core Principles
- Production reality always wins
- Checkout authority remains centralized
- POS is optional and constrained
- Scope is earned, not assumed

---

## Core Capabilities (Stage 3)

### Card-Present Payments
- Implemented via Stripe Terminal
- Single supported device (WisePOS E)
- No native mobile app requirement
- Payments captured and associated with:
  - a show
  - an event
  - or a manual order

Fibermade records the transaction; reconciliation remains explicit.

---

### Show-Aware Payments
- Card-present sales can be linked to a Show
- Inventory impact is resolved via reconciliation
- No requirement for real-time inventory accuracy

POS supports calm selling, not perfect accounting.

---

## Explicit Constraints
Stage 3 intentionally avoids:
- Multiple hardware vendors
- Offline-first complexity
- Real-time inventory decrements
- Complex tax handling
- Acting as a general-purpose POS

These are not omissions; they are safeguards.

---

## Success Metric
Stage 3 succeeds when:
- In-person selling feels simpler than external tools
- Post-show reconciliation is faster and clearer
- POS does not redefine Fibermade’s identity
- Users trust Fibermade more, not less

If POS complexity starts to dominate development, Stage 3 has failed.
