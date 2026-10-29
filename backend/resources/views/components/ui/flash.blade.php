@if (session('toast'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.MieTemu?.toast(@js(session('toast.message')), @js(session('toast.type', '')));
        });
    </script>
@endif
