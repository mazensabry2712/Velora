{{-- Dark mode: run BEFORE render to avoid flash. Kept inline (not an external file)
     on purpose, so the browser doesn't need a second request before it can apply
     the class to <html>. --}}
<script>
    (function () {
        var saved = localStorage.getItem('adminDarkMode');
        if (saved === null) {
            saved = localStorage.getItem('darkMode');
        }
        if (saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
