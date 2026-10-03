User-agent: *
Disallow: /admin
Disallow: /dashboard
Disallow: /settings
Disallow: /login
Disallow: /api

# Moteurs de réponse IA : accès explicite au contenu public
User-agent: GPTBot
User-agent: OAI-SearchBot
User-agent: ChatGPT-User
User-agent: ClaudeBot
User-agent: Claude-SearchBot
User-agent: PerplexityBot
User-agent: Google-Extended
User-agent: Applebot-Extended
Allow: /
Disallow: /admin
Disallow: /dashboard
Disallow: /settings
Disallow: /login
Disallow: /api

Sitemap: {{ $baseUrl }}/sitemap.xml
