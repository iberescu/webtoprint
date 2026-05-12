#!/usr/bin/env bash
# Bring up a single ngrok tunnel pointing at the aggregator nginx (port 8080),
# then wire the resulting public URL into storefront + designer.
#
# Prereqs:
#   - ngrok authenticated (`ngrok config add-authtoken <token>`)
#   - docker compose up -d already running
#   - jq installed
#
# Stop with Ctrl-C; the trap reverts to localhost URLs.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

DEFAULT_CFG="${LOCALAPPDATA:-$HOME/.config}/ngrok/ngrok.yml"
[ -f "$DEFAULT_CFG" ] || DEFAULT_CFG="$APPDATA/ngrok/ngrok.yml"
[ -f "$DEFAULT_CFG" ] || DEFAULT_CFG=""

cleanup() {
    echo
    echo "→ Stopping ngrok and reverting storefront/designer to localhost URLs."
    kill "${NGROK_PID:-0}" 2>/dev/null || true
    unset PUBLIC_API_URL PUBLIC_SHOP_API_URL PUBLIC_DESIGNER_URL VITE_API_URL VITE_STOREFRONT_URL PUBLIC_HOST
    docker compose up -d --force-recreate storefront designer >/dev/null
}
trap cleanup EXIT INT TERM

echo "→ Starting ngrok agent..."
if [ -n "$DEFAULT_CFG" ]; then
    ngrok start --all --config "$DEFAULT_CFG" --config scripts/ngrok.yml --log=stdout --log-format=json >/tmp/ngrok.log 2>&1 &
else
    ngrok start --all --config scripts/ngrok.yml --log=stdout --log-format=json >/tmp/ngrok.log 2>&1 &
fi
NGROK_PID=$!

echo "→ Waiting for tunnel to come up..."
for _ in $(seq 1 30); do
    if curl -sf http://localhost:4040/api/tunnels >/dev/null 2>&1; then
        if [ "$(curl -s http://localhost:4040/api/tunnels | jq '.tunnels | length')" -ge 1 ]; then break; fi
    fi
    sleep 1
done

URL=$(curl -s http://localhost:4040/api/tunnels | jq -r '.tunnels[0].public_url')
if [ -z "$URL" ] || [ "$URL" = "null" ]; then
    echo "ERROR: could not read ngrok tunnel. Log tail:"
    tail -30 /tmp/ngrok.log
    exit 1
fi

# Strip the protocol to get the bare host (for HMR / WSS).
HOST=${URL#https://}; HOST=${HOST#http://}

cat <<EOF

  Public URL: $URL
  Routes:
    Storefront      : $URL/
    Product page    : $URL/products/business-card
    Designer        : $URL/designer/
    Print API       : $URL/api/v1
    Shop API        : $URL/shop/api/v1

→ Reloading storefront + designer with the public URLs baked in...
EOF

export PUBLIC_API_URL="$URL/api/v1"
export PUBLIC_SHOP_API_URL="$URL/shop/api/v1"
export PUBLIC_DESIGNER_URL="$URL/designer"
export VITE_API_URL="$URL/api/v1"
export VITE_STOREFRONT_URL="$URL"
export PUBLIC_HOST="$HOST"

docker compose up -d --force-recreate storefront designer >/dev/null

echo "→ Done. Open $URL/ in your browser. Ctrl-C to stop."
wait $NGROK_PID
