# Travel World Theme System

## Folder Structure

```
travelworld/
├── themes/
│   ├── base.css              # Common base styles for all themes
│   ├── theme-selector.css     # Theme selector component styles
│   ├── theme1/
│   │   └── style.css         # Theme 1 (Classic) styles
│   └── theme2/
│       └── style.css         # Theme 2 (Modern) styles
├── assets/
│   └── js/
│       └── theme-switcher.js # Theme switching functionality
└── includes/
    └── theme-head.php        # Include file for theme system
```

## How to Use

### In PHP Files

Simply include the theme head file in the `<head>` section:

```php
<?php include 'includes/theme-head.php'; ?>
```

This will automatically:
- Load Google Fonts (Poppins)
- Load base.css
- Load theme1/style.css
- Load theme2/style.css
- Load theme-selector.css
- Load theme-switcher.js

### Theme Switching

The theme switcher is automatically added to all pages. Users can:
- Click "Classic" for Theme 1
- Click "Modern" for Theme 2
- Their preference is saved in localStorage

## Theme Details

### Theme 1 - Classic
- Light, clean design
- Soft colors (orange/blue palette)
- Traditional card layouts
- Radial gradient backgrounds

### Theme 2 - Modern
- Dark theme with purple/indigo accents
- Glassmorphism effects
- Animated hover effects
- Modern gradient backgrounds

## Customization

To add a new theme:
1. Create a new folder in `themes/` (e.g., `theme3/`)
2. Create `style.css` in that folder
3. Add theme variables using `[data-theme="theme3"]` selector
4. Update `theme-switcher.js` to include the new theme
5. Update `theme-selector.css` if needed

## CSS Variables

Both themes use CSS custom properties:
- `--primary`: Primary brand color
- `--accent`: Accent color
- `--text`: Main text color
- `--muted`: Muted/secondary text color
- `--radius-lg`: Large border radius
- `--shadow-card`: Card shadow
