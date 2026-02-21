# ARCH-002: No Content Format in Route Paths

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/architecture/ARCH-002-no-content-format-in-route-path.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat definitions/architecture/ARCH-002-no-content-format-in-route-path.prompt.txt)" --cwd .` |

## Definition

Route paths must identify **resources**, not their representation format. Content format (PDF, CSV, XLSX, XML, HTML) must be negotiated via the HTTP `Accept` header, not encoded in the URL path or route name.

This follows the HTTP content negotiation mechanism defined in [RFC 7231 §5.3](https://www.rfc-editor.org/rfc/rfc7231#section-5.3): the URL identifies **what** (the resource), the `Accept` header specifies **how** (the representation).

## Rules

### 1. No Format Indicators in Route Paths

Route paths must not contain suffixes or segments that describe the response format.

**Forbidden patterns:**
- Format suffixes: `-pdf`, `-csv`, `-xlsx`, `-xml`, `-html`, `-doc`
- Format actions: `/export-csv`, `/download-pdf`, `/generate-pdf`, `/as-pdf`, `/to-csv`, `/generate-xlsx`
- Format sub-paths that refer to format, not resource: `/pdf`, `/csv`, `/excel`

```
# Incorrect
/api/orders/{id}/cmr-pdf
/api/reports/{id}/export-csv
/api/invoices/{id}/download-pdf
/api/settlements/{id}/generate-xlsx

# Correct
/api/orders/{id}/cmr
/api/reports/{id}
/api/invoices/{id}
/api/settlements/{id}
```

### 2. Resources and Sub-resources Are Valid Path Segments

The resource itself (e.g. CMR document, invoice, report) **is** a valid sub-resource in the URL. Only the format indicator is forbidden.

```
# Correct - "cmr" is a sub-resource (a CMR document belonging to an order)
GET /api/orders/{id}/cmr

# Correct - "invoice" is a resource
GET /api/invoices/{id}

# Incorrect - "pdf" is a format, not a resource
GET /api/orders/{id}/cmr-pdf
```

### 3. Format Selection via Accept Header

The client specifies the desired format using the `Accept` header:

| Format | Accept Header Value |
|--------|-------------------|
| PDF | `application/pdf` |
| CSV | `text/csv` |
| XLSX | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| XML | `application/xml` |
| HTML | `text/html` |
| JSON | `application/json` |

## Correct Usage

```php
class OrderController extends AbstractRestApiController
{
    // CMR document - format negotiated via Accept header
    // Accept: application/pdf → returns PDF
    // Accept: application/json → returns CMR data as JSON
    #[Route('/api/orders/{id}/cmr', methods: ['POST'])]
    public function createCmrAction(CreateCmrRequest $request): Response { }

    // Report - format negotiated via Accept header
    // Accept: text/csv → returns CSV
    // Accept: application/json → returns JSON
    #[Route('/api/reports/{id}', methods: ['GET'])]
    public function getReportAction(GetReportRequest $request): Response { }

    // Invoice - format negotiated via Accept header
    // Accept: application/pdf → returns PDF
    #[Route('/api/invoices/{id}', methods: ['GET'])]
    public function getInvoiceAction(GetInvoiceRequest $request): Response { }

    // Settlement export - format negotiated via Accept header
    // Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet → returns XLSX
    #[Route('/api/settlements/{id}', methods: ['GET'])]
    public function getSettlementAction(GetSettlementRequest $request): Response { }
}
```

## Violation

```php
// WRONG: Format in route path
#[Route('/api/orders/{id}/cmr-pdf', methods: ['POST'])]
public function generateCmrPdfAction(): Response { }

#[Route('/api/reports/{id}/export-csv', methods: ['GET'])]
public function exportReportCsvAction(): Response { }

#[Route('/api/invoices/{id}/download-pdf', methods: ['GET'])]
public function downloadInvoicePdfAction(): Response { }

#[Route('/api/settlements/{id}/generate-xlsx', methods: ['GET'])]
public function generateSettlementXlsxAction(): Response { }
```

### How to Fix

| Violation | Correct Alternative |
|-----------|---------------------|
| `POST /api/orders/{id}/cmr-pdf` | `POST /api/orders/{id}/cmr` + `Accept: application/pdf` |
| `GET /api/reports/{id}/export-csv` | `GET /api/reports/{id}` + `Accept: text/csv` |
| `GET /api/invoices/{id}/download-pdf` | `GET /api/invoices/{id}` + `Accept: application/pdf` |
| `GET /api/settlements/{id}/generate-xlsx` | `GET /api/settlements/{id}` + `Accept: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| Route name `api_order_generate_cmr_pdf` | Route name `api_order_create_cmr` |

## Rationale

1. **RFC 7231 Compliance**: HTTP defines content negotiation via `Accept` header. URLs identify resources; headers negotiate representation.

2. **Resource/Representation Separation**: A CMR document is a resource. PDF is one of many possible representations. The URL should identify the resource, not couple it to a specific format.

3. **Endpoint Reusability**: A single endpoint `/api/orders/{id}/cmr` can serve PDF, JSON, or any future format without URL changes.

4. **Format Extensibility**: Adding a new format (e.g. PNG preview) requires no route changes — just a new `Accept` header value.

5. **ARCH-001 Alignment**: Format suffixes like `-pdf` or actions like `/export-csv` violate ARCH-001's rule against verbs and non-resource segments in paths.
