chrome.runtime.onInstalled.addListener(() => {
    chrome.contextMenus.create({
        id: 'ai-biz-import',
        title: 'Import product to AI Biz Manager',
        contexts: ['page']
    });
});

chrome.contextMenus.onClicked.addListener((info, tab) => {
    if (info.menuItemId === 'ai-biz-import') {
        chrome.storage.sync.get(['site', 'token'], (items) => {
            chrome.tabs.sendMessage(tab.id, { action: 'send_import', site: items.site, token: items.token }, response => {
                if (chrome.runtime.lastError) {
                    console.error(chrome.runtime.lastError);
                    alert('Extension error: ' + chrome.runtime.lastError.message);
                } else {
                    console.log('Import response', response);
                }
            });
        });
    }
});
