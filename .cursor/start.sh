#!/usr/bin/env bash
# Keep Docker available for wp-env on every agent boot.
set -euo pipefail

if ! command -v docker >/dev/null; then
	exit 0
fi

if docker info >/dev/null 2>&1; then
	exit 0
fi

if [[ ! -S /var/run/docker.sock ]]; then
	sudo dockerd >/tmp/dockerd-start.log 2>&1 &
	for _ in $(seq 1 60); do
		docker info >/dev/null 2>&1 && break
		sleep 1
	done
fi

sudo chmod 666 /var/run/docker.sock 2>/dev/null || true

# DinD on cloud VMs: docker-compose bridge traffic is dropped with FORWARD policy DROP.
if command -v iptables-legacy >/dev/null; then
	sudo iptables-legacy -P FORWARD ACCEPT
	for br in /sys/class/net/br-+; do
		[[ -e "$br" ]] || continue
		br_name="$(basename "$br")"
		sudo iptables-legacy -C FORWARD -i "$br_name" -o "$br_name" -j ACCEPT 2>/dev/null ||
			sudo iptables-legacy -I FORWARD 1 -i "$br_name" -o "$br_name" -j ACCEPT
	done
fi
