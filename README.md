# Aysar Alatrash — Developer Portfolio

A bilingual (DE/EN) personal portfolio built with **Symfony 7.4** and **Tailwind CSS**, designed, developed, deployed and operated end to end on my own Linux server.

🌐 **Live:** [aysar-alatrash.de](https://aysar-alatrash.de)

---

## Features

- **Bilingual (German / English)** with locale-based routing and full translations
- **Refined design system** — semantic design tokens, elegant serif (Playfair Display) display type, mono caps labels
- **Dark mode** — token-based theme toggle with no-flash init and `localStorage` persistence
- **AI assistant** — a built-in chat widget that answers questions about my profile, powered by the **Google Gemini API** (server-side, rate-limited, grounded in curated content)
- **Self-hosted fonts** — no Google Fonts requests; fonts served locally for privacy and speed
- **Privacy-friendly analytics** — self-hosted, cookieless [Umami](https://umami.is/) (production only)
- **Accessibility-minded** — visible focus states, ARIA-labelled controls, semantic landmarks
- **Legal pages** — Impressum and Datenschutzerklärung (DE/EN)
- **CI/CD** — automatic deployment via GitHub Actions
- **Lighthouse 100 / 100 / 100 / 100** (Performance, Accessibility, Best Practices, SEO)

---

## Tech stack

| Area        | Technology                                                        |
| ----------- | ----------------------------------------------------------------- |
| Backend     | PHP 8.2+, Symfony 7.4 (Routing, Twig, Translation, HttpClient)    |
| Frontend    | Twig, Tailwind CSS v4, AssetMapper, Stimulus, Turbo — no Node build |
| AI          | Google Gemini API (via Symfony HttpClient)                         |
| Analytics   | Self-hosted Umami (cookieless)                                     |
| Infra       | Hetzner Linux server, Nginx, HTTPS (Let's Encrypt)                 |
| CI/CD       | GitHub Actions                                                     |

> Tailwind is compiled by the [symfonycasts/tailwind-bundle](https://github.com/SymfonyCasts/tailwind-bundle) — there is **no Node.js build step**.

---

## Getting started

### Requirements

- PHP **8.2+** with the `ctype` and `iconv` extensions
- [Composer](https://getcomposer.org/)
- (Optional) the [Symfony CLI](https://symfony.com/download) for a local web server

### Install

```bash
git clone https://github.com/Aysar9/portfolio.git
cd portfolio
composer install
```

### Configure

Copy your local secrets into `.env.local` (never committed):

```dotenv
# Required only for the AI chat assistant — without it the assistant
# returns a graceful "unavailable" response instead of erroring.
GEMINI_API_KEY=your-gemini-api-key
GEMINI_MODEL=gemini-2.5-flash
```

Get a key from [Google AI Studio](https://aistudio.google.com/apikey).

### Build CSS & run

```bash
# Compile Tailwind (use --watch during development)
php bin/console tailwind:build --watch

# Start the dev server
symfony serve        # or: php -S localhost:8000 -t public
```

Then open <http://localhost:8000>.

---

## Project structure

```
assets/
  controllers/      Stimulus controllers (navbar, theme, chat)
  styles/app.css    Tailwind entry + design tokens + @font-face
src/
  Controller/       HomeController (pages), ChatController (AI endpoint)
  Service/          PortfolioKnowledge (grounding content for the assistant)
templates/
  base.html.twig    Layout: nav, footer, theme + analytics, chat widget
  home/             Page templates (home, about, projects, contact, legal)
  _chat.html.twig   AI assistant widget
translations/       messages.de.yaml / messages.en.yaml
public/
  fonts/            Self-hosted woff2 fonts
  files/            CV (PDF)
```

---

## Internationalization

All routes are prefixed with `/{_locale}` (`de` | `en`); `/` redirects to the German homepage. UI strings live in `translations/messages.{de,en}.yaml`, and the language toggle in the header switches locale while preserving the current route.

---

## AI assistant

The chat widget posts to `POST /api/chat` ([`ChatController`](src/Controller/ChatController.php)), which calls the Gemini API with a system instruction built from [`PortfolioKnowledge`](src/Service/PortfolioKnowledge.php). It is grounded strictly in curated CV/project facts, replies in the visitor's language, and is protected by per-IP rate limiting and input length caps. The API key stays server-side.

---

## Deployment

Pushing to the default branch triggers a **GitHub Actions** workflow that deploys to a Hetzner Linux server (Nginx, HTTPS via Let's Encrypt). Production environment variables (e.g. `GEMINI_API_KEY`) are configured on the server / in CI secrets — never committed.

---

## License

© Aysar Alatrash. All rights reserved. This repository is published as a portfolio showcase; feel free to browse it for inspiration.

---

## Contact

- **Email:** aysar.it.it@gmail.com
- **GitHub:** [github.com/Aysar9](https://github.com/Aysar9)
- **Website:** [aysar-alatrash.de](https://aysar-alatrash.de)
