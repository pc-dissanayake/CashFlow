#!/usr/bin/env python3
"""
Generate a self-signed PKCS#12 certificate for PDF digital signing.
Usage: python generate_cert.py --company "Company Name" --out /path/to/cert.p12
"""

import argparse
import os
from datetime import datetime, timedelta, timezone

from cryptography import x509
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.x509.oid import NameOID
from cryptography.hazmat.primitives.serialization import pkcs12, BestAvailableEncryption


def generate_certificate(company_name: str, output_path: str, password: str = "cashflow"):
    """Generate a self-signed PKCS#12 certificate for the company."""

    # Generate RSA private key
    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)

    # Build the certificate
    subject = issuer = x509.Name([
        x509.NameAttribute(NameOID.COUNTRY_NAME, "LK"),
        x509.NameAttribute(NameOID.ORGANIZATION_NAME, company_name),
        x509.NameAttribute(NameOID.COMMON_NAME, f"{company_name} Document Signing"),
    ])

    now = datetime.now(timezone.utc)
    cert = (
        x509.CertificateBuilder()
        .subject_name(subject)
        .issuer_name(issuer)
        .public_key(key.public_key())
        .serial_number(x509.random_serial_number())
        .not_valid_before(now)
        .not_valid_after(now + timedelta(days=3650))  # 10 years
        .add_extension(
            x509.KeyUsage(
                digital_signature=True,
                content_commitment=True,  # non-repudiation
                key_encipherment=False,
                data_encipherment=False,
                key_agreement=False,
                key_cert_sign=False,
                crl_sign=False,
                encipher_only=False,
                decipher_only=False,
            ),
            critical=True,
        )
        .sign(key, hashes.SHA256())
    )

    # Export as PKCS#12
    pfx_data = pkcs12.serialize_key_and_certificates(
        name=company_name.encode(),
        key=key,
        cert=cert,
        cas=None,
        encryption_algorithm=BestAvailableEncryption(password.encode()),
    )

    os.makedirs(os.path.dirname(output_path) or ".", exist_ok=True)
    with open(output_path, "wb") as f:
        f.write(pfx_data)

    print(f"Certificate generated: {output_path}")
    return output_path


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Generate signing certificate")
    parser.add_argument("--company", required=True, help="Company name")
    parser.add_argument("--out", required=True, help="Output .p12 file path")
    parser.add_argument("--password", default="cashflow", help="PKCS#12 password")
    args = parser.parse_args()

    generate_certificate(args.company, args.out, args.password)
