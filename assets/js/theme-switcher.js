// Theme Switcher JavaScript
(function() {
  'use strict';

  // Theme configuration
  const themes = {
    theme1: {
      name: 'Theme 1',
      label: 'Classic'
    },
    theme2: {
      name: 'Theme 2',
      label: 'Modern'
    }
  };

  // Initialize theme on page load
  function initTheme() {
    const savedTheme = localStorage.getItem('selectedTheme') || 'theme1';
    switchTheme(savedTheme, false);
  }

  // Switch theme function
  function switchTheme(themeName, save = true) {
    if (!themes[themeName]) {
      console.warn('Theme not found:', themeName);
      return;
    }

    // Set theme attribute on html element
    document.documentElement.setAttribute('data-theme', themeName);

    // Save to localStorage
    if (save) {
      localStorage.setItem('selectedTheme', themeName);
    }

    // Update active button
    updateThemeButtons(themeName);

    // Dispatch custom event for theme change
    const event = new CustomEvent('themeChanged', {
      detail: { theme: themeName }
    });
    document.dispatchEvent(event);
  }

  // Update theme selector buttons
  function updateThemeButtons(activeTheme) {
    const buttons = document.querySelectorAll('.theme-btn');
    buttons.forEach(btn => {
      const btnTheme = btn.getAttribute('data-theme');
      if (btnTheme === activeTheme) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  // Create theme selector HTML (only on index.php)
  function createThemeSelector() {
    // Only show theme selector on index.php
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const isIndexPage = currentPage === 'index.php' || 
                        currentPage === '' || 
                        currentPage.includes('index') ||
                        window.location.pathname.endsWith('/');
    
    if (!isIndexPage) {
      return; // Don't create selector on other pages
    }

    const selector = document.createElement('div');
    selector.className = 'theme-selector';
    selector.innerHTML = Object.keys(themes).map(themeKey => {
      const theme = themes[themeKey];
      return `<button class="theme-btn" data-theme="${themeKey}" onclick="window.switchTheme('${themeKey}')">
        ${theme.label}
      </button>`;
    }).join('');

    document.body.appendChild(selector);
  }

  // Make switchTheme available globally
  window.switchTheme = function(themeName) {
    switchTheme(themeName, true);
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initTheme();
      createThemeSelector();
    });
  } else {
    initTheme();
    createThemeSelector();
  }
})();
