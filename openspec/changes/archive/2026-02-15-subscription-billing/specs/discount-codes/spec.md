## ADDED Requirements

### Requirement: Support promo code via URL parameter at registration
The system SHALL accept an optional query parameter (e.g. promo or code) on the registration page URL and pass that value to the Stripe Checkout Session so the discount is applied when the user completes checkout.

#### Scenario: Registration with promo parameter creates checkout with discount
- **WHEN** the user visits the registration page with a promo parameter (e.g. /register?promo=LAUNCH2026)
- **AND** submits the registration form successfully
- **THEN** the system SHALL include the promo value in the session and in the Stripe Checkout Session (e.g. as discount or coupon)
- **AND** Stripe SHALL apply the discount when the user completes payment, if the coupon is valid

#### Scenario: Registration without promo parameter
- **WHEN** the user visits the registration page without a promo parameter and submits the form
- **THEN** the system SHALL create the Checkout Session without any discount or coupon
- **AND** the user SHALL pay the full subscription price unless they add a code in Stripe Checkout (if enabled there)

### Requirement: Promo code not required on registration form
The system SHALL NOT display a discount code input field on the registration form. Discount codes SHALL only be applicable via URL parameter or (if Stripe allows) within Stripe Checkout itself.

#### Scenario: No discount code field on form
- **WHEN** the user views the registration form
- **THEN** the form SHALL NOT include a visible "Discount code" or "Promo code" input
- **AND** discount SHALL still be applicable via URL parameter as specified above

### Requirement: Invalid or expired promo does not block checkout
The system SHALL allow checkout to proceed even if the provided promo code is invalid or expired. Stripe SHALL validate the coupon; the application SHALL NOT block creation of the Checkout Session based on promo validity.

#### Scenario: Invalid promo in URL still creates checkout
- **WHEN** the user registers with a promo parameter that does not match a valid Stripe coupon (e.g. typo or expired)
- **THEN** the system SHALL still create the Stripe Checkout Session
- **AND** Stripe may show the full price or an error in Checkout; the application SHALL NOT prevent the user from reaching Checkout
