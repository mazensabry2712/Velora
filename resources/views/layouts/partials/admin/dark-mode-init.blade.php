{{-- Dark mode: run BEFORE render to avoid flash. Kept inline (not an external file)
     on purpose, so the browser doesn't need a second request before it can apply
     the class to <html>.

     Default is ON. Once the person uses the Dark Mode toggle in the profile
     menu, their explicit choice (saved as 'true'/'false' in localStorage)
     always wins from then on. --}}
<script>
    (function() {
        var saved = localStorage.getItem('adminDarkMode');
        if (saved === null) {
            saved = localStorage.getItem('darkMode');
        }
        if (saved !== 'false') {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
