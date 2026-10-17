AI Business Manager — MVP scaffold
---------------------------------

What this scaffold provides:
- A modular WordPress plugin skeleton (wp-ai-business-manager)
- A REST endpoint /wp-json/ai-biz-manager/v1/import to accept product data
- WooCommerce product creation (draft) + image sideloading
- A simple AI client wrapper (OpenAI example)
- Companion browser extension (unpacked) that extracts OG/meta/JSON-LD product data and posts to the REST endpoint

How to install (quick):
1. Copy the `wp-ai-business-manager` folder into `wp-content/plugins/`
2. Activate the plugin in WP admin.
3. Go to WP admin → AI Biz Manager and paste your OpenAI key (or leave blank for now). Copy the API token (or set one).
4. Load the browser extension unpacked (Chrome/Edge: chrome://extensions → Load unpacked → point to browser-extension/).
5. In the extension storage (future UI), set `site` (e.g. https://staging.example.com) and `token` to the API token shown in settings.
6. Visit a product page, right-click → Import product to AI Biz Manager.

Required keys / permissions to proceed for full features:
- OpenAI (or other LLM) API key for description rewriting, SEO, replies.
- WooCommerce active for product import.
- Social platform OAuth apps (Facebook/Meta, Instagram, X, LinkedIn, YouTube) — we will build OAuth flows and ask you to register dev apps for each provider.
- WhatsApp Business API access (optional — typically requires Business API setup).

Next steps I can do immediately when you provide details:
- Push this scaffold to a repository (create or update files) — give me repo NWO and permission.
- Improve content extraction rules for the 2–3 target sites you list (marketplace-specific selectors + structured data handling).
- Implement AI rewriting flow (connect to OpenAI + test rewriting).
- Add admin UI to manage connected social accounts and a scheduled queue.

Security note:
- Do not paste private API keys or WP admin passwords into this chat. Use a secure channel (GitHub Secrets, Vault, or a private share link) that you control. I will provide instructions for secure transfer once you indicate how you want to proceed.
