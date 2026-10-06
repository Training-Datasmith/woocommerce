#!/usr/bin/env bash
# Corpus wp-env bootstrap: fix DinD forwarding before wp-env runs compose lifecycle scripts.
set -euo pipefail

if command -v iptables-legacy >/dev/null; then
	sudo iptables-legacy -P FORWARD ACCEPT 2>/dev/null || true
	for br in /sys/class/net/br-+; do
		[[ -e "$br" ]] || continue
		br_name="$(basename "$br")"
		sudo iptables-legacy -C FORWARD -i "$br_name" -o "$br_name" -j ACCEPT 2>/dev/null ||
			sudo iptables-legacy -I FORWARD 1 -i "$br_name" -o "$br_name" -j ACCEPT 2>/dev/null || true
	done
fi

exec pnpm wp-env start "$@"
