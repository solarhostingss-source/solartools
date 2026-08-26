# ☀️ SolarTools — Blueprint Extension for Pterodactyl

> Solar AI Console Assistant + Discord Webhook Manager

A [Blueprint Framework](https://blueprint.zip) extension for the [Pterodactyl Panel](https://pterodactyl.io) that adds AI-powered console log analysis (powered by Google Gemini) and per-server Discord webhook notifications.

## ✨ Features

### ☀️ Solar AI — Console Assistant
- One-click **"Analizar con Solar AI"** button injected into the server console
- Captures the last 50 lines from the xterm.js terminal
- Sends logs to **Google Gemini AI** for intelligent error analysis
- Identifies critical errors, warnings, performance issues, and suggests fixes
- Elegant dark-themed modal with Markdown rendering

### 🔔 Discord Webhooks — Server State Notifications
- Per-server Discord webhook URL configuration
- Automatic notifications on server state changes (online, offline, starting, stopping, etc.)
- Rich Discord embeds with color-coded status indicators
- Built-in webhook test functionality
- New **"Webhooks"** tab in server navigation

## 📦 Installation

### Prerequisites
- Pterodactyl Panel installed and running
- [Blueprint Framework](https://blueprint.zip) installed on the panel
- A Google Gemini API key ([Get one here](https://aistudio.google.com/apikey))

### Quick Install

```bash
# 1. Download or copy solartools.blueprint to the panel directory
cp solartools.blueprint /srv/pterodactyl/

# 2. Install the extension
cd /srv/pterodactyl
blueprint -i solartools.blueprint

# 3. Add your Gemini API key to .env
echo "GEMINI_API_KEY=your_key_here" >> .env

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
```

## 🗂️ File Structure

```
├── conf.yml                          # Blueprint manifest
├── solartools.blueprint              # Compiled extension package
├── components/
│   ├── Components.yml                # React component injection map
│   ├── SolarAIButton.tsx             # AI analysis button for console
│   └── WebhookSettings.tsx           # Webhook configuration page
├── app/
│   ├── Controllers/
│   │   ├── SolarAIController.php     # Gemini AI integration
│   │   └── WebhookController.php     # Webhook CRUD operations
│   ├── Listeners/
│   │   └── ServerStateListener.php   # Server state → Discord alerts
│   └── Providers/
│       └── SolarToolsServiceProvider.php
├── routes/
│   ├── client.php                    # Client API routes
│   └── web.php                       # Web routes
├── migrations/
│   └── 2025_01_01_..._add_discord_webhook_to_servers.php
├── admin/
│   ├── view.blade.php                # Admin panel view
│   └── controller.php                # Admin controller
└── css/
    └── solartools.css                # Dashboard styles
```

## 🔗 API Endpoints

All routes are prefixed with `/api/client/extensions/solartools/`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/ai/analyze` | Send console logs for AI analysis |
| `GET` | `/webhook/{server}` | Get configured webhook URL |
| `POST` | `/webhook/{server}` | Save/update webhook URL |
| `POST` | `/webhook/{server}/test` | Send test notification |

## ⚙️ Configuration

The only required configuration is the `GEMINI_API_KEY` environment variable in your panel's `.env` file. The admin panel page (`/admin/extensions/solartools`) shows the current status of the API key.

## 📄 License

MIT

## 👤 Author

**SolarCloud** — [solarcloud.dev](https://solarcloud.dev)
