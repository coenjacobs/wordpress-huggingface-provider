# Hugging Face Provider for WordPress

A WordPress plugin that registers [Hugging Face Inference Providers](https://huggingface.co/docs/inference-providers) as an AI provider for the WordPress AI Client. Hugging Face's router API offers an OpenAI-compatible gateway that routes requests across 19+ backend inference providers (Cerebras, Groq, Together, SambaNova, and others) with automatic failover.

## Requirements

- WordPress 7.0 or higher
- PHP 7.4 or higher
- [AI Experiments](https://wordpress.org/plugins/ai/) plugin (for running experiments through the WordPress admin)

## Installation

Clone this repository into your `wp-content/plugins/` directory:

```bash
git clone https://github.com/coenjacobs/wordpress-huggingface-provider.git wp-content/plugins/huggingface-provider
cd wp-content/plugins/huggingface-provider/plugin
composer install
```

Activate the plugin through the WordPress admin panel or WP-CLI:

```bash
wp plugin activate huggingface-provider
```

## Configuration

### API Token

The API token can be configured in three ways (in order of precedence):

1. **Environment variable**: Set `HUGGINGFACE_API_KEY` in your environment
2. **PHP constant**: Define `HUGGINGFACE_API_KEY` in `wp-config.php`
3. **Settings page**: Enter it at **Settings > Hugging Face** in the WordPress admin

You can create a token at [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens).

### Model Selection

Visit **Settings > Hugging Face** to enable specific models. The settings page displays all available models grouped by owner (e.g. meta-llama, Qwen, mistralai, google). Only enabled models are exposed to the WordPress AI Client and available for use in AI Experiments.

Use the **Refresh Model List** button to update the available models from the Hugging Face API.

### Routing Strategy

Hugging Face routes requests across multiple inference providers. The **Routing Strategy** setting controls how this routing works:

- **Preferred** (default) — uses Hugging Face's default provider ranking
- **Fastest** — prioritizes the provider with the lowest latency
- **Cheapest** — prioritizes the provider with the lowest cost

The chosen strategy is appended as a suffix to model IDs in API requests (e.g. `meta-llama/Llama-3.3-70B-Instruct:fastest`).

### Organization Billing

The optional **Organization** field lets you specify an organization to bill usage to. When set, an `X-HF-Bill-To` header is included with every API request.

## How It Works

The plugin registers a single provider (`huggingface`) with the WordPress AI Client registry on the `init` hook. All Hugging Face Inference Provider models use the OpenAI-compatible `/chat/completions` endpoint, so only a single model class is needed.

Model IDs follow the format `owner/model-name` (e.g. `meta-llama/Llama-3.3-70B-Instruct`, `Qwen/Qwen3-235B-A22B`). The settings page groups models by this owner prefix.

## Development Environment

The project includes a Docker-based development environment. No PHP, Composer, or other tools are needed on the host machine.

### Quick Start

```bash
make build    # Build the Docker image
make setup    # Full setup: download WordPress, configure, install, activate plugin
```

This gives you a working WordPress 7.0-beta2 installation at **http://localhost:8082** (admin/admin) with the plugin activated.

### Makefile Targets

| Target | Purpose |
|--------|---------|
| `make build` | Build the Docker image |
| `make setup` | Full clean setup: download WordPress, configure, install, activate plugin |
| `make up` / `make down` | Start/stop containers |
| `make clean-wp` | Stop containers and wipe the WordPress directory |
| `make composer` | Run `composer install` for the plugin |
| `make activate` | Activate the plugin via WP-CLI |

### Docker Stack

- **PHP**: 8.5 CLI Alpine with built-in web server
- **Database**: MariaDB 11
- **WordPress**: 7.0-beta2 (downloaded via `curl` + `tar`)

### Volume Mounts

- `./wordpress/` → `/var/www/html` — WordPress root (gitignored)
- `./plugin/` → `/var/www/html/wp-content/plugins/huggingface-provider` — plugin source
- `./docker/mariadb/data/` → `/var/lib/mysql` — database storage (gitignored)
