# Web-to-Print Platform — MVP Technical Specification

## 1. Project Type

Build a self-hosted web-to-print platform for individual printing companies.

This is **not SaaS**.

Each customer receives a dedicated installation with:

- One backend
- One storefront
- One database
- One file storage area
- One admin panel
- Customer-specific customizations

The system should be built as a reusable core platform with a customization layer for each customer.

---

## 2. MVP Goal

The MVP must allow a printing company to sell configurable print products online.

The customer should be able to:

- Manage print products
- Define dynamic product options
- Define option values
- Define pricing
- Block invalid product combinations
- Offer online design editing
- Allow PDF upload instead of designing
- Accept orders through an online storefront
- Generate production-ready handoff files
- Generate a job sheet PDF
- Generate MXML
- Generate JDF
- Download a production package

---

## 3. MVP Modules

The MVP consists of four independent modules:

```text
1. PIM + Pricing Engine
2. Online Designer
3. Distribution / Production Handoff
4. E-commerce Storefront
```

Modules must communicate through APIs and events.

```text
┌─────────────────────────────────────┐
│          E-commerce Storefront       │
│     Static site + dynamic APIs       │
└──────────────────┬──────────────────┘
                   │
                   ▼
┌─────────────────────────────────────┐
│          Laravel Backend API         │
└───────┬──────────┬──────────┬────────┘
        │          │          │
        ▼          ▼          ▼
┌────────────┐ ┌────────────┐ ┌────────────────┐
│    PIM     │ │  Designer  │ │  Distribution  │
│  Pricing   │ │  PDF Gen   │ │ JobSheet/JDF   │
└────────────┘ └────────────┘ └────────────────┘
        │          │          │
        └──────────┴──────────┘
                   ▼
             ┌────────────┐
             │ E-commerce │
             │ Orders     │
             └────────────┘
```

---

## 4. MVP Technology Stack

### Backend

Use:

```text
Laravel
PHP 8.3+
PostgreSQL
Redis
Laravel Horizon
Laravel Scheduler
Laravel Sanctum
Laravel Events / Queues
OpenAPI documentation
```

### Admin Panel

Use:

```text
FilamentPHP
Livewire
Alpine.js
Tailwind CSS
```

### Storefront

Use:

```text
Astro
React islands
TypeScript
Tailwind CSS
```

Astro handles static pages. React islands handle dynamic parts such as product configurator, pricing, cart, checkout, designer launch, and PDF upload.

### Online Designer

Use:

```text
Fabric.js
TypeScript
PDF-LIB
Vite
Web Workers
```

### PDF / Prepress

Use for MVP:

```text
PDF-LIB
Ghostscript
qpdf
Poppler
ImageMagick
```

Use for prepress hardening:

```text
callas pdfToolbox
```

callas should be used for advanced PDF validation, PDF/X checks, preflight profiles, font checks, image resolution checks, color checks, bleed checks, and production readiness validation.

### Storage

Use:

```text
MinIO for self-hosted installations
S3-compatible storage abstraction
```

### Search

Use:

```text
Meilisearch
```

### Infrastructure

Use:

```text
Docker
Docker Compose
Nginx
PHP-FPM
PostgreSQL
Redis
MinIO
Meilisearch
Supervisor
```

---

## 5. Repository Structure

Recommended structure:

```text
web-to-print/
  backend/
    app/
    modules/
    database/
    routes/
    tests/

  storefront/
    src/
    public/
    themes/

  designer/
    src/
    packages/

  packages/
    shared-types/
    api-client/
    pdf-tools/

  custom/
    customer/
      config/
      pricing/
      distribution/
      templates/
      themes/
      integrations/

  docker/
  deployment/
  docs/
```

Core logic should go into:

```text
backend/modules/
```

Customer-specific overrides should go into:

```text
custom/customer/
```

---

## 6. Backend Modules

Use a modular Laravel structure:

```text
backend/modules/
  Core/
  Auth/
  Settings/
  PIM/
  Pricing/
  Designer/
  Ecommerce/
  Distribution/
  FileStorage/
  Templates/
  Integrations/
```

Each module should follow this structure:

```text
modules/PIM/
  Domain/
    Models/
    ValueObjects/
    Services/
    Rules/

  Application/
    Actions/
    DTOs/
    Commands/
    Queries/

  Infrastructure/
    Repositories/
    ExternalServices/

  Http/
    Controllers/
    Requests/
    Resources/

  Database/
    Migrations/
    Seeders/

  Events/
  Listeners/
  Tests/
```

---

