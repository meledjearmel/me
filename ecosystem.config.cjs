// Configuration PM2 — Portfolio Armel
// Usage : pm2 start ecosystem.config.cjs

module.exports = {
    apps: [
        {
            name: 'portfolio-queue',
            script: 'php',
            args: 'artisan queue:work --sleep=3 --tries=3 --max-time=3600',
            cwd: '/srv/projects/me',
            exec_mode: 'fork',
            instances: 1,
            autorestart: true,
            max_restarts: 10,
            max_memory_restart: '300M',
            env: {
                APP_ENV: 'production',
            },
        },
        {
            name: 'portfolio-scheduler',
            // artisan schedule:work remplace le cron classique : boucle en continu
            // et déclenche les tâches planifiées (ex. spatie/laravel-backup) à l'heure due.
            script: 'php',
            args: 'artisan schedule:work',
            cwd: '/srv/projects/me',
            exec_mode: 'fork',
            instances: 1,
            autorestart: true,
            max_restarts: 10,
            env: {
                APP_ENV: 'production',
            },
        },
        {
            name: 'portfolio-ssr',
            // Sert le bundle resources/js/ssr.tsx pour le rendu côté serveur Inertia.
            script: 'php',
            args: 'artisan inertia:start-ssr',
            cwd: '/srv/projects/me',
            exec_mode: 'fork',
            instances: 1,
            autorestart: true,
            max_restarts: 10,
            env: {
                APP_ENV: 'production',
            },
        },
    ],
};
