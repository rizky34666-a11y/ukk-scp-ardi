<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('{{ route('pwa.sw', [], false) }}', { scope: '/' }).catch(function () {});
        });
    }
</script>