## 7. Module 1 — PIM + Pricing Engine

### Purpose

The PIM module manages configurable print products.

Example product:

```text
Flyer
```

Example options:

```text
Format: A4, A5, A6
Paper: 125g, 135g, 170g
Colors: 4/0, 4/4
Refinement: None, Lamination
Quantity: 100, 250, 500, 1000
```

All product options and option values must be dynamic.

### MVP Features

The PIM MVP must include:

- Product CRUD
- Product category CRUD
- Dynamic product options
- Dynamic option values
- Product configurator API
- Product validation API
- Whitelist rules
- Blacklist rules
- Price tables
- Quantity pricing
- Option-based price modifiers
- Price calculation API
- Custom calculator interface
- Product snapshot saved on order item

### Main Database Tables

```text
products
product_categories
product_options
product_option_values
product_rules
price_lists
price_rules
price_tables
price_table_rows
price_modifiers
product_assets
product_template_bindings
product_versions
```

### Product Table

```text
products
  id
  category_id
  name
  slug
  sku
  description
  status
  requires_design
  allows_pdf_upload
  default_bleed_mm
  default_safe_margin_mm
  sort_order
  created_at
  updated_at
```

### Product Options Table

```text
product_options
  id
  product_id
  code
  label
  type
  required
  sort_order
  help_text
  config_json
  created_at
  updated_at
```

Option types:

```text
select
radio
checkbox
number
range
text
boolean
```

### Product Option Values Table

```text
product_option_values
  id
  product_option_id
  code
  label
  value
  sort_order
  metadata_json
  created_at
  updated_at
```

### Configurator API

```http
GET /api/v1/products/{slug}/configurator
```

Example response:

```json
{
  "product": {
    "id": "uuid",
    "name": "Flyer",
    "slug": "flyer",
    "requires_design": true,
    "allows_pdf_upload": true
  },
  "options": [
    {
      "code": "format",
      "label": "Format",
      "type": "select",
      "required": true,
      "values": [
        {
          "code": "a4",
          "label": "A4",
          "metadata": {
            "width_mm": 210,
            "height_mm": 297
          }
        }
      ]
    }
  ],
  "defaults": {
    "format": "a4",
    "paper": "135g",
    "colors": "4-4",
    "quantity": 100
  }
}
```

### Validation API

```http
POST /api/v1/products/{slug}/validate
```

Example request:

```json
{
  "configuration": {
    "format": "a4",
    "paper": "125g",
    "colors": "4-0",
    "quantity": 100
  }
}
```

Example response:

```json
{
  "valid": false,
  "errors": [
    {
      "code": "combination_not_allowed",
      "message": "Flyer A4 with 4/0 color is not available."
    }
  ],
  "disabled_options": {
    "colors": ["4-0"]
  }
}
```

### Whitelist Rules

Whitelist rules define combinations that are explicitly allowed.

Example:

```json
{
  "product": "flyer",
  "allow": {
    "format": ["a4", "a5"],
    "colors": ["4-4"],
    "paper": ["125g", "135g"]
  }
}
```

### Blacklist Rules

Blacklist rules block specific combinations.

Example:

```json
{
  "product": "flyer",
  "block": {
    "format": "a4",
    "colors": "4-0"
  },
  "reason": "This combination cannot be produced."
}
```

### Pricing Engine

The pricing engine must support:

- Price tables
- Quantity breaks
- Option modifiers
- Setup fees
- Minimum prices
- Custom calculator classes
- Manual quote mode

Required interface:

```php
interface PriceCalculatorInterface
{
    public function calculate(ProductConfiguration $configuration): PriceResult;
}
```

### Price API

```http
POST /api/v1/products/{slug}/price
```

Example request:

```json
{
  "configuration": {
    "format": "a4",
    "paper": "125g",
    "colors": "4-4",
    "refinement": "none",
    "quantity": 100
  }
}
```

Example response:

```json
{
  "valid": true,
  "currency": "EUR",
  "net_price": 42.5,
  "tax": 8.08,
  "gross_price": 50.58,
  "breakdown": [
    {
      "label": "Base price",
      "amount": 30
    },
    {
      "label": "Paper surcharge",
      "amount": 5
    },
    {
      "label": "Color surcharge",
      "amount": 7.5
    }
  ]
}
```

---

## 8. Module 2 — Online Designer

### Purpose

The Designer module allows users to customize print templates online or upload a print-ready PDF.

Use:

```text
Fabric.js
TypeScript
PDF-LIB
```

### MVP Features

Admin users must be able to:

