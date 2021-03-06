<div class="content">
    <div class="title">Something went wrong.</div>

    @if(app()->bound('sentry') && !empty(Sentry::getLastEventID()))
        <div class="subtitle">Error ID: {{ Sentry::getLastEventID() }}</div>

        <!-- Sentry JS SDK 2.1.+ required -->
        <script src="https://cdn.ravenjs.com/3.3.0/raven.min.js"></script>

        <script>
          Raven.showReportDialog({
            eventId: '{{ Sentry::getLastEventID() }}',
            // use the public DSN (dont include your secret!)
            dsn: 'https://0da3b50e17b945918c0f990719ed921b@sentry.io/251314',
            user: {
              'name': 'xxx先生/小姐',
              'email': 'xxx@xxx.com',
            }
          });
        </script>
    @endif
</div>