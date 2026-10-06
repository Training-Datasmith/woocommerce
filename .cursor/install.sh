#!/usr/bin/env bash
# Cloud corpus bootstrap: Node 20+, pnpm@9, Docker, monorepo install.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if ! command -v node >/dev/null; then
	echo "Node.js is required (20+)." >&2
	exit 1
fi

NODE_MAJOR="$(node -p 'process.versions.node.split(".")[0]')"
if [[ "${NODE_MAJOR}" -lt 20 ]]; then
	echo "Node 20+ required; found $(node -v)." >&2
	exit 1
fi

if ! command -v pnpm >/dev/null; then
	corepack enable
	corepack prepare pnpm@9.15.0 --activate
fi

if ! command -v docker >/dev/null; then
	export DEBIAN_FRONTEND=noninteractive
	sudo apt-get update -qq
	sudo apt-get install -y -qq docker.io docker-compose-v2
fi

if ! command -v composer >/dev/null; then
	export DEBIAN_FRONTEND=noninteractive
	sudo apt-get install -y -qq php-cli php-xml php-mbstring php-zip unzip curl
	curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! docker info >/dev/null 2>&1; then
	if [[ ! -S /var/run/docker.sock ]]; then
		sudo dockerd >/tmp/dockerd-install.log 2>&1 &
		for _ in $(seq 1 30); do
			docker info >/dev/null 2>&1 && break
			sleep 1
		done
	fi
	if ! docker info >/dev/null 2>&1; then
		sudo chmod 666 /var/run/docker.sock 2>/dev/null || true
	fi
fi

docker info >/dev/null || {
	echo "Docker daemon is not available." >&2
	exit 1
}

if command -v iptables-legacy >/dev/null; then
	sudo iptables-legacy -P FORWARD ACCEPT
fi

pnpm install --frozen-lockfile

php "${ROOT}/plugins/woocommerce/bin/generate-feature-config.php"

CORPUS_OVERRIDE="${ROOT}/plugins/woocommerce/.wp-env.corpus.json"
if [[ -f "${CORPUS_OVERRIDE}" ]]; then
	ln -sf .wp-env.corpus.json "${ROOT}/plugins/woocommerce/.wp-env.override.json"
fi

pnpm --filter=@woocommerce/plugin-woocommerce exec wp-env --version
