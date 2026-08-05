# YellowPages.so Backend Audit

Audit score: 0/100

## Finding counts

- Critical: 1
- High: 12
- Medium: 43
- Low: 0

## Executive priorities

1. [CRITICAL] Placeholder production secret
2. [HIGH] Database password stored in phpunit.xml
3. [HIGH] Unauthenticated write route requires review
4. [HIGH] Unauthenticated write route requires review
5. [HIGH] Unauthenticated write route requires review
6. [HIGH] Unauthenticated write route requires review
7. [HIGH] Unauthenticated write route requires review
8. [HIGH] Unauthenticated write route requires review
9. [HIGH] Unauthenticated write route requires review
10. [HIGH] Unauthenticated write route requires review
11. [HIGH] Unauthenticated write route requires review
12. [HIGH] Unauthenticated write route requires review
13. [HIGH] Unauthenticated write route requires review

## Architecture

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Http/Controllers/Api/BusinessOwnerPortalController.php File: `app/Http/Controllers/Api/BusinessOwnerPortalController.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Http/Controllers/Api/ReviewController.php File: `app/Http/Controllers/Api/ReviewController.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Services/AiBusinessIntelligenceService.php File: `app/Services/AiBusinessIntelligenceService.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Services/BusinessOwnerPortalService.php File: `app/Services/BusinessOwnerPortalService.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Services/CmsContentService.php File: `app/Services/CmsContentService.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Services/ReviewReputationService.php File: `app/Services/ReviewReputationService.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.

### MEDIUM: Runtime schema compatibility logic

Evidence: app/Services/SubscriptionBillingService.php File: `app/Services/SubscriptionBillingService.php`

Recommendation: Move schema compatibility into migrations or upgrade commands. Application services should target one stable schema.


## Maintainability

### MEDIUM: Large PHP file: app/Console/Commands/ImportSomaliaGeography.php

Evidence: 412 lines File: `app/Console/Commands/ImportSomaliaGeography.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Http/Controllers/Api/BusinessOwnerPortalController.php

Evidence: 411 lines File: `app/Http/Controllers/Api/BusinessOwnerPortalController.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/AdvertisingService.php

Evidence: 307 lines File: `app/Services/AdvertisingService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/CmsContentService.php

Evidence: 396 lines File: `app/Services/CmsContentService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/CommerceService.php

Evidence: 355 lines File: `app/Services/CommerceService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/CommunicationManager.php

Evidence: 337 lines File: `app/Services/CommunicationManager.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/LeadMarketplaceService.php

Evidence: 340 lines File: `app/Services/LeadMarketplaceService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/MediaManagementService.php

Evidence: 447 lines File: `app/Services/MediaManagementService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/SubscriptionBillingService.php

Evidence: 445 lines File: `app/Services/SubscriptionBillingService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.

### MEDIUM: Large PHP file: app/Services/WorkflowAutomationService.php

Evidence: 494 lines File: `app/Services/WorkflowAutomationService.php`

Recommendation: Split responsibilities into smaller services, actions, requests, resources, or policies.


## Migrations

### MEDIUM: Migration depends on pre-existing schema

Evidence: database/migrations/2026_08_02_150000_extend_directory_core_tables.php File: `database/migrations/2026_08_02_150000_extend_directory_core_tables.php`

Recommendation: Document the baseline dependency or consolidate migrations into a deterministic installation sequence.

### MEDIUM: Self-referencing foreign key requires review

Evidence: database/migrations/2026_08_03_020000_create_marketplace_commerce_platform.php File: `database/migrations/2026_08_03_020000_create_marketplace_commerce_platform.php`

Recommendation: Verify the referenced column is primary or unique before adding the foreign key.

### MEDIUM: Self-referencing foreign key requires review

Evidence: database/migrations/2026_08_03_050000_create_cms_content_platform.php File: `database/migrations/2026_08_03_050000_create_cms_content_platform.php`

Recommendation: Verify the referenced column is primary or unique before adding the foreign key.

### MEDIUM: Self-referencing foreign key requires review

Evidence: database/migrations/2026_08_03_060000_create_customer_support_platform.php File: `database/migrations/2026_08_03_060000_create_customer_support_platform.php`

Recommendation: Verify the referenced column is primary or unique before adding the foreign key.


## Security

### CRITICAL: Placeholder production secret

Evidence: Unsafe placeholder detected. File: `.env.production`

Recommendation: Replace placeholders through a secret manager.

### HIGH: Database password stored in phpunit.xml

Evidence: DB_PASSWORD is defined in phpunit.xml. File: `phpunit.xml`

Recommendation: Load the password from .env.testing or CI secrets.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/auth/login

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/auth/register

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/commerce/cart/items

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/commerce/checkout

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/compliance/privacy-requests

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/payments/intents

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/payments/intents/{intentId}/capture

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/quote-requests

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/reporting/events

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: POST api/v1/support/tickets

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### HIGH: Unauthenticated write route requires review

Evidence: PUT storage/{path}

Recommendation: Confirm it is intentionally public. Add throttling, validation, abuse controls, and audit logging.

### MEDIUM: Model allows unrestricted mass assignment

Evidence: protected $guarded = [] File: `app/Models/Category.php`, line 19

Recommendation: Use explicit fillable fields or DTO-based assignment.

### MEDIUM: Model allows unrestricted mass assignment

Evidence: protected $guarded = [] File: `app/Models/DirectoryService.php`, line 16

Recommendation: Use explicit fillable fields or DTO-based assignment.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/AdminCustomerSupportController.php File: `app/Http/Controllers/Api/AdminCustomerSupportController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/AdvertisingController.php File: `app/Http/Controllers/Api/AdvertisingController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/AnalyticsReportingController.php File: `app/Http/Controllers/Api/AnalyticsReportingController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/AuthController.php File: `app/Http/Controllers/Api/AuthController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/CmsContentController.php File: `app/Http/Controllers/Api/CmsContentController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/CommerceController.php File: `app/Http/Controllers/Api/CommerceController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/CustomerSupportController.php File: `app/Http/Controllers/Api/CustomerSupportController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/DeveloperPlatformController.php File: `app/Http/Controllers/Api/DeveloperPlatformController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/LeadMarketplaceController.php File: `app/Http/Controllers/Api/LeadMarketplaceController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/MediaController.php File: `app/Http/Controllers/Api/MediaController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/PaymentController.php File: `app/Http/Controllers/Api/PaymentController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/PlatformHealthController.php File: `app/Http/Controllers/Api/PlatformHealthController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/ReviewController.php File: `app/Http/Controllers/Api/ReviewController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/SubscriptionBillingController.php File: `app/Http/Controllers/Api/SubscriptionBillingController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Controllers/Api/WorkflowAutomationController.php File: `app/Http/Controllers/Api/WorkflowAutomationController.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Http/Middleware/AuthenticateApiClient.php File: `app/Http/Middleware/AuthenticateApiClient.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Services/CommunicationManager.php File: `app/Services/CommunicationManager.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Services/MediaManagementService.php File: `app/Services/MediaManagementService.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Services/WebhookService.php File: `app/Services/WebhookService.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

### MEDIUM: Raw exception message usage

Evidence: app/Services/WorkflowAutomationService.php File: `app/Services/WorkflowAutomationService.php`

Recommendation: Do not expose internal database or filesystem errors to clients. Log internal details with a request ID.

## Limitations

This automated scan does not replace manual code review, penetration testing, load testing, production data-flow review, or third-party integration review.
