import * as Sentry from '@sentry/browser/esm';



Sentry.init({
    dsn: process.env.SENTRY_DSN,
    release: process.env.VERSION
});
console.log('Sentry initialized!');
