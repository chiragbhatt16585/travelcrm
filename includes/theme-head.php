<?php
// Theme System Head Include
// Include this in the <head> section of all pages
?>
<base href="/">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="themes/base.css">
<link rel="stylesheet" href="themes/theme1/style.css">
<link rel="stylesheet" href="themes/theme2/style.css">
<link rel="stylesheet" href="themes/theme-selector.css">
<script src="assets/js/theme-switcher.js" defer></script>
<script>
  function toggleMobileMenu() {
    var menu = document.getElementById('mobileMenu');
    if (menu) menu.classList.toggle('open');
  }
</script>
