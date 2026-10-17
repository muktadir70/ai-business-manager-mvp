# AI Business Manager — MVP feature/import-ai-smm-mvp

This branch adds site-specific extractors for Daraz, AliExpress and Taobao (browser extension + server-side extractor skeleton), a price calculation helper, social OAuth scaffolding, and a Messenger webhook scaffold.

What's included
- modules/extractors/class-extractors.php — server-side parsing rules (DOM + JSON-LD)
- modules/importer/class-price.php — simple price calculation (markup percent)
- modules/social/class-oauth.php — placeholder for OAuth token storage and exchange
- modules/messenger/class-messenger.php — REST webhook endpoint to receive Messenger events
- browser-extension/content_script.js — updated with site-specific DOM selectors for Daraz, AliExpress, Taobao
- updated admin settings to configure default markup and select AI provider (OpenAI/Azure/Anthropic)

Next steps
- Implement provider-specific token exchange and posting logic for Facebook and X.
- Add thorough unit/integration tests and stronger error handling.
- Harden server-side scraping with proxy/CAPTCHA handling if needed.
