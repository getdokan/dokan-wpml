import domReady from '@wordpress/dom-ready';
// Constants
const WPML_LANGUAGE_SWITCHER_SELECTOR = '.wpml-ls-item a';

// Function to preserve hash fragments in WPML language switcher links
const preserveHashInLanguageSwitcherLinks = async () => {
    const currentHash = window.location.hash;

    if (!currentHash) {
        return;
    }

    const languageLinks = document.querySelectorAll(WPML_LANGUAGE_SWITCHER_SELECTOR);

    languageLinks.forEach(link => {
        const originalHref = link.getAttribute('href');

        if (originalHref) {
            const baseUrl = originalHref.split('#')[0];
            link.setAttribute('href', baseUrl + currentHash);
        }
    });
};

// Handle DOM ready state
domReady(async () => {
    // Handle initial page load
    await preserveHashInLanguageSwitcherLinks();

    // Listen for hash changes
    window.addEventListener('hashchange', async () => {
        await preserveHashInLanguageSwitcherLinks();
    });
});
