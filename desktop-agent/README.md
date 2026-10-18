# AI Business Manager Desktop Agent

This folder contains a minimal local desktop agent scaffold. It is intended to run on your computer and can be extended to automate browser actions, request product data from websites, or send approved tasks to a WordPress site.

## What it does
- Exposes a small command-line interface.
- Can connect to the WordPress plugin via a configured API token.
- Can fetch product pages from a URL and send parsed data to the plugin import endpoint.
- Supports optional browser automation via Playwright or Selenium later.

## Setup
1. Create a virtual environment and install requirements:
   python3 -m venv .venv
   source .venv/bin/activate
   pip install -r requirements.txt
2. Export environment variables:
   export WP_SITE_URL="https://your-wordpress-site.com"
   export WP_API_TOKEN="your-api-token"
3. Run the CLI:
   python app.py --help

## Example use
- Send a page/product URL to the WordPress import REST endpoint:
  python app.py --import-url https://example.com/product

## Security
- This agent is intentionally local-only and requires explicit approval for every task.
- Never store raw passwords in this repo. Use browser profiles or OS secret stores.
- Sensitive actions should require human confirmation.
