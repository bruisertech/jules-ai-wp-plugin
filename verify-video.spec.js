const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();

    // Desktop View
    const desktopContext = await browser.newContext({ viewport: { width: 1280, height: 720 } });
    const desktopPage = await desktopContext.newPage();

    // Fast forward animations
    await desktopPage.addInitScript(() => {
        const style = document.createElement('style');
        style.textContent = `
            * {
                animation: none !important;
                transition: none !important;
            }
        `;
        document.head.appendChild(style);
    });

    await desktopPage.goto('https://lhparfum.com/', { waitUntil: 'networkidle' });
    await desktopPage.screenshot({ path: '/home/jules/verification/video_desktop.png' });
    await desktopContext.close();

    // Mobile View
    const mobileContext = await browser.newContext({
        viewport: { width: 390, height: 844 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/604.1'
    });
    const mobilePage = await mobileContext.newPage();

    // Fast forward animations
    await mobilePage.addInitScript(() => {
        const style = document.createElement('style');
        style.textContent = `
            * {
                animation: none !important;
                transition: none !important;
            }
        `;
        document.head.appendChild(style);
    });

    await mobilePage.goto('https://lhparfum.com/', { waitUntil: 'networkidle' });
    await mobilePage.screenshot({ path: '/home/jules/verification/video_mobile.png' });

    await browser.close();
    console.log("Screenshots captured successfully.");
})();
