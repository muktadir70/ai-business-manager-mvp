#!/usr/bin/env python3
import argparse
import json
import os
import sys

import requests


def import_product(site_url: str, api_token: str, product_url: str):
    payload = {
        "source_url": product_url,
        "title": "Imported via Desktop Agent",
        "description": "This item was imported from a browser-driven desktop process.",
        "price": 0,
        "images": [],
    }
    headers = {
        "Content-Type": "application/json",
        "X-AI-BIZ-TOKEN": api_token,
    }
    endpoint = site_url.rstrip("/") + "/wp-json/ai-biz-manager/v1/import"
    response = requests.post(endpoint, headers=headers, data=json.dumps(payload), timeout=60)
    print(response.status_code)
    print(response.text)


def main():
    parser = argparse.ArgumentParser(description="AI Business Manager desktop agent")
    parser.add_argument("--site", default=os.getenv("WP_SITE_URL"), help="WordPress site URL")
    parser.add_argument("--token", default=os.getenv("WP_API_TOKEN"), help="WordPress API token")
    parser.add_argument("--import-url", help="Import a product page URL into WordPress")
    args = parser.parse_args()

    if not args.site or not args.token:
        print("Please set WP_SITE_URL and WP_API_TOKEN or pass --site and --token.", file=sys.stderr)
        sys.exit(1)

    if args.import_url:
        import_product(args.site, args.token, args.import_url)
        return

    parser.print_help()


if __name__ == "__main__":
    main()
