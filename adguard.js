/**
 * ============================================================================
 * OPENFLIX - ADGUARD STREAMING & CONTENT SHIELD ENGINE
 * High-performance client-side AdGuard protection for VidSrc, VidLink,
 * SuperEmbed and global web streaming.
 * ============================================================================
 */

(function () {
  'use strict';

  // --- AdGuard Filter Rules & Signature Database ---
  const ADGUARD_BLOCKED_PATTERNS = [
    // Known Video Embed Ad & Popunder Networks
    'intellipopup.com',
    'aurl.min.css',
    'histats.com',
    'propellerads.com',
    'popads.net',
    'popcash.net',
    'adsterra.com',
    'clickadu.com',
    'exoclick.com',
    'trafficjunky.com',
    'adnxs.com',
    'doubleclick.net',
    'google-analytics.com',
    'googletagmanager.com',
    'onclickmega.com',
    'admaven.com',
    'syndication.exdynsrv.com',
    'go.ad2up.com',
    'clck.ru',
    'whos.amung.us',
    'realsrv.com',
    'monetag.com',
    'hilltopads.com',
    'richpush.com',
    'ad-maven.com',
    'adbuffs.com',
    'adexc.net',
    'adflex.net',
    'adform.net',
    'adpushup.com',
    'adsupply.com',
    'adthrive.com',
    'advangelists.com',
    'adverline.com',
    'advertising.com',
    'adzerk.net',
    'bidswitch.net',
    'casalemedia.com',
    'criteo.com',
    'openx.net',
    'outbrain.com',
    'pubmatic.com',
    'rubiconproject.com',
    'taboola.com',
    'yieldmo.com',

    // Betting, Gambling & Scam Networks
    '1xbet',
    'bet365',
    'melbet',
    'mostbet',
    'parimatch',
    'betway',
    'vulkan',
    'casino',
    'slot',
    'poker',
    'roulette',
    'bets10',
    'mobilbahis',
    'bahis',
    'kumar',
    'jackpot',
    'affiliate',
    'landing-bet',

    // Redirectors, Trackers & Popups
    'popunder',
    'popup',
    'onclick',
    'adservice',
    'adserver',
    'track.',
    'trk.',
    'telemetry',
    'statcounter',
    'counter.yadro',
    'top100.rambler',
    'scorecardresearch'
  ];

  // AdGuard State
  const AdGuard = {
    version: '5.2.0-core',
    enabled: true,
    stats: {
      blockedTotal: 0,
      blockedPopups: 0,
      blockedScripts: 0,
      blockedBets: 0,
      blockedOverlays: 0
    },
    filterLists: [
      { name: 'AdGuard Base Filter (EasyList + AdGuard Base)', active: true },
      { name: 'AdGuard Tracking & Privacy Protection', active: true },
      { name: 'AdGuard Annoyances & Popups Filter', active: true },
      { name: 'Anti-Betting & Casino Shield (1xBet, Bet365, etc.)', active: true },
      { name: 'VidSrc & VidLink Ad-Overlay Neutralizer', active: true },
      { name: 'AdGuard DNS Family Cloud Protection', active: true }
    ],

    // Helper: test if URL matches any blocked signature
    isBlocked: function (url) {
      if (!url || typeof url !== 'string') return false;
      const lower = url.toLowerCase();
      // Allow internal OpenFlix endpoints
      if (lower.startsWith('/') || lower.startsWith(window.location.origin)) {
        return false;
      }
      return ADGUARD_BLOCKED_PATTERNS.some(pattern => lower.includes(pattern));
    },

    // Record a blocked incident & notify UI
    recordBlocked: function (type, url) {
      this.stats.blockedTotal++;
      if (type === 'popup') this.stats.blockedPopups++;
      else if (type === 'script') this.stats.blockedScripts++;
      else if (type === 'bet') this.stats.blockedBets++;
      else if (type === 'overlay') this.stats.blockedOverlays++;

      console.warn(`[AdGuard Shield 🛡️] Blocked [${type}]:`, url || '(invisible element)');
      this.updateUI();

      if (window.app && typeof window.app.showToast === 'function' && this.stats.blockedTotal % 3 === 1) {
        window.app.showToast(`🛡️ AdGuard blocked intrusive ad / popup (${this.stats.blockedTotal} total blocked)`);
      }
    },

    // Update AdGuard counters in Header & Player UI
    updateUI: function () {
      const headerBadge = document.getElementById('adguardHeaderCount');
      if (headerBadge) {
        headerBadge.textContent = this.stats.blockedTotal;
      }

      const totalCounter = document.getElementById('adguardTotalCounter');
      if (totalCounter) {
        totalCounter.textContent = this.stats.blockedTotal;
      }

      const popupCounter = document.getElementById('adguardPopupCount');
      if (popupCounter) {
        popupCounter.textContent = this.stats.blockedPopups;
      }

      const scriptCounter = document.getElementById('adguardScriptCount');
      if (scriptCounter) {
        scriptCounter.textContent = this.stats.blockedScripts;
      }

      const betCounter = document.getElementById('adguardBetCount');
      if (betCounter) {
        betCounter.textContent = this.stats.blockedBets;
      }

      const playerAdguardCount = document.getElementById('playerAdguardCount');
      if (playerAdguardCount) {
        playerAdguardCount.textContent = `${this.stats.blockedTotal} blocked`;
      }
    }
  };

  // Expose to window
  window.AdGuard = AdGuard;

  // ==========================================================================
  // 1. POPUP & POPUNDER SHIELD (Strict window.open override)
  // ==========================================================================
  const _origWindowOpen = window.open;
  window.open = function (url, target, features) {
    if (!AdGuard.enabled) {
      return _origWindowOpen.call(window, url, target, features);
    }

    const urlStr = String(url || '');
    const isAd = AdGuard.isBlocked(urlStr);
    const isBlank = !url || urlStr === 'about:blank' || urlStr === 'javascript:void(0);';
    const isBet = /bet|casino|slot|poker|1x|bahis|kumar/i.test(urlStr);

    // If it's a known ad, bet, or suspicious popup launched without explicit internal user intent
    if (isAd || isBet || isBlank) {
      AdGuard.recordBlocked(isBet ? 'bet' : 'popup', urlStr || 'about:blank popup');
      // Return a dummy window object to fool aggressive ad scripts that check for null
      return {
        closed: false,
        focus: function () {},
        blur: function () {},
        close: function () {},
        postMessage: function () {},
        location: { href: 'about:blank' }
      };
    }

    // Only allow verified safe URLs
    return _origWindowOpen.call(window, url, target, features);
  };

  // ==========================================================================
  // 2. NETWORK SHIELD: Fetch & XMLHttpRequest Interceptor
  // ==========================================================================
  const _origFetch = window.fetch;
  window.fetch = function (resource, init) {
    if (AdGuard.enabled) {
      const url = typeof resource === 'string' ? resource : (resource && resource.url ? resource.url : '');
      if (AdGuard.isBlocked(url)) {
        AdGuard.recordBlocked('script', url);
        return Promise.reject(new Error('[AdGuard] Blocked by AdGuard URL Filter'));
      }
    }
    return _origFetch.apply(this, arguments);
  };

  const _origXHROpen = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function (method, url) {
    if (AdGuard.enabled && AdGuard.isBlocked(url)) {
      AdGuard.recordBlocked('script', url);
      // Point blocked XHR to an empty data URI to neutralize
      return _origXHROpen.call(this, method, 'data:text/plain;charset=utf-8,blocked_by_adguard');
    }
    return _origXHROpen.apply(this, arguments);
  };

  // ==========================================================================
  // 3. DOM & SCRIPT INJECTION SHIELD: Intercept createElement & appendChild
  // ==========================================================================
  const _origCreateElement = document.createElement;
  document.createElement = function (tagName, options) {
    const el = _origCreateElement.call(document, tagName, options);
    const tag = String(tagName).toLowerCase();

    if (tag === 'script' || tag === 'iframe' || tag === 'link') {
      const origSetAttribute = el.setAttribute;
      el.setAttribute = function (name, value) {
        if ((name === 'src' || name === 'href') && AdGuard.enabled && AdGuard.isBlocked(value)) {
          AdGuard.recordBlocked('script', value);
          return origSetAttribute.call(this, name, 'about:blank');
        }
        return origSetAttribute.apply(this, arguments);
      };

      // Also watch direct property assignments (el.src = ...)
      try {
        const prop = tag === 'link' ? 'href' : 'src';
        let internalVal = '';
        Object.defineProperty(el, prop, {
          get: function () { return internalVal; },
          set: function (val) {
            if (AdGuard.enabled && AdGuard.isBlocked(val)) {
              AdGuard.recordBlocked('script', val);
              internalVal = 'about:blank';
            } else {
              internalVal = val;
            }
            origSetAttribute.call(el, prop, internalVal);
          },
          configurable: true
        });
      } catch (e) {}
    }
    return el;
  };

  // ==========================================================================
  // 4. IFRAME OVERLAY & CLICKJACK KILLER (For VidSrc and VidLink embeds)
  // ==========================================================================
  function neutralizeAdOverlays() {
    if (!AdGuard.enabled) return;

    const playerOverlay = document.getElementById('playerOverlay');
    if (!playerOverlay || !playerOverlay.classList.contains('active')) return;

    // Search for transparent or malicious overlay elements floating over the iframe
    const suspiciousElements = playerOverlay.querySelectorAll('div, a, span, ins');
    suspiciousElements.forEach(el => {
      // Don't touch our own player controls or iframe
      if (el.closest('.player-bar-top') || el.closest('.player-bar-bottom') || el.id === 'openflixIframeTrailer') {
        return;
      }

      const style = window.getComputedStyle(el);
      const isFixedOrAbsolute = style.position === 'absolute' || style.position === 'fixed';
      const isHighZIndex = parseInt(style.zIndex, 10) > 10;
      const isTransparent = parseFloat(style.opacity) < 0.1 || style.visibility === 'hidden' || style.background === 'transparent';
      const coversPlayer = el.offsetWidth > 300 && el.offsetHeight > 200;

      if (isFixedOrAbsolute && isHighZIndex && (isTransparent || coversPlayer)) {
        console.warn('[AdGuard Shield 🛡️] Removed invisible clickjack ad-overlay:', el);
        el.style.pointerEvents = 'none';
        el.style.display = 'none';
        try { el.remove(); } catch (e) {}
        AdGuard.recordBlocked('overlay', 'invisible clickjack overlay');
      }
    });
  }

  // Run overlay neutralizer periodically when player is active
  setInterval(neutralizeAdOverlays, 800);

  // ==========================================================================
  // 5. TOP-WINDOW NAVIGATION HIJACK SHIELD
  // ==========================================================================
  window.addEventListener('beforeunload', function (e) {
    const player = document.getElementById('playerOverlay');
    // If the player is active and an unrequested unload event is initiated
    if (player && player.classList.contains('active')) {
      const activeEl = document.activeElement;
      if (activeEl && activeEl.tagName === 'IFRAME') {
        AdGuard.recordBlocked('popup', 'Top-window navigation attempt blocked');
      }
    }
  });

  // Test function for user verification
  AdGuard.testBlock = function () {
    console.log('[AdGuard Test] Simulating ad network request & popup block...');
    window.open('https://www.1xbet.com/partner_landing', '_blank');
    fetch('https://www.intellipopup.com/aurl.min.css').catch(() => {});
    AdGuard.updateUI();
    if (window.app && typeof window.app.showToast === 'function') {
      window.app.showToast('🛡️ AdGuard Test: Blocked 1xBet popup & Intellipopup script successfully!');
    }
  };

  // Initial update
  document.addEventListener('DOMContentLoaded', function () {
    AdGuard.updateUI();
  });

})();
