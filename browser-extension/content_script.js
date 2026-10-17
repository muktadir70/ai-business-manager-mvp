// Attempts to extract basic product fields (OG tags, JSON-LD product) and sends to WP site
(function(){
    function getMeta(name) {
        var el = document.querySelector('meta[property="' + name + '"]') || document.querySelector('meta[name="' + name + '"]');
        return el ? el.content : '';
    }

    function parseJSONLD() {
        var scripts = document.querySelectorAll('script[type="application/ld+json"]');
        for (var s of scripts) {
            try {
                var j = JSON.parse(s.innerText);
                if (j && (j['@type'] === 'Product' || (Array.isArray(j['@type']) && j['@type'].includes('Product')))) {
                    return j;
                }
            } catch(e){}
        }
        return null;
    }

    function collect() {
        var p = {};
        p.title = getMeta('og:title') || document.title;
        p.description = getMeta('og:description') || getMeta('description') || '';
        p.images = [];
        var ogImg = getMeta('og:image');
        if (ogImg) p.images.push(ogImg);

        var jsonld = parseJSONLD();
        if (jsonld) {
            p.title = p.title || jsonld.name || '';
            p.description = p.description || jsonld.description || '';
            if (jsonld.image) {
                if (Array.isArray(jsonld.image)) p.images = p.images.concat(jsonld.image);
                else p.images.push(jsonld.image);
            }
            if (jsonld.offers && jsonld.offers.price) {
                p.price = jsonld.offers.price;
            }
        }
        // Basic fallback selectors (common marketplaces) - optional enhancements later
        return p;
    }

    // Listen for message from background popup to send import
    chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
        if (msg && msg.action === 'send_import') {
            const site = msg.site; // e.g. https://example.com
            const token = msg.token;
            const data = collect();
            fetch(site.replace(/\/$/, '') + '/wp-json/ai-biz-manager/v1/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-AI-BIZ-TOKEN': token
                },
                body: JSON.stringify(data),
                credentials: 'omit'
            }).then(r => r.json()).then(j => {
                sendResponse({ ok: true, result: j });
            }).catch(err => {
                sendResponse({ ok: false, error: err.toString() });
            });
            return true;
        }
    });
})();
