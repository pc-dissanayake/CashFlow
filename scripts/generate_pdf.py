#!/usr/bin/env python3
"""
Generate a digitally signed PDF from an invoice HTML page.

Usage:
    python3 generate_pdf.py \
        --url "http://localhost/invoices/3/pdf" \
        --cookie "PHPSESSID=abc123" \
        --company "Deep Diagnostics" \
        --cert /var/www/CashFlow/storage/certs/company_1.p12 \
        --output /var/www/CashFlow/storage/pdfs/invoice_3.pdf

Flow:
  1. Fetch the invoice HTML (using the session cookie for auth)
  2. Convert HTML → PDF via WeasyPrint
  3. Digitally sign the PDF with the company's PKCS#12 certificate
  4. Write the signed PDF to --output
"""

import argparse
import os
import sys
import tempfile
from datetime import datetime, timezone

import weasyprint

# ── PDF signing with endesive ──────────────────────────────────────
from endesive.pdf import cms as pdf_cms
from cryptography.hazmat.primitives.serialization import pkcs12
from cryptography.hazmat.primitives import hashes


CERT_PASSWORD = "cashflow"
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def fetch_html_to_pdf(url: str, cookie: str, base_url: str) -> bytes:
    """Render the invoice URL to a PDF byte string via WeasyPrint."""
    import ssl
    import urllib.request

    # Create an SSL context that skips certificate verification for
    # loopback requests (the script talks to its own server which likely
    # uses a self-signed cert).
    no_verify_ctx = ssl.create_default_context()
    no_verify_ctx.check_hostname = False
    no_verify_ctx.verify_mode = ssl.CERT_NONE

    # Fetch the HTML from the live app
    req = urllib.request.Request(url)
    if cookie:
        req.add_header("Cookie", cookie)
    with urllib.request.urlopen(req, timeout=30, context=no_verify_ctx) as resp:
        html_bytes = resp.read()

    html_string = html_bytes.decode("utf-8")

    # Custom URL fetcher that forwards the session cookie for same-origin resources
    def url_fetcher(fetched_url, timeout=10, ssl_context=None):
        if cookie and fetched_url.startswith(base_url):
            resource_req = urllib.request.Request(fetched_url)
            resource_req.add_header("Cookie", cookie)
            with urllib.request.urlopen(resource_req, timeout=timeout, context=no_verify_ctx) as r:
                return {
                    "string": r.read(),
                    "mime_type": r.headers.get_content_type(),
                    "encoding": r.headers.get_content_charset(),
                }
        return weasyprint.default_url_fetcher(fetched_url, timeout=timeout, ssl_context=no_verify_ctx)

    # Convert to PDF
    doc = weasyprint.HTML(string=html_string, base_url=base_url, url_fetcher=url_fetcher)
    return doc.write_pdf()


def sign_pdf(pdf_bytes: bytes, cert_path: str, password: str = CERT_PASSWORD) -> bytes:
    """Digitally sign a PDF using a PKCS#12 certificate."""
    if not os.path.exists(cert_path):
        # No cert yet – return unsigned
        print(f"Warning: Certificate not found at {cert_path}, returning unsigned PDF", file=sys.stderr)
        return pdf_bytes

    with open(cert_path, "rb") as f:
        p12_data = f.read()

    # Load the PKCS#12 bundle
    private_key, certificate, additional_certs = pkcs12.load_key_and_certificates(
        p12_data, password.encode()
    )

    # Build signing parameters
    now = datetime.now(timezone.utc)
    dct = {
        "aligned": 0,
        "sigflags": 3,
        "sigflag": 3,
        "sigpage": 0,
        "sigbutton": True,
        "sigfield": "Signature1",
        "auto_sigfield": True,
        "sigandcertify": True,
        "signaturebox": (0, 0, 0, 0),  # invisible signature
        "signature": f"Digitally signed on {now.strftime('%Y-%m-%d %H:%M:%S UTC')}",
        "contact": "",
        "location": "",
        "signingdate": now.strftime("D:%Y%m%d%H%M%S+00'00'"),
        "reason": "Document authenticity verification",
        "password": password,
    }

    # Sign the PDF
    # endesive's sign() returns only the incremental update (signature objects).
    # We must concatenate it with the original PDF to produce a valid signed file.
    signed_data = pdf_cms.sign(
        pdf_bytes,
        dct,
        private_key,
        certificate,
        additional_certs or [],
        "sha256",
        None,
    )

    return pdf_bytes + signed_data


def ensure_cert(company_name: str, company_id: int) -> str:
    """Ensure a certificate exists for this company; generate if missing."""
    certs_dir = os.path.join(BASE_DIR, "storage", "certs")
    os.makedirs(certs_dir, exist_ok=True)
    cert_path = os.path.join(certs_dir, f"company_{company_id}.p12")

    if not os.path.exists(cert_path):
        print(f"Generating certificate for '{company_name}'...", file=sys.stderr)
        # Import the cert generator
        sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
        from generate_cert import generate_certificate
        generate_certificate(company_name, cert_path, CERT_PASSWORD)

    return cert_path


def main():
    parser = argparse.ArgumentParser(description="Generate a signed invoice PDF")
    parser.add_argument("--url", required=True, help="Full URL to the invoice PDF view")
    parser.add_argument("--cookie", default="", help="Session cookie string (e.g. PHPSESSID=abc123)")
    parser.add_argument("--company", required=True, help="Company name (for cert generation)")
    parser.add_argument("--company-id", required=True, type=int, help="Company profile ID")
    parser.add_argument("--base-url", default="http://localhost", help="Base URL for resolving relative resources")
    parser.add_argument("--output", required=True, help="Output path for signed PDF")
    parser.add_argument("--cert", default="", help="Path to .p12 certificate (auto-generated if omitted)")
    args = parser.parse_args()

    # Determine certificate
    cert_path = args.cert if args.cert else ensure_cert(args.company, args.company_id)

    # Step 1: Render HTML to PDF
    print("Rendering HTML to PDF...", file=sys.stderr)
    pdf_bytes = fetch_html_to_pdf(args.url, args.cookie, args.base_url)

    # Step 2: Sign the PDF
    print("Signing PDF...", file=sys.stderr)
    signed_pdf = sign_pdf(pdf_bytes, cert_path)

    # Step 3: Write output
    os.makedirs(os.path.dirname(args.output) or ".", exist_ok=True)
    with open(args.output, "wb") as f:
        f.write(signed_pdf)

    # Print the output path to stdout so PHP can read it
    print(args.output)


if __name__ == "__main__":
    main()