- Create design templates
- Assign templates to products
- Upload background images or PDFs
- Define page size
- Define bleed
- Define safe area
- Lock template objects
- Define editable objects
- Create text placeholders
- Create image placeholders
- Create multi-page templates

End users must be able to:

- Choose a template
- Edit text
- Upload images
- Move objects
- Resize objects
- Rotate objects
- Preview design
- Save draft
- Approve artwork
- Upload PDF instead of designing

### Design Template Table

```text
design_templates
  id
  product_id
  name
  status
  width_mm
  height_mm
  bleed_mm
  safe_margin_mm
  page_count
  thumbnail_file_id
  template_json
  created_at
  updated_at
```

### Design Table

```text
designs
  id
  customer_id
  product_id
  template_id
  status
  design_json
  preview_file_id
  print_pdf_file_id
  created_at
  updated_at
```

Design statuses:

```text
draft
preview_generated
approved
print_pdf_generated
failed
```

### Designer Document Format

Store editable designs as JSON.

```json
{
  "version": "1.0",
  "product_id": "uuid",
  "template_id": "uuid",
  "configuration": {
    "format": "a4",
    "colors": "4-4"
  },
  "pages": [
    {
      "page_number": 1,
      "trim_width_mm": 210,
      "trim_height_mm": 297,
      "bleed_mm": 3,
      "safe_margin_mm": 5,
      "fabric_json": {}
    }
  ]
}
```

### PDF Generation MVP

The MVP PDF generation process:

```text
1. Load saved Fabric.js JSON.
2. Render each page at high resolution.
3. Create a PDF with PDF-LIB.
4. Set page size including bleed.
5. Place rendered page image into the PDF.
6. Save print PDF to object storage.
7. Generate preview image.
8. Save preview to object storage.
```

### PDF Upload Flow

Users can skip the designer and upload a PDF.

Flow:

```text
1. User selects product configuration.
2. User uploads PDF.
3. System validates uploaded PDF.
4. System generates preview.
5. User approves uploaded artwork.
6. Artwork is attached to cart item.
```

MVP validation checks:

```text
PDF file type
Page count
Page size
Bleed size
Password protection
File size
Corruption check
```

### Designer APIs

```http
GET    /api/v1/designer/templates
GET    /api/v1/designer/templates/{id}
POST   /api/v1/designer/designs
GET    /api/v1/designer/designs/{id}
PUT    /api/v1/designer/designs/{id}
POST   /api/v1/designer/designs/{id}/preview
POST   /api/v1/designer/designs/{id}/generate-print-pdf
POST   /api/v1/designer/designs/{id}/approve

POST   /api/v1/designer/pdf-upload
POST   /api/v1/designer/pdf-upload/{id}/validate
POST   /api/v1/designer/pdf-upload/{id}/approve
```

---

## 9. Module 3 — Distribution / Production Handoff

### Purpose

The Distribution module generates production files from approved order items.

Input:

```text
Order
Order item
Product configuration snapshot
Approved artwork PDF
Customer details
Shipping details
Production settings
```

Output:

```text
Job sheet PDF
MXML file
JDF file
Production package ZIP
```

### MVP Features

The Distribution MVP must include:

- Production job creation
- Job sheet PDF generation
- MXML generation
- JDF generation
- Editable templates
- File naming rules
- Folder naming rules
- Production package ZIP
- Regenerate job sheet
- Regenerate MXML
- Regenerate JDF
- Download production package

### Production Job Table

```text
production_jobs
  id
  order_id
  order_item_id
  job_number
  product_name
  configuration_snapshot_json
  artwork_file_id
  status
  package_file_id
  created_at
  updated_at
```

Production job statuses:

```text
pending
generating
ready
exported
failed
cancelled
```

### Production Package Structure

```text
/order-10001/item-1/
  artwork.pdf
  jobsheet.pdf
  job.jdf
  job.mxml
  metadata.json
  preview.png
```

ZIP output example:

```text
ORDER-10001-ITEM-1-FLYER.zip
```

### Template System

Use editable templates for all generated documents.

Template types:

```text
jobsheet_pdf
jdf_xml
mxml_xml
folder_name
file_name
```

Use:

```text
Blade for HTML job sheet templates
Blade or Twig for XML templates
```

Example file naming template:

```text
{{ order.number }}_{{ product.slug }}_{{ configuration.quantity }}_{{ configuration.format }}.pdf
```

Example MXML/JDF-style XML template:

