import * as Sentry from '@sentry/browser/esm';

if (process.env.NODE_ENV === 'production') {
    // Code will only appear in production build.
    try {
        Sentry.init({
            dsn: process.env.SENTRY_DSN,
            release: process.env.APP_VERSION
        });
    } catch (e) {
        // sentry init error
    }
}