Feature Summary
===============

This file documents the main features of the CashFlow project and highlights recent invoice updates.

Core Features
-------------

- Authentication: registration and login with basic user management (`src/Controllers/AuthController.php`, `src/Models/User.php`).
- Profiles: company and client profile management (`src/Controllers/CompanyProfileController.php`, `src/Controllers/ClientProfileController.php`).
- Currencies: currency management and formatting (`src/Controllers/CurrencyController.php`, `src/Models/Currency.php`).
- Entities/Programmes/Purposes/Modes: CRUD for domain data used on invoices and reports.
- Tasks & Transactions: simple task tracking and transaction recording.

Invoicing (recent updates)
--------------------------

- Updated invoice creation view and form handling: see `src/Views/invoices/create.php` for the new layout and fields.
- Improved PDF layout and export pipeline: `src/Views/invoices/pdf.php` and `scripts/generate_pdf.py` handle logos, signatures and long line-items better.
- Invoice items are itemised with automatic totals and currency-aware formatting (`src/Models/InvoiceItem.php`, `src/Models/Invoice.php`).
- Generated PDFs are saved under `storage/pdfs/` and user-uploaded logos/signatures under `uploads/`.

Analytics & Reporting
---------------------

- Expenditure and crypto dashboards show visualisations in `src/Views/analytics/`.

Deployment & Storage
--------------------

- Config files located in `config/`.
- Database migrations under `database/migrations/`.

Notes and Next Steps
--------------------

- If you want a changelog or a PR with the invoice UI refinements, tell me and I can prepare it.