```xml
<Job>
  <JobNumber>{{ job.number }}</JobNumber>
  <OrderNumber>{{ order.number }}</OrderNumber>
  <Product>{{ product.name }}</Product>
  <Quantity>{{ configuration.quantity }}</Quantity>
  <Format>{{ configuration.format }}</Format>
  <Paper>{{ configuration.paper }}</Paper>
  <Artwork>{{ files.artwork }}</Artwork>
</Job>
```

### Distribution APIs

```http
POST   /api/v1/production/jobs
GET    /api/v1/production/jobs
GET    /api/v1/production/jobs/{id}
POST   /api/v1/production/jobs/{id}/generate-package
POST   /api/v1/production/jobs/{id}/regenerate-jobsheet
POST   /api/v1/production/jobs/{id}/regenerate-jdf
POST   /api/v1/production/jobs/{id}/regenerate-mxml
GET    /api/v1/production/jobs/{id}/download
```

---

## 10. Module 4 — E-commerce Storefront

### Purpose

The storefront is a normal online shop for print products.

The storefront must be static-first, fast, SEO-friendly, and API-driven for product configuration.

Use:

```text
Astro
React islands
TypeScript
Tailwind CSS
Laravel API
```

### MVP Static Pages

Generated by Astro:

```text
Home
Category pages
Product pages
About
Contact
Terms
Privacy
Help
```

### MVP Dynamic Components

API-driven dynamic components:

```text
Product configurator
Live pricing
Cart
Checkout
Customer login
Order history
Designer launcher
PDF upload
Payment selection
Shipping selection
```

### Product Page Flow

```text
1. User opens product page.
2. Storefront fetches configurator schema from PIM.
3. User selects product options.
4. Storefront validates configuration.
5. Storefront requests price.
6. User chooses design online or upload PDF.
7. Artwork is approved.
8. Configured product is added to cart.
9. User checks out.
10. Order is created.
11. Production job is generated.
```

### Cart Item Structure

```json
{
  "product_id": "uuid",
  "product_name": "Flyer",
  "configuration": {
    "format": "a4",
    "paper": "125g",
    "colors": "4-4",
    "quantity": 100
  },
  "artwork": {
    "source": "designer",
    "artwork_id": "uuid",
    "print_pdf_file_id": "uuid"
  },
  "price": {
    "currency": "EUR",
    "net": 42.5,
    "tax": 8.08,
    "gross": 50.58
  }
}
```

### E-commerce Tables

```text
customers
customer_addresses
carts
cart_items
orders
order_items
payments
shipments
discount_codes
tax_rules
checkout_sessions
invoices
```

### MVP Payment Methods

Support:

```text
Stripe
PayPal
Bank transfer
Manual invoice
```

---

## 11. File Storage MVP

### Purpose

Centralized file handling for all modules.

### Stored File Types

```text
Product images
Template thumbnails
Uploaded user images
Uploaded PDFs
Generated previews
Generated print PDFs
Job sheet PDFs
JDF files
MXML files
Production package ZIP files
Invoices
```

### Files Table

```text
files
  id
  disk
  path
  original_name
  mime_type
  size
  checksum
  visibility
  metadata_json
  created_at
  updated_at
```

### Required Features

```text
Direct browser upload to MinIO/S3
Temporary signed URLs
File checksums
Basic file validation
Automatic cleanup of abandoned files
```

---

## 12. Core Events

Use Laravel events and queued listeners.

Required MVP events:

```text
ProductConfigurationValidated
PriceCalculated
DesignSaved
PreviewGenerated
ArtworkApproved
PdfUploaded
PdfValidated
CartItemAdded
OrderPlaced
PaymentReceived
ProductionJobCreated
ProductionPackageGenerated
```

---

## 13. Main MVP Workflow

```text
1. Product options are loaded from PIM.
2. User selects configuration.
3. PIM validates configuration.
4. Pricing engine calculates price.
5. User designs online or uploads PDF.
6. Artwork is approved.
7. Cart item is created.
8. Checkout creates order.
9. Payment is completed or marked as manual payment.
10. Production job is created.
11. Distribution module generates job sheet PDF.
12. Distribution module generates MXML.
13. Distribution module generates JDF.
14. Distribution module creates production package ZIP.
15. Production package is available for download.
```

---

## 14. API Style

Use REST APIs.

Rules:

```text
Use JSON
Use /api/v1 prefix
Use OpenAPI documentation
Use Laravel API resources
Use Laravel request validation
Use consistent error format
```

Example error response:

```json
{
  "message": "The selected product configuration is invalid.",
  "errors": [
    {
      "field": "colors",
      "code": "not_available",
      "message": "4/0 is not available for A4 flyers."
    }
  ]
}
```

