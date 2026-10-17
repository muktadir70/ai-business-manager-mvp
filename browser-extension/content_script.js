// Enhanced browser extension content script with site-specific extraction for Daraz, AliExpress, Taobao
(function(){
    function getMeta(name) {
        var el = document.querySelector('meta[property="' + name + '"]') || document.querySelector('meta[name="' + name + '"]');
        return el ? el.content : '';
    }

    function parseJSONLD() {
        var scripts = document.querySelectorAll('script[type="application/ld+json"]');
        for (var i = 0; i < scripts.length; i++) {
            try {
                var j = JSON.parse(scripts[i].innerText);
                if (!j) continue;
                if (j['@type'] === 'Product' || (Array.isArray(j['@type']) && j['@type'].includes('Product'))) {
                    return j;
                }
                if (Array.isArray(j)) {
                    for (var k=0;k<j.length;k++){
                        if (j[k] && j[k]['@type'] === 'Product') return j[k];
                    }
                }
            } catch(e){}
        }
        return null;
    }

    function selectText(selectors) {
        for (var i=0;i<selectors.length;i++){
            var el = document.querySelector(selectors[i]);
            if (el && el.innerText.trim()) return el.innerText.trim();
            if (el && el.value && el.value.trim) return el.value.trim();
        }
        return '';
    }

    function selectImage(selectors) {
        var imgs = [];
        for (var i=0;i<selectors.length;i++){
            var el = document.querySelector(selectors[i]);
            if (el) {
                if (el.src) imgs.push(el.src);
                else if (el.getAttribute) imgs.push(el.getAttribute('data-src')||el.getAttribute('src'));
            }
        }
        // also collect og:image
        var og = getMeta('og:image'); if (og) imgs.push(og);
        return imgs.filter(Boolean);
    }

    function collect() {
        var host = location.hostname;
        var data = { images: [] };
        if (host.indexOf('daraz') !== -1) {
            data.title = selectText(['h1.pdp-mod-product-badge-title','h1.pdp-product-title','.pdp__title','h1']) || getMeta('og:title') || document.title;
            data.description = selectText(['div.pdp-product-desc','.pdp-product-detail','meta[name=description]']) || getMeta('og:description') || '';
            data.price = selectText(['span.pdp-price','div.pdp-product-price','span.price']) || '';
            data.images = selectImage(['.pdp-mod-product-gallery img','.pdp-image-gallery img']);
        } else if (host.indexOf('aliexpress') !== -1) {
            data.title = selectText(['h1.product-title-text','h1.product-title','h1']) || getMeta('og:title') || document.title;
            data.description = selectText(['#product-description','meta[name=description]']) || getMeta('og:description') || '';
            data.price = selectText(['span.product-price-value','span.price']) || '';
            data.images = selectImage(['img.magnifier-image','#icImg','.images-view img']);
        } else if (host.indexOf('taobao') !== -1 || host.indexOf('tmall') !== -1) {
            data.title = selectText(['.tb-main-title','#J_Title .tb-main-title','h3.tb-main-title']) || getMeta('og:title') || document.title;
            data.description = selectText(['#J_DivItemDesc','meta[name=description]']) || getMeta('og:description') || '';
            data.price = selectText(['.tb-rmb-num','.tm-price']) || '';
            data.images = selectImage(['#J_ImgBooth','.tb-thumb img']);
        } else {
            // generic
            var jsonld = parseJSONLD();
            data.title = (jsonld && (jsonld.name || jsonld.title)) || getMeta('og:title') || document.title;
            data.description = (jsonld && jsonld.description) || getMeta('og:description') || '';
            data.price = (jsonld && jsonld.offers && jsonld.offers.price) || '';
            data.images = [];
            var og = getMeta('og:image'); if (og) data.images.push(og);
        }
        // fallback: try open graph image
        if (!data.images || data.images.length === 0) {
            var og = getMeta('og:image'); if (og) data.images.push(og);
            // also try to collect main images
            document.querySelectorAll('img').forEach(function(i){ if (i.src && i.width>50) data.images.push(i.src); });
        }
        // dedupe
        data.images = data.images.filter(function(v,i,a){return a.indexOf(v)===i;}).slice(0,10);
        return data;
    }

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