---

## 15. Customer Customization Layer

The MVP must support customer-specific overrides without modifying core code directly.

Use:

```text
custom/customer/
  config/
  pricing/
  distribution/
  templates/
  themes/
  integrations/
```

### Custom Pricing

```text
custom/customer/pricing/FlyerPriceCalculator.php
custom/customer/pricing/BusinessCardPriceCalculator.php
```

### Custom Product Rules

```text
custom/customer/pricing/FlyerAvailabilityRule.php
custom/customer/pricing/PaperCompatibilityRule.php
```

### Custom Production Templates

```text
custom/customer/templates/jobsheet.blade.php
custom/customer/templates/jdf.xml.twig
custom/customer/templates/mxml.xml.twig
```

### Custom Storefront Theme

```text
custom/customer/themes/default/theme.json
custom/customer/themes/default/components/
custom/customer/themes/default/pages/
```

---

## 16. MVP Admin Screens

Required admin screens:

```text
Dashboard
Products
Categories
Product options
Option values
Pricing
Rules
Design templates
Uploaded files
Orders
Customers
Production jobs
Job sheet templates
JDF templates
MXML templates
Payment settings
Shipping settings
Company settings
Users
Roles
Logs
```

---

## 17. Prepress Hardening with callas

Use **callas pdfToolbox** for prepress hardening.

This should be added after the basic MVP PDF generation and upload flow is working.

callas should be used for:

```text
PDF preflight
PDF/X validation
Font embedding checks
Image resolution checks
Bleed checks
Trim box checks
Media box checks
Color space checks
Spot color checks
Transparency checks
Overprint checks
Production readiness reports
Automatic correction profiles where safe
```

### Prepress Flow

```text
1. User generates or uploads PDF.
2. System stores original PDF.
3. System sends PDF to callas pdfToolbox.
4. callas runs the selected preflight profile.
5. System receives preflight report.
6. System stores report.
7. System marks artwork as passed, warning, or failed.
8. If failed, user must upload/fix file before checkout or production.
```

### Preflight Statuses

```text
not_checked
checking
passed
passed_with_warnings
failed
error
```

### Artwork Table Additions

```text
preflight_status
preflight_report_file_id
preflight_checked_at
preflight_profile
preflight_summary_json
```

---

## 18. MVP Products

Start with three products:

```text
Flyer
Business Card
Poster
```

### Flyer Options

```text
Format: A4, A5, A6
Paper: 125g, 135g, 170g
Colors: 4/0, 4/4
Refinement: None, Lamination
Quantity: 100, 250, 500, 1000
```

### Business Card Options

```text
Format: 85 x 55 mm, 90 x 50 mm
Paper: 300g, 350g, 400g
Colors: 4/0, 4/4
Refinement: None, Matte Lamination, Gloss Lamination
Quantity: 100, 250, 500, 1000
```

### Poster Options

```text
Format: A3, A2, A1
Paper: 135g, 170g, 200g
Colors: 4/0
Quantity: 1, 5, 10, 25, 50
```

---

## 19. MVP Completion Criteria

The MVP is complete when the following works end-to-end:

```text
1. Admin creates a print product.
2. Admin adds dynamic options and values.
3. Admin defines price rules.
4. Admin defines allowed and blocked combinations.
5. Admin creates or uploads a design template.
6. Storefront displays product page.
7. Storefront loads configurator from API.
8. User selects product options.
9. System validates selected combination.
10. System calculates live price.
11. User edits a design online or uploads PDF.
12. System generates or validates artwork.
13. User adds configured product to cart.
14. User completes checkout.
15. Order is created.
16. Production job is created.
17. Job sheet PDF is generated.
18. MXML file is generated.
19. JDF file is generated.
20. Production package ZIP is generated.
21. Admin downloads production package.
```

---

## 20. Final MVP Stack Summary

```text
Backend:
  Laravel
  PHP 8.3+
  PostgreSQL
  Redis
  Laravel Horizon
  Laravel Sanctum
  FilamentPHP

Storefront:
  Astro
  React islands
  TypeScript
  Tailwind CSS

Designer:
  Fabric.js
  TypeScript
  PDF-LIB
  Vite
  Web Workers

PDF / Prepress:
  PDF-LIB
  Ghostscript
  qpdf
  Poppler
  ImageMagick
  callas pdfToolbox for prepress hardening

Storage:
  MinIO
  S3-compatible storage abstraction

Search:
  Meilisearch

Infrastructure:
  Docker
  Docker Compose
  Nginx
  PHP-FPM
  Supervisor
```
